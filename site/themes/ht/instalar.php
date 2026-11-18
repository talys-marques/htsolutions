<?php
/**
 * Prepara o site da HT Solutions neste blog (na pasta site/):
 *
 *   php themes/ht/instalar.php              cria as páginas que faltam a partir de themes/ht/content/
 *   php themes/ht/instalar.php --atualizar  também sobrescreve as páginas que existem com o content/
 *   php themes/ht/instalar.php --exportar   grava em content/ o que está no site (para versionar no Git)
 *
 * As páginas vêm do Conteúdo do site do núcleo (SiteContent::install/export, o mesmo que
 * tools/site-content.php). Este script acrescenta o que é só da HT: ativa o tema, cria a página
 * dos posts (/blog/), escolhe o início e redireciona os endereços do site antigo
 * (/blog/slug → /slug/). Precisa da conta de administrador.
 */
if (PHP_SAPI !== 'cli') {
    exit("Use pela linha de comando.\n");
}
require dirname(__DIR__, 2) . '/app/bootstrap.php';

$opcoes = array_slice($argv, 1);
if (setting('theme') !== 'ht') {
    Settings::save(['theme' => 'ht']);
    echo "  tema ativado: HT Solutions\n";
}

if (in_array('--exportar', $opcoes, true)) {
    foreach (SiteContent::export() as $arquivo) {
        echo "  $arquivo\n";
    }
    exit(0);
}

$admin = Auth::admin();
if (!$admin) {
    fwrite(STDERR, "Crie a conta de administrador antes (abra /admin no navegador).\n");
    exit(1);
}

// Páginas do site
$log = SiteContent::install($admin['username'], in_array('--atualizar', $opcoes, true));
foreach (['created' => 'criada', 'updated' => 'atualizada', 'skipped' => 'já existe'] as $chave => $rotulo) {
    foreach ($log[$chave] as $caminho) {
        echo "  $rotulo: $caminho\n";
    }
}

// A página dos posts (/blog/). Um post antigo usava o endereço "blog": ganha um próprio.
$conflito = Posts::findBySlug('blog');
if ($conflito && $conflito['type'] === 'post') {
    $post = Posts::find($conflito['id']);
    $novo = Posts::save(['id' => $post['id'], 'slug' => slugify($post['title']), 'slug_antigo' => 'blog'], false);
    echo "  post /blog/ mudou para /{$novo['slug']}/\n";
}
$blog = Posts::findBySlug('blog');
if (!$blog) {
    $blog = Posts::save([
        'type' => 'page', 'status' => 'published', 'title' => 'Blog', 'slug' => 'blog', 'author' => $admin['username'],
        'content' => '<p>Notícias, cases e conteúdos sobre infraestrutura de TI, educação e transformação digital.</p>',
        'seo_description' => 'Notícias, cases e conteúdos da HT Solutions sobre infraestrutura de TI, Google for Education, Chrome Enterprise e transformação digital.',
    ]);
    echo "  criada: /blog/\n";
}

$inicio = SiteContent::pageOf('inicio');
$configuracoes = ['home_page' => $inicio['id'] ?? 0, 'blog_page' => $blog['id']];
if (in_array(setting('title'), ['Meu Blog', 'Blog HT Solutions', 'Blog HT Solution'], true)) {
    $configuracoes += [
        'title' => 'HT Solutions',
        'tagline' => 'Infraestrutura de TI e transformação digital',
        'description' => 'Especialistas em infraestrutura de TI, com as mais elevadas certificações. Acelerando a transformação digital na sua empresa.',
    ];
}
if (str_ends_with(rtrim((string) setting('url'), '/'), '/blog')) {
    $configuracoes['url'] = substr(rtrim((string) setting('url'), '/'), 0, -5);
}
Settings::save($configuracoes);

// Endereços do site antigo (Angular): /blog/slug e /blog/categoria/x. O endereço "/blog" sozinho é a
// página dos posts, então sai da lista (ele foi criado quando o post de mesmo nome mudou de endereço).
$redirecionamentos = ['/campanha-hp' => '/hp/'];
foreach (Posts::items() as $item) {
    $post = $item['type'] === 'post' ? Posts::find($item['id']) : null;
    if ($post && isset($post['strapi_id'])) {
        $redirecionamentos['/blog/' . ($post['slug_antigo'] ?? $item['slug'])] = '/' . $item['slug'] . '/';
    }
}
foreach (Posts::terms('categories', false) as $slug => $termo) {
    $redirecionamentos["/blog/categoria/$slug"] = "/category/$slug/";
}
$todos = Redirects::all();
unset($todos['/blog']);
Redirects::replaceAll(array_merge($todos, $redirecionamentos));

echo "Pronto: início = /, posts em /blog/, " . count($redirecionamentos) . " endereços antigos redirecionados.\n";
