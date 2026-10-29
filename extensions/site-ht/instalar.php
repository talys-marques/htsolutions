<?php
/**
 * Prepara o site da HT Solutions neste blog a partir de extensions/site-ht/conteudo/*.json:
 *
 *   php extensions/site-ht/instalar.php              cria as páginas que faltam (não mexe nas que existem)
 *   php extensions/site-ht/instalar.php --atualizar  sobrescreve os campos das páginas com o conteudo/
 *   php extensions/site-ht/instalar.php --exportar   grava em conteudo/ o que está publicado (para versionar)
 *
 * Também configura o início (página "inicio"), a página dos posts (/blog/), o nome do site e os
 * redirecionamentos dos endereços do site antigo (/blog/slug → /slug/). Precisa da conta de administrador.
 */
if (PHP_SAPI !== 'cli') {
    exit("Use pela linha de comando.\n");
}
require dirname(__DIR__, 2) . '/app/bootstrap.php';

$opcoes = array_slice($argv, 1);
$pasta = __DIR__ . '/conteudo';

if (in_array('--exportar', $opcoes, true)) {
    foreach (SiteHT::paginas() as ['post' => $post]) {
        $arquivo = "$pasta/{$post['modelo']}.json";
        $atual = is_file($arquivo) ? json_decode((string) file_get_contents($arquivo), true) : [];
        $dados = [
            'titulo' => $post['title'],
            'slug' => $post['slug'],
            'status' => $post['status'] === 'draft' ? 'draft' : 'published',
            'descricao' => $post['seo_description'],
            'campos' => $post['campos'],
        ] + (array) $atual;
        file_put_contents($arquivo, json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
        echo "  {$post['modelo']}.json\n";
    }
    exit(0);
}

$admin = Auth::admin();
if (!$admin) {
    fwrite(STDERR, "Crie a conta de administrador antes (abra /admin no navegador).\n");
    exit(1);
}
$atualizar = in_array('--atualizar', $opcoes, true);

// Páginas do site
$ids = [];
foreach (glob("$pasta/*.json") as $arquivo) {
    $modelo = basename($arquivo, '.json');
    $dados = json_decode((string) file_get_contents($arquivo), true);
    $esquema = SiteHT::modelo($modelo);
    if (!$esquema || !is_array($dados)) {
        fwrite(STDERR, "Ignorado: $arquivo\n");
        continue;
    }
    $existente = Posts::findBySlug($dados['slug']);
    if ($existente && !$atualizar) {
        $ids[$modelo] = $existente['id'];
        echo "  já existe: /{$dados['slug']}/\n";
        continue;
    }
    $post = Posts::save([
        'id' => $existente['id'] ?? 0,
        'type' => 'page',
        'status' => $dados['status'] ?? 'published',
        'title' => $dados['titulo'],
        'slug' => $dados['slug'],
        'seo_description' => $dados['descricao'] ?? '',
        'author' => $existente['author'] ?? $admin['username'],
        // O texto comum da página não aparece: o conteúdo vem dos campos
        'content' => '<p>Esta página é editada em <strong>Conteúdo do site</strong>, no menu do painel.</p>',
        'modelo' => $modelo,
        'campos' => SiteHT::limpar($esquema['campos'], $dados['campos'] ?? []),
    ]);
    $ids[$modelo] = $post['id'];
    echo ($existente ? '  atualizada: ' : '  criada: ') . "/{$post['slug']}/\n";
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

$configuracoes = ['home_page' => $ids['inicio'] ?? 0, 'blog_page' => $blog['id']];
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
