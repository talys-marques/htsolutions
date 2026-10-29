<?php
/**
 * Páginas públicas do blog. Cada método monta os dados e chama um
 * template de theme/. Rotas em app/routes/site.php.
 * Devolver false = "não encontrei" (vira 404 ou redirecionamento).
 */
final class Site
{
    public static function home(int $page = 1)
    {
        if (isset($_GET['s'])) {
            return self::search(get_param('s'), $page);
        }
        if ($page === 1 && (isset($_GET['p']) || isset($_GET['page_id']))) {
            return self::legacyId((int) ($_GET['p'] ?? $_GET['page_id']));
        }
        // Página fixa no início (Configurações → Página inicial). A lista de posts, se houver, fica na página dos posts.
        if ($home = Posts::homePage()) {
            if ($page > 1) {
                if ($blog = Posts::blogPage()) {
                    redirect(post_path($blog) . "page/$page/", 301); // /page/2/ do WordPress
                }
                return false;
            }
            $post = Posts::find($home['id']);
            if (!$post) {
                return false;
            }
            self::renderPost($post);
            return true;
        }
        return self::blog($page);
    }

    /**
     * Lista de posts, na estrutura de Configurações → Página inicial. Fica no início do blog
     * ou, quando o início é uma página fixa, no endereço da página dos posts ($slug).
     */
    public static function blog(int $page = 1, string $slug = '')
    {
        $blog = $slug !== '' ? Posts::blogPage() : null;
        if ($slug !== '' && ($blog['slug'] ?? null) !== $slug) {
            return false;
        }
        $blog = $blog ? Posts::find($blog['id']) : null;
        $base = $blog ? post_path($blog) : '/';

        $pagination = paginate(Posts::query(['type' => 'post', 'public' => true]), $page, (int) setting('posts_per_page', 10));
        if ($page > 1 && !$pagination['items']) {
            return false;
        }

        // Estrutura escolhida em Configurações. Destaque e seções só na primeira página.
        $layout = (string) setting('home_layout');
        if (!isset(Settings::homeLayouts()[$layout])) {
            $layout = 'classic';
        }
        $columns = max(2, min(4, (int) setting('home_columns', 3)));
        $posts = $pagination['items'];
        $featured = $page === 1 && ($layout === 'magazine' || setting('home_featured')) ? array_shift($posts) : null;
        $side = $featured && $layout === 'magazine' ? array_splice($posts, 0, 2) : [];
        $sections = $layout === 'categories' && $page === 1 ? self::categorySections($featured, $columns) : [];

        $path = fn(int $n) => $n === 1 ? $base : $base . "page/$n/";
        $meta = ['title' => Seo::homeTitle($page), 'canonical' => absolute_url($path($page)), 'schema' => 'collection'];
        if ($blog) {
            // Título e SEO da própria página dos posts; o texto dela aparece acima da lista (theme/home.php).
            $blog['content'] = Media::optimizeHtml($blog['content']);
            $meta = [
                'title' => Seo::title($blog['seo_title'] ?: $blog['title'], $page),
                'og_title' => $blog['seo_title'] ?: $blog['title'],
                'description' => $blog['seo_description'] ?: ($blog['excerpt'] ?: excerpt_from($blog['content'], 160))
                    ?: Seo::listDescription('Posts recentes', $pagination['items']),
                'image' => $blog['cover'],
                'image_alt' => $blog['cover_alt'],
                'noindex' => !empty($blog['seo_noindex']),
                'breadcrumb' => [['Início', absolute_url('/')], [$blog['title'], absolute_url($base)]],
            ] + $meta;
        }

        theme('home', [
            'layout' => $layout,
            'columns' => $columns,
            'featured' => $featured,
            'side' => $side,
            'posts' => $posts,
            'sections' => $sections,
            'blog' => $blog,
            'body_class' => ($blog ? 'blog' : 'home') . ' home-' . $layout,
            'pagination' => $pagination,
            'page_link' => fn(int $n) => url($path($n)),
            'meta' => $meta,
        ]);
        return true;
    }

    /** Estrutura "Por categoria": as 6 categorias com mais posts e os mais recentes de cada (sem repetir o destaque). */
    private static function categorySections(?array $featured, int $perSection): array
    {
        $sections = [];
        foreach (array_slice(Posts::terms('categories'), 0, 6) as $term) {
            $posts = array_filter(
                Posts::query(['type' => 'post', 'public' => true, 'category' => $term['slug']]),
                fn($p) => $p['id'] !== ($featured['id'] ?? null)
            );
            if ($posts) {
                $sections[] = $term + ['posts' => array_slice($posts, 0, $perSection)];
            }
        }
        return $sections;
    }

    /** $kind: 'category', 'tag' ou 'author'. */
    public static function archive(string $kind, string $slug, int $page = 1)
    {
        $items = Posts::query(['type' => 'post', 'public' => true, $kind => $slug]);
        if (!$items) {
            return false;
        }
        if ($kind === 'author') {
            $label = 'Autor';
            $name = Auth::find($slug)['name'] ?? $slug;
        } else {
            $label = $kind === 'tag' ? 'Tag' : 'Categoria';
            $name = $slug;
            foreach ($items[0][$kind === 'tag' ? 'tags' : 'categories'] as $term) {
                if (slugify($term) === $slug) {
                    $name = $term;
                }
            }
        }
        $pagination = paginate($items, $page, (int) setting('posts_per_page', 10));
        if (!$pagination['items']) {
            return false;
        }
        $base = "/$kind/" . rawurlencode($slug) . '/';
        // Textos de Painel → SEO → Categorias e tags. Sem eles, a descrição é montada com os títulos dos posts.
        $seo = $kind === 'author' ? Terms::blank() : Terms::get($kind, $slug);
        $description = $seo['seo_description'] ?: excerpt_from($seo['description'], 160)
            ?: Seo::listDescription(($kind === 'author' ? 'Posts de ' : 'Posts sobre ') . $name, $pagination['items']);
        theme('archive', [
            'label' => $label,
            'name' => $name,
            // HTML escrito no painel (links, imagens), com as imagens otimizadas como no texto dos posts.
            // No desktop a imagem fica pequena, ao lado do texto (site.css, .archive-description).
            'description' => $page === 1 ? Media::optimizeHtml($seo['description'], true, '(max-width: 799px) 100vw, 320px') : '',
            'edit_url' => $kind !== 'author' && Auth::user() ? url("/admin/seo/terms/$kind/" . rawurlencode($slug)) : '',
            'posts' => $pagination['items'],
            'pagination' => $pagination,
            'page_link' => fn(int $n) => url($n === 1 ? $base : $base . "page/$n/"),
            'meta' => [
                'title' => Seo::title($seo['seo_title'] ?: $name, $page),
                'description' => $description,
                'canonical' => absolute_url($page === 1 ? $base : $base . "page/$page/"),
                'noindex' => !Seo::indexable($kind, $slug),
                'schema' => 'collection',
                'breadcrumb' => [['Início', absolute_url('/')], [$name, absolute_url($base)]],
            ],
        ]);
        return true;
    }

    public static function search(string $query, int $page = 1)
    {
        $query = trim(mb_substr($query, 0, 100));
        $results = $query === '' ? [] : Posts::query(['public' => true, 'search' => $query]);
        $pagination = paginate($results, $page, (int) setting('posts_per_page', 10));
        theme('search', [
            'query' => $query,
            'posts' => $pagination['items'],
            'pagination' => $pagination,
            'page_link' => fn(int $n) => url($n === 1 ? '/' : "/page/$n/") . '?s=' . rawurlencode($query),
            'meta' => ['title' => Seo::title($query !== '' ? "Busca: $query" : 'Busca'), 'noindex' => true],
        ]);
        return true;
    }

    /**
     * Sugestões enquanto o visitante digita na busca (/search.json?s=termo), usadas por site.js.
     * Mesma busca da página de resultados: os primeiros posts e as categorias e tags com o termo no nome.
     */
    public static function suggest(): void
    {
        $query = trim(mb_substr(get_param('s'), 0, 100));
        $results = $query === '' ? [] : Posts::query(['public' => true, 'search' => $query]);
        $posts = array_map(fn($p) => [
            'title' => $p['title'] !== '' ? $p['title'] : '(sem título)',
            'url' => post_url($p),
            'meta' => $p['type'] === 'page' ? 'Página' : implode(' · ', array_filter([$p['categories'][0] ?? '', format_date($p['published_at'])])),
        ], array_slice($results, 0, 6));

        $terms = [];
        $needle = normalize_text($query);
        if (mb_strlen($needle) >= 2) {
            foreach (['categories' => 'Categoria', 'tags' => 'Tag'] as $field => $label) {
                foreach (Posts::terms($field) as $term) {
                    if (count($terms) < 3 && str_contains(normalize_text($term['name']), $needle)) {
                        $terms[] = [
                            'title' => $term['name'],
                            'url' => term_url($field === 'tags' ? 'tag' : 'category', $term['name']),
                            'meta' => $label . ' · ' . $term['count'] . ($term['count'] === 1 ? ' post' : ' posts'),
                        ];
                    }
                }
            }
        }
        header('X-Robots-Tag: noindex');
        json_response(['posts' => $posts, 'terms' => $terms, 'total' => count($results)]);
    }

    public static function single(string $slug)
    {
        $item = Posts::findBySlug($slug);
        if (!$item) {
            return false;
        }
        $public = Posts::isPublic($item);
        if (!$public && !Auth::canEdit($item)) {
            return false; // rascunhos e agendados só aparecem para quem pode editá-los
        }
        if ($item['id'] === (Posts::blogPage()['id'] ?? null)) {
            return self::blog(1, $slug);
        }
        // A página escolhida como inicial cai em renderPost() com o endereço oficial "/", e theme() redireciona para lá.
        $post = Posts::find($item['id']);
        if (!$post) {
            return false;
        }
        self::renderPost($post, !$public);
        return true;
    }

    public static function renderPost(array $post, bool $preview = false): void
    {
        $author = Auth::find($post['author']);
        [$prev, $next] = $post['type'] === 'post' && !$preview ? Posts::adjacent($post['id']) : [null, null];
        $isPost = $post['type'] === 'post';
        $isHome = post_path($post) === '/'; // página escolhida como inicial
        $canonical = absolute_url(post_path($post));
        $category = $isPost ? ($post['categories'][0] ?? null) : null;

        $meta = [
            // Na página inicial fixa, sem título de SEO próprio, valem o título e a descrição do blog (Painel → SEO).
            'title' => $isHome && !$post['seo_title'] ? Seo::homeTitle() : Seo::title($post['seo_title'] ?: $post['title']),
            'og_title' => $post['seo_title'] ?: ($isHome ? setting('title') : $post['title']),
            'headline' => $post['title'],
            'description' => $post['seo_description'] ?: ($isHome ? Seo::siteDescription() : '')
                ?: ($post['excerpt'] ?: excerpt_from($post['content'], 160)),
            'canonical' => $canonical,
            'image' => $post['cover'],
            'image_alt' => $post['cover_alt'],
            'type' => $isPost ? 'article' : 'website',
            'published' => $post['published_at'],
            'modified' => $post['updated_at'],
            'section' => (string) $category,
            'tags' => $isPost ? $post['tags'] : [],
            'author' => $isPost ? array_filter([
                'name' => $author['name'] ?? $post['author'],
                'url' => setting('show_author') ? full_url(author_url($post['author'])) : null,
            ]) : null,
            'noindex' => $preview || !empty($post['seo_noindex']),
            'schema' => $isPost ? 'post' : 'page',
            'breadcrumb' => $isHome ? [] : array_values(array_filter([
                ['Início', absolute_url('/')],
                $category ? [$category, full_url(term_url('category', $category))] : null,
                [$post['title'], $canonical],
            ])),
        ];
        // Imagens do texto: carregamento sob demanda e versões menores (srcset).
        $post['content'] = Media::optimizeHtml($post['content'], $post['cover'] === '');

        theme($post['type'] === 'page' ? 'page' : 'post', [
            'post' => $post,
            'author' => $author,
            'prev' => $prev,
            'next' => $next,
            // "Leia também" (Configurações → Leitura), sem repetir o anterior e o próximo.
            'related' => $isPost ? Posts::related($post, (int) setting('related_posts', 3), array_column(array_filter([$prev, $next]), 'id')) : [],
            'preview' => $preview,
            'reading_time' => reading_time($post['content']),
            'meta' => $meta,
        ] + ($isHome ? ['body_class' => 'page front-page'] : []));
    }

    /** Links antigos do WordPress: /?p=123 e /?page_id=123 */
    private static function legacyId(int $id)
    {
        $item = Posts::findByLegacyId($id);
        if (!$item || !Posts::isPublic($item)) {
            return false;
        }
        redirect(post_url($item), 301);
    }

    /** Só o que pode aparecer no Google (Painel → SEO), com a capa de cada post para o Google Imagens. */
    public static function sitemap(): void
    {
        $x = fn($s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $items = array_filter(Posts::query(['public' => true]), fn($item) => empty($item['noindex']));
        $lines = [];
        $add = function (string $path, ?string $modified = null, string $image = '') use (&$lines, $x) {
            $ts = $modified ? strtotime($modified) : false;
            $lines[] = '  <url><loc>' . $x(absolute_url($path)) . '</loc>'
                . ($ts ? '<lastmod>' . date('c', $ts) . '</lastmod>' : '')
                . ($image !== '' ? '<image:image><image:loc>' . $x(full_url($image)) . '</image:loc></image:image>' : '')
                . '</url>';
        };
        // Com página fixa no início, "/" é ela; a página dos posts muda sempre que sai um post.
        $home = Posts::homePage();
        $blogId = Posts::blogPage()['id'] ?? null;
        $newest = $items ? max(array_column($items, 'updated_at')) : null;
        if (!$home || empty($home['noindex'])) {
            $add('/', $home ? $home['updated_at'] : $newest, $home['cover'] ?? '');
        }
        $latest = []; // última alteração entre os posts de cada categoria, tag e autor
        foreach ($items as $item) {
            if ($item['id'] === ($home['id'] ?? null)) {
                continue;
            }
            $add(post_path($item), $item['id'] === $blogId ? $newest : $item['updated_at'], $item['cover']);
            if ($item['type'] !== 'post') {
                continue;
            }
            foreach (['category' => $item['categories'], 'tag' => $item['tags'], 'author' => [$item['author']]] as $kind => $names) {
                foreach ($names as $name) {
                    $slug = $kind === 'author' ? $name : slugify($name);
                    $latest[$kind][$slug] = max($latest[$kind][$slug] ?? '', $item['updated_at']);
                }
            }
        }
        foreach ($latest as $kind => $terms) {
            foreach ($terms as $slug => $modified) {
                if (Seo::indexable($kind, (string) $slug)) {
                    $add("/$kind/" . rawurlencode((string) $slug) . '/', $modified);
                }
            }
        }
        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">', "\n",
            implode("\n", $lines), "\n</urlset>\n";
    }

    /** /favicon.ico: alguns navegadores e robôs pedem este endereço direto. */
    public static function favicon(): void
    {
        header('Location: ' . favicon_url(), true, 302); // 302: o dono pode trocar o ícone
    }

    public static function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        echo Seo::robotsTxt();
    }

    public static function notFound(): void
    {
        theme('404', ['meta' => ['title' => Seo::title('Página não encontrada'), 'noindex' => true]], 404);
    }
}
