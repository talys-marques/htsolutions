<?php
/**
 * SEO das páginas públicas: título, meta tags, Open Graph, dados estruturados
 * (schema.org), o que entra ou não no Google e os redirecionamentos para o
 * endereço oficial. É o que o Yoast fazia no WordPress.
 * Opções em Painel → SEO (chaves seo_* de Settings); textos das categorias e tags em Terms.
 *
 * Site.php monta $meta de cada página e theme/layout.php imprime Seo::head($meta).
 * Campos de $meta (todos opcionais):
 *   title        título da aba, já com o nome do blog (use Seo::title())
 *   og_title     título para as redes sociais (vazio = title)
 *   headline     título do post nos dados estruturados
 *   description  meta description (vazio = descrição do site)
 *   canonical    URL absoluta oficial da página
 *   image        imagem para as redes sociais (vazio = imagem padrão); image_alt = texto alternativo dela
 *   type         'website' | 'article'
 *   published, modified, section (categoria principal), tags[], author [name, url]   só posts
 *   noindex      true = fora do Google
 *   schema       'post' | 'page' | 'collection' (listagens) | null = só os dados do site
 *   breadcrumb   [[nome, URL absoluta], ...] do início até a página
 */
final class Seo
{
    /** Separadores que o dono pode escolher para o título ("Post – Blog"). */
    public const SEPARATORS = ['–', '|', '-', '·', '•', '—'];

    public static function separator(): string
    {
        $sep = (string) setting('seo_separator');
        return in_array($sep, self::SEPARATORS, true) ? $sep : '–';
    }

    /** "Texto – Nome do blog", com "– Página 2" a partir da segunda página. */
    public static function title(string $text, int $page = 1): string
    {
        $sep = self::separator();
        return $text . ($page > 1 ? " $sep Página $page" : '') . " $sep " . setting('title');
    }

    public static function homeTitle(int $page = 1): string
    {
        $sep = self::separator();
        if ($page > 1) {
            return setting('title') . " $sep Página $page";
        }
        return (string) (setting('seo_home_title') ?: setting('title') . (setting('tagline') ? " $sep " . setting('tagline') : ''));
    }

    /** Descrição da página inicial, usada também onde não há outra. */
    public static function siteDescription(): string
    {
        return (string) (setting('description') ?: setting('tagline'));
    }

    /** Descrição para listagens sem texto próprio: "Posts sobre X: Título 1, Título 2 e Título 3." */
    public static function listDescription(string $intro, array $posts): string
    {
        $titles = array_slice(array_column($posts, 'title'), 0, 3);
        $last = count($titles) > 1 ? ' e ' . array_pop($titles) : '';
        return excerpt_from($intro . ': ' . implode(', ', $titles) . $last . '.', 160);
    }

    /** A página de uma categoria, tag ou autor pode aparecer no Google? $kind: 'category' | 'tag' | 'author'. */
    public static function indexable(string $kind, string $slug): bool
    {
        return match ($kind) {
            'author' => setting('seo_index_authors') && setting('show_author'),
            'tag' => setting('seo_index_tags') && !Terms::get('tag', $slug)['noindex'],
            default => !Terms::get($kind, $slug)['noindex'],
        };
    }

    /** Conteúdo da meta tag robots (o "max-image-preview:large" libera imagens grandes no Google Discover). */
    public static function robots(bool $noindex): string
    {
        if (setting('seo_hide_site')) {
            return 'noindex, nofollow';
        }
        return $noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    }

    /** Tags de SEO do <head>: título, descrição, robots, canonical, verificações, Open Graph, Twitter e JSON-LD. */
    public static function head(array $meta): string
    {
        $meta += [
            'title' => setting('title'), 'og_title' => '', 'headline' => '', 'description' => self::siteDescription(),
            'canonical' => '', 'image' => '', 'image_alt' => '', 'type' => 'website', 'noindex' => false,
            'published' => '', 'modified' => '', 'section' => '', 'tags' => [], 'author' => null,
            'schema' => null, 'breadcrumb' => [],
        ];
        $meta['image'] = $meta['image'] ?: (string) setting('default_image');
        $size = $meta['image'] !== '' ? Media::size($meta['image']) : null;

        $out = ['<title>' . e($meta['title']) . '</title>'];
        $tag = function (string $attr, string $name, $content) use (&$out) {
            if ((string) $content !== '') {
                $out[] = '<meta ' . $attr . '="' . e($name) . '" content="' . e($content) . '">';
            }
        };
        $tag('name', 'description', $meta['description']);
        $tag('name', 'robots', self::robots((bool) $meta['noindex']));
        if ($meta['canonical']) {
            $out[] = '<link rel="canonical" href="' . e($meta['canonical']) . '">';
        }
        $tag('name', 'google-site-verification', setting('seo_google'));
        $tag('name', 'msvalidate.01', setting('seo_bing'));
        $tag('property', 'og:locale', str_replace('-', '_', (string) setting('language')));
        $tag('property', 'og:site_name', setting('title'));
        $tag('property', 'og:type', $meta['type']);
        $tag('property', 'og:title', $meta['og_title'] ?: $meta['title']);
        $tag('property', 'og:description', $meta['description']);
        $tag('property', 'og:url', $meta['canonical']);
        if ($meta['image'] !== '') {
            $tag('property', 'og:image', full_url($meta['image']));
            $tag('property', 'og:image:width', $size[0] ?? '');
            $tag('property', 'og:image:height', $size[1] ?? '');
            $tag('property', 'og:image:alt', $meta['image_alt']);
        }
        if ($meta['type'] === 'article' && $meta['published']) {
            $tag('property', 'article:published_time', $meta['published']);
            $tag('property', 'article:modified_time', $meta['modified'] ?: $meta['published']);
            $tag('property', 'article:section', $meta['section']);
            foreach ($meta['tags'] as $name) {
                $tag('property', 'article:tag', $name);
            }
        }
        $tag('name', 'twitter:card', $meta['image'] !== '' ? 'summary_large_image' : 'summary');
        $tag('name', 'twitter:site', self::twitterUser());
        $json = json_encode(['@context' => 'https://schema.org', '@graph' => self::graph($meta, $size)],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE);
        $out[] = '<script type="application/ld+json">' . $json . '</script>';
        return implode("\n", $out) . "\n";
    }

    /**
     * Endereço oficial (Configurações → Endereço do site) com o caminho pedido, quando a
     * requisição chegou por outro domínio ou protocolo (http://, www...). null = já está certo.
     * Não vale no painel (um endereço digitado errado trancaria o dono do lado de fora)
     * nem no servidor embutido do PHP (testes locais com os dados de produção).
     */
    public static function hostRedirect(string $path): ?string
    {
        $configured = parse_url((string) setting('url'));
        if (empty($configured['host']) || PHP_SAPI === 'cli-server' || str_starts_with($path . '/', '/admin/')) {
            return null;
        }
        $host = (string) preg_replace('/:(80|443)$/', '', strtolower($_SERVER['HTTP_HOST'] ?? ''));
        $want = strtolower($configured['host']) . (isset($configured['port']) ? ':' . $configured['port'] : '');
        $scheme = strtolower($configured['scheme'] ?? 'http');
        if ($host === '' || ($host === $want && $scheme === (is_https() ? 'https' : 'http'))) {
            return null;
        }
        return site_origin() . ($_SERVER['REQUEST_URI'] ?? '/');
    }

    /**
     * Página pedida por um endereço diferente do canônico (sem a barra no fim, /page/1/...):
     * redireciona (301) para ele, como o WordPress. Só no site público e em GET.
     */
    public static function redirectToCanonical(string $canonical): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $base = site_url();
        if (($method !== 'GET' && $method !== 'HEAD') || str_starts_with(App::$path . '/', '/admin/') || !str_starts_with($canonical, $base . '/')) {
            return;
        }
        $want = substr($canonical, strlen($base));
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = rawurldecode((string) parse_url($uri, PHP_URL_PATH));
        if (App::$basePath !== '' && str_starts_with($path, App::$basePath)) {
            $path = substr($path, strlen(App::$basePath));
        }
        if ($path !== rawurldecode($want)) {
            $query = (string) parse_url($uri, PHP_URL_QUERY);
            redirect($want . ($query !== '' ? '?' . $query : ''), 301);
        }
    }

    /** robots.txt: painel fora, sitemap e as linhas extras de Painel → SEO. */
    public static function robotsTxt(): string
    {
        $text = "User-agent: *\nDisallow: " . url('/admin/') . "\n";
        $extra = trim((string) setting('seo_robots'));
        return $text . ($extra !== '' ? $extra . "\n" : '') . "\nSitemap: " . absolute_url('/sitemap.xml') . "\n";
    }

    /** @usuario do X (Twitter) cadastrado em Redes sociais. */
    private static function twitterUser(): string
    {
        foreach ((array) setting('social', []) as $item) {
            $user = trim((string) ($item['user'] ?? ''));
            if (($item['network'] ?? '') === 'x' && $user !== '') {
                return preg_match('#^https?://(?:www\.)?(?:x|twitter)\.com/([\w]+)#i', $user, $m) ? '@' . $m[1]
                    : (preg_match('#^@?\w+$#', $user) ? '@' . ltrim($user, '@') : '');
            }
        }
        return '';
    }

    /** Dados estruturados no formato que o Google lê: quem publica, o site, a página, a trilha e o post. */
    private static function graph(array $meta, ?array $size): array
    {
        $home = absolute_url('/');
        $language = (string) setting('language');
        $person = setting('seo_entity') === 'person';
        $logo = (string) (setting('seo_entity_logo') ?: setting('logo'));
        $publisher = array_filter([
            '@type' => $person ? 'Person' : 'Organization',
            '@id' => $home . ($person ? '#person' : '#organization'),
            'name' => setting('seo_entity_name') ?: setting('title'),
            'url' => $home,
            ($person ? 'image' : 'logo') => $logo !== '' ? full_url($logo) : null,
            'sameAs' => array_values(array_filter(array_column(Social::links(), 'url'), fn($u) => (bool) preg_match('#^https?://#i', $u))) ?: null,
        ]);
        $graph = [$publisher, array_filter([
            '@type' => 'WebSite',
            '@id' => $home . '#website',
            'url' => $home,
            'name' => setting('title'),
            'description' => self::siteDescription() ?: null,
            'publisher' => ['@id' => $publisher['@id']],
            'inLanguage' => $language,
        ])];

        $url = (string) $meta['canonical'];
        if ($url === '' || !$meta['schema']) {
            return $graph;
        }
        $page = array_filter([
            '@type' => $meta['schema'] === 'collection' ? 'CollectionPage' : 'WebPage',
            '@id' => $url . '#webpage',
            'url' => $url,
            'name' => $meta['title'],
            'description' => $meta['description'] ?: null,
            'isPartOf' => ['@id' => $home . '#website'],
            'inLanguage' => $language,
            'datePublished' => $meta['published'] ?: null,
            'dateModified' => $meta['modified'] ?: null,
        ]);
        $hasImage = $meta['image'] !== '' && $meta['schema'] !== 'collection';
        if ($hasImage) {
            $graph[] = array_filter([
                '@type' => 'ImageObject',
                '@id' => $url . '#primaryimage',
                'url' => full_url($meta['image']),
                'width' => $size[0] ?? null,
                'height' => $size[1] ?? null,
                'caption' => $meta['image_alt'] ?: null,
            ]);
            $page['primaryImageOfPage'] = ['@id' => $url . '#primaryimage'];
        }
        if ($meta['breadcrumb']) {
            $items = [];
            foreach (array_values($meta['breadcrumb']) as $i => [$name, $link]) {
                $items[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => $link];
            }
            $graph[] = ['@type' => 'BreadcrumbList', '@id' => $url . '#breadcrumb', 'itemListElement' => $items];
            $page['breadcrumb'] = ['@id' => $url . '#breadcrumb'];
        }
        $graph[] = $page;

        if ($meta['schema'] === 'post') {
            $graph[] = array_filter([
                '@type' => 'BlogPosting',
                '@id' => $url . '#article',
                'isPartOf' => ['@id' => $url . '#webpage'],
                'mainEntityOfPage' => ['@id' => $url . '#webpage'],
                'headline' => $meta['headline'] ?: $meta['title'],
                'description' => $meta['description'] ?: null,
                'datePublished' => $meta['published'] ?: null,
                'dateModified' => $meta['modified'] ?: null,
                'author' => $meta['author'] ? array_filter(['@type' => 'Person'] + $meta['author']) : null,
                'publisher' => ['@id' => $publisher['@id']],
                'image' => $hasImage ? ['@id' => $url . '#primaryimage'] : null,
                'articleSection' => $meta['section'] ?: null,
                'keywords' => $meta['tags'] ? implode(', ', $meta['tags']) : null,
                'inLanguage' => $language,
            ]);
        }
        return $graph;
    }
}
