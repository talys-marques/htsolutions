<?php
/**
 * Importa o arquivo de exportação do WordPress (WXR):
 * WordPress → Ferramentas → Exportar → Todo o conteúdo.
 *
 * - Posts e páginas mantêm slug, data, categorias (a principal primeiro), tags,
 *   imagem destacada com o texto alternativo, resumo e o SEO do Yoast / Rank Math
 *   (título, descrição, palavra-chave e "não indexar").
 * - Categorias e tags com slug diferente do daqui ganham redirecionamento, e a
 *   descrição delas vira o texto de apresentação (Painel → SEO → Categorias e tags).
 * - Links internos e imagens viram caminhos relativos (/wp-content/uploads/...):
 *   basta copiar a pasta wp-content/uploads do WordPress para public/wp-content/uploads
 *   ou baixar as imagens do site antigo (WordPressImages).
 * - Conteúdo do editor clássico ganha parágrafos (como o wpautop do WordPress);
 *   comentários de blocos do Gutenberg são removidos; [caption] vira <figure>;
 *   links do YouTube/Vimeo viram players.
 * - Se o endereço antigo de um post era diferente de /slug/, um
 *   redirecionamento 301 é criado.
 * - Rodar de novo atualiza os mesmos posts (pelo id do WordPress), sem duplicar.
 *
 * O arquivo é lido em streaming (XMLReader), então exportações grandes funcionam.
 */
final class WordPressImporter
{
    private const CONTENT_NS = 'http://purl.org/rss/1.0/modules/content/';
    private const DC_NS = 'http://purl.org/dc/elements/1.1/';

    private string $wpNs = 'http://wordpress.org/export/1.2/';
    private string $excerptNs = 'http://wordpress.org/export/1.2/excerpt/';
    private string $oldUrl = '';
    private array $attachments = [];
    private array $redirects = [];
    private array $report = [];

    /** id do anexo => texto alternativo (para a capa). */
    private array $alts = [];

    /** Categorias e tags do WordPress: term_id => [kind (category|tag), slug, name, description]. */
    private array $terms = [];

    /** Nome do site antigo, tirado do fim dos títulos do Yoast ("Post | Blog"). */
    private string $siteName = '';

    /** id do WordPress => id aqui, dos posts já importados antes. */
    private array $imported = [];

    public function __construct(private string $defaultAuthor, private bool $includeDrafts = true)
    {
    }

    /** Devolve um relatório com contagens e avisos. */
    public function import(string $file, string $oldUrl = ''): array
    {
        $this->open($file, $oldUrl);
        self::protectUploadsFolder();

        Posts::batch(function () use ($file) {
            $this->imported = [];
            foreach (Posts::items() as $item) {
                if (isset($item['wp_id'])) {
                    $this->imported[$item['wp_id']] = $item['id'];
                }
            }
            $this->each($file, function (string $name, SimpleXMLElement $el) {
                if ($name === 'item') {
                    $this->importItem($el);
                }
            });
        });

        $this->importTerms();
        Redirects::addMany($this->redirects);
        $this->report['redirects'] = count($this->redirects);
        arsort($this->report['shortcodes']);
        return $this->report;
    }

    /**
     * Para blogs importados antes: atualiza nos posts já importados só o que a importação passou
     * a trazer (título SEO, palavra-chave e "noindex" do Yoast/Rank Math, categoria principal e
     * texto alternativo da capa), além dos redirecionamentos e textos das categorias.
     * Só preenche campos vazios: texto, datas e o que foi editado aqui ficam como estão.
     */
    public function updateSeo(string $file): array
    {
        $this->open($file, '');
        $updated = 0;
        Posts::batch(function () use ($file, &$updated) {
            $ids = [];
            foreach (Posts::items() as $item) {
                if (isset($item['wp_id'])) {
                    $ids[$item['wp_id']] = $item['id'];
                }
            }
            $this->each($file, function (string $name, SimpleXMLElement $el) use ($ids, &$updated) {
                $id = $name === 'item' ? ($ids[(int) $el->children($this->wpNs)->post_id] ?? null) : null;
                $post = $id ? Posts::find($id) : null;
                if (!$post) {
                    return;
                }
                $seo = $this->seoFields($el);
                $data = ['id' => $id];
                foreach (['seo_title', 'seo_description', 'seo_keyword'] as $field) {
                    if ($post[$field] === '' && $seo[$field] !== '') {
                        $data[$field] = $seo[$field];
                    }
                }
                if ($post['cover_alt'] === '' && $seo['cover_alt'] !== '' && $post['cover'] === $seo['cover']) {
                    $data['cover_alt'] = $seo['cover_alt'];
                }
                if ($seo['seo_noindex'] && empty($post['seo_noindex'])) {
                    $data['seo_noindex'] = true;
                }
                $i = $seo['primary'] !== null ? array_search($seo['primary'], $post['categories'], true) : false;
                if ($i) { // 0 = já é a primeira
                    $data['categories'] = $post['categories'];
                    array_unshift($data['categories'], ...array_splice($data['categories'], $i, 1));
                }
                if (count($data) > 1) {
                    Posts::save($data, false);
                    $updated++;
                }
            });
        });
        $this->importTerms();
        Redirects::addMany($this->redirects);
        return ['updated' => $updated, 'redirects' => count($this->redirects)];
    }

    /** Confere o arquivo, descobre os namespaces e faz a primeira passada (anexos, categorias, endereço). */
    private function open(string $file, string $oldUrl): void
    {
        if (!is_file($file)) {
            throw new RuntimeException('Arquivo não encontrado: ' . $file);
        }
        @ini_set('memory_limit', '512M');

        $head = (string) file_get_contents($file, false, null, 0, 16384);
        if (!str_contains($head, '<rss') || !str_contains($head, 'wordpress.org/export')) {
            throw new RuntimeException('Esse arquivo não parece uma exportação do WordPress (WXR).');
        }
        if (preg_match('#xmlns:wp="([^"]+)"#', $head, $m)) {
            $this->wpNs = $m[1];
        }
        if (preg_match('#xmlns:excerpt="([^"]+)"#', $head, $m)) {
            $this->excerptNs = $m[1];
        }

        $this->report = [
            'posts' => 0, 'pages' => 0, 'updated' => 0, 'skipped' => 0, 'redirects' => 0,
            'old_url' => '', 'shortcodes' => [], 'authors' => [], 'with_cover' => 0,
        ];
        $this->attachments = [];
        $this->alts = [];
        $this->terms = [];
        $this->redirects = [];

        $urls = $this->scan($file);
        $this->oldUrl = rtrim($oldUrl !== '' ? $oldUrl : ($urls['blog'] ?? $urls['site'] ?? $urls['link'] ?? ''), '/');
        $this->report['old_url'] = $this->oldUrl;
    }

    /**
     * Categorias e tags com endereço diferente no WordPress (o slug de lá foi escolhido à mão,
     * ex.: /category/casa-construcao/ virou /category/casa-e-construcao/) ganham redirecionamento.
     * As descrições delas (HTML, com links e imagens) viram o texto de apresentação
     * (Painel → SEO → Categorias e tags), só onde ele está vazio ou ainda é o texto puro
     * de uma importação antiga: o que foi editado aqui fica.
     */
    private function importTerms(): void
    {
        $used = ['category' => Posts::terms('categories', false), 'tag' => Posts::terms('tags', false)];
        foreach ($this->terms as $term) {
            $slug = slugify($term['name']);
            if (!isset($used[$term['kind']][$slug])) {
                continue; // nenhum post usa: não há página para onde levar
            }
            if ($term['slug'] !== '' && $term['slug'] !== $slug) {
                $this->redirects["/{$term['kind']}/{$term['slug']}/"] = "/{$term['kind']}/$slug/";
            }
            $html = $term['description'] !== '' ? $this->convertContent($term['description']) : '';
            $current = Terms::get($term['kind'], $slug);
            $untouched = $current['description'] === '' || normalize_text(plain_text($current['description'])) === normalize_text(plain_text($html));
            if ($html !== '' && $html !== $current['description'] && $untouched) {
                Terms::save($term['kind'], $slug, ['description' => $html] + $current);
            }
        }
    }

    /** Endereço do site antigo, que aparece no começo do arquivo de exportação. */
    public static function siteUrl(string $file): string
    {
        $head = (string) @file_get_contents($file, false, null, 0, 65536);
        return preg_match('#<wp:base_(?:blog|site)_url>\s*(?:<!\[CDATA\[)?\s*(https?://[^<\]\s]+)#', $head, $m) ? rtrim($m[1], '/') : '';
    }

    /**
     * Prepara public/wp-content/, onde vai a cópia de wp-content/uploads do WordPress,
     * com a mesma proteção de public/uploads (nenhum arquivo ali é executado).
     */
    private static function protectUploadsFolder(): void
    {
        $dir = PUBLIC_DIR . '/wp-content';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $source = PUBLIC_DIR . '/uploads/.htaccess';
        // Recopia se a proteção de uploads/ mudou (ex.: atualização do sistema).
        if (is_file($source) && (!is_file($dir . '/.htaccess') || md5_file($source) !== md5_file($dir . '/.htaccess'))) {
            @copy($source, $dir . '/.htaccess');
        }
    }

    /** Primeira passada: anexos (imagens destacadas e textos alternativos), categorias, tags e o endereço do site. */
    private function scan(string $file): array
    {
        $urls = [];
        $this->each($file, function (string $name, SimpleXMLElement $el, string $ns) use (&$urls) {
            if ($name === 'item') {
                $wp = $el->children($this->wpNs);
                if ((string) $wp->post_type === 'attachment') {
                    $id = (int) $wp->post_id;
                    $this->attachments[$id] = (string) $wp->attachment_url;
                    foreach ($wp->postmeta as $meta) {
                        if ((string) $meta->meta_key === '_wp_attachment_image_alt') {
                            $this->alts[$id] = trim((string) $meta->meta_value);
                        }
                    }
                }
            } elseif ($ns === $this->wpNs && ($name === 'category' || $name === 'tag')) {
                $wp = $el->children($this->wpNs);
                $isCategory = $name === 'category';
                $this->terms[(int) $wp->term_id] = [
                    'kind' => $name,
                    'slug' => rawurldecode((string) ($isCategory ? $wp->category_nicename : $wp->tag_slug)),
                    'name' => trim(html_entity_decode((string) ($isCategory ? $wp->cat_name : $wp->tag_name), ENT_QUOTES | ENT_HTML5, 'UTF-8')),
                    'description' => trim((string) ($isCategory ? $wp->category_description : $wp->tag_description)),
                ];
            } elseif ($ns === '' && $name === 'title') {
                $this->siteName = trim(html_entity_decode((string) $el, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            } elseif ($ns === $this->wpNs && $name === 'base_blog_url') {
                $urls['blog'] = trim((string) $el);
            } elseif ($ns === $this->wpNs && $name === 'base_site_url') {
                $urls['site'] = trim((string) $el);
            } elseif ($ns === '' && $name === 'link') {
                $urls['link'] = trim((string) $el);
            }
        });
        return array_filter($urls);
    }

    /** Percorre os elementos filhos de <channel> sem carregar o arquivo inteiro na memória. */
    private function each(string $file, callable $fn): void
    {
        $reader = new XMLReader();
        if (!@$reader->open($file, 'UTF-8', LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_NOCDATA)) {
            throw new RuntimeException('Não foi possível abrir o arquivo XML.');
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $ok = @$reader->read();
            while ($ok) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->depth === 2) {
                    $node = $reader->expand(new DOMDocument());
                    if ($node) {
                        $fn($reader->localName, simplexml_import_dom($node), (string) $reader->namespaceURI);
                    }
                    $ok = @$reader->next();
                    continue;
                }
                $ok = @$reader->read();
            }
            $error = libxml_get_last_error();
            if ($error && $error->level === LIBXML_ERR_FATAL) {
                throw new RuntimeException('XML inválido na linha ' . $error->line . ': ' . trim($error->message));
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $reader->close();
        }
    }

    private function importItem(SimpleXMLElement $item): void
    {
        $wp = $item->children($this->wpNs);
        $type = (string) $wp->post_type;
        if (!in_array($type, ['post', 'page'], true)) {
            return;
        }
        $status = match ((string) $wp->status) {
            'publish', 'future' => 'published',
            'draft', 'pending', 'private' => 'draft',
            default => null, // lixeira, auto-draft, inherit...
        };
        if ($status === null || ($status === 'draft' && !$this->includeDrafts)) {
            $this->report['skipped']++;
            return;
        }

        $wpId = (int) $wp->post_id;
        $title = trim(html_entity_decode((string) $item->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        // Rascunhos não têm data de publicação: ela é definida quando forem publicados.
        $date = $this->date((string) $wp->post_date_gmt, $status === 'draft' ? '' : (string) $wp->post_date);
        $modified = $this->date((string) $wp->post_modified_gmt, (string) $wp->post_modified)
            ?: ($date ?: $this->date('', (string) $wp->post_date));

        $seo = $this->seoFields($item);

        $login = (string) $item->children(self::DC_NS)->creator;
        $author = $login !== '' && Auth::find($login) ? strtolower($login) : $this->defaultAuthor;
        if ($login !== '' && $author === $this->defaultAuthor && strtolower($login) !== $this->defaultAuthor) {
            $this->report['authors'][$login] = true;
        }

        $existing = $this->imported[$wpId] ?? null;
        $id = $existing ?? (Posts::isFreeId($wpId) ? $wpId : 0);

        $post = Posts::save([
            'id' => $id,
            'wp_id' => $wpId,
            'type' => $type,
            'status' => $status,
            'title' => $title !== '' ? $title : '(sem título)',
            'slug' => rawurldecode((string) $wp->post_name),
            'content' => $this->convertContent((string) $item->children(self::CONTENT_NS)->encoded),
            'excerpt' => trim(plain_text((string) $item->children($this->excerptNs)->encoded)),
            'author' => $author,
            'published_at' => $date,
            'created_at' => $date,
            'updated_at' => $modified,
        ] + array_diff_key($seo, ['primary' => 0]), false);

        $this->imported[$wpId] = $post['id'];
        $this->report[$type === 'page' ? 'pages' : 'posts']++;
        if ($existing) {
            $this->report['updated']++;
        }
        if ($seo['cover'] !== '') {
            $this->report['with_cover']++;
        }

        // Endereço antigo diferente do novo (ex.: /2020/05/titulo/) → redirecionamento.
        $oldPath = (string) parse_url((string) $item->link, PHP_URL_PATH);
        $newPath = '/' . $post['slug'] . '/';
        if ($status === 'published' && $oldPath !== '' && !str_contains((string) $item->link, '?')
            && Redirects::normalize($oldPath) !== Redirects::normalize($newPath)) {
            $this->redirects[$oldPath] = $newPath;
        }
    }

    /**
     * Categorias (a principal do Yoast/Rank Math primeiro), tags, capa com o texto alternativo e o
     * SEO do post no Yoast ou no Rank Math: título, descrição, palavra-chave e "não indexar".
     * 'primary' = nome da categoria principal (ou null).
     */
    private function seoFields(SimpleXMLElement $item): array
    {
        $categories = [];
        $tags = [];
        foreach ($item->category as $term) {
            $domain = (string) $term['domain'];
            $name = trim(html_entity_decode((string) $term, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($domain === 'category' && !in_array((string) $term['nicename'], ['uncategorized', 'sem-categoria'], true)) {
                $categories[] = $name;
            } elseif ($domain === 'post_tag') {
                $tags[] = $name;
            }
        }

        $fields = ['cover' => '', 'cover_alt' => '', 'seo_title' => '', 'seo_description' => '', 'seo_keyword' => '', 'seo_noindex' => false];
        $primary = null;
        foreach ($item->children($this->wpNs)->postmeta as $meta) {
            $key = (string) $meta->meta_key;
            $value = trim(html_entity_decode((string) $meta->meta_value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            // Com variáveis (%%title%% no Yoast, %title% no Rank Math) é o padrão do plugin, que aqui já é automático.
            $plain = $value !== '' && !preg_match('/%%?[a-z_]+%%?/i', $value);
            if ($key === '_thumbnail_id' && isset($this->attachments[(int) $value])) {
                $fields['cover'] = $this->localUrl($this->attachments[(int) $value]);
                $fields['cover_alt'] = $this->alts[(int) $value] ?? '';
            } elseif (in_array($key, ['_yoast_wpseo_metadesc', 'rank_math_description'], true) && $plain) {
                $fields['seo_description'] = $value;
            } elseif (in_array($key, ['_yoast_wpseo_title', 'rank_math_title'], true) && $plain) {
                $fields['seo_title'] = $this->withoutSiteName($value);
            } elseif ($key === '_yoast_wpseo_focuskw') {
                $fields['seo_keyword'] = $value;
            } elseif ($key === 'rank_math_focus_keyword') {
                $fields['seo_keyword'] = trim(explode(',', $value)[0]); // o Rank Math guarda várias, separadas por vírgula
            } elseif ($key === '_yoast_wpseo_meta-robots-noindex' || $key === 'rank_math_robots') {
                $fields['seo_noindex'] = $value === '1' || str_contains($value, 'noindex');
            } elseif (in_array($key, ['_yoast_wpseo_primary_category', 'rank_math_primary_category'], true)) {
                $primary = $this->terms[(int) $value]['name'] ?? null;
            }
        }
        $i = $primary !== null ? array_search($primary, $categories, true) : false;
        if ($i) {
            array_unshift($categories, ...array_splice($categories, $i, 1));
        }
        return ['categories' => $categories, 'tags' => $tags, 'primary' => $primary] + $fields;
    }

    /** "Título | Blog da taQi" → "Título": aqui o nome do blog é acrescentado sozinho (Seo::title()). */
    private function withoutSiteName(string $title): string
    {
        foreach (array_unique(array_filter([$this->siteName, (string) setting('title')])) as $name) {
            $title = (string) preg_replace('/\s*[|\-–—·•:]\s*' . preg_quote($name, '/') . '\s*$/iu', '', $title);
        }
        return trim($title);
    }

    /** Converte o HTML do WordPress para HTML limpo. */
    public function convertContent(string $html): string
    {
        $isBlocks = str_contains($html, '<!-- wp:');
        $html = (string) preg_replace('/<!--\s*\/?wp:.*?-->/s', '', $html);
        $html = $this->captions($html);
        $html = $this->embeds($html);
        if (!$isBlocks) {
            $html = self::autop($html);
        }
        $html = $this->rewriteUrls($html);

        if (preg_match_all('/\[([a-z][\w-]*)(?:\s[^\]]*)?\]/i', $html, $m)) {
            foreach ($m[1] as $shortcode) {
                $this->report['shortcodes'][$shortcode] = ($this->report['shortcodes'][$shortcode] ?? 0) + 1;
            }
        }
        return trim((string) preg_replace("/\n{3,}/", "\n\n", $html));
    }

    /**
     * Versão simplificada do wpautop(): blocos separados por linha em branco
     * viram <p>, quebras simples viram <br>.
     */
    public static function autop(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        if (trim($text) === '') {
            return '';
        }
        $protected = [];
        $text = (string) preg_replace_callback('#<(pre|script|style)\b.*?</\1>#is', function ($m) use (&$protected) {
            $key = '<!--AUTOP' . count($protected) . '-->';
            $protected[$key] = $m[0];
            return "\n\n$key\n\n";
        }, $text);

        $blocks = 'table|thead|tfoot|caption|col|colgroup|tbody|tr|td|th|div|dl|dd|dt|ul|ol|li|pre|form|blockquote|address|math|p|h[1-6]|hr|fieldset|legend|section|article|aside|header|footer|nav|figure|figcaption|details|summary|iframe';
        $text = (string) preg_replace('#(<(?:' . $blocks . ')[\s/>])#i', "\n\n$1", $text);
        $text = (string) preg_replace('#(</(?:' . $blocks . ')>)#i', "$1\n\n", $text);

        $out = '';
        foreach (preg_split('/\n\s*\n/', $text) as $chunk) {
            $chunk = trim($chunk);
            if ($chunk === '') {
                continue;
            }
            if (preg_match('#^</?(?:' . $blocks . ')[\s/>]#i', $chunk) || str_starts_with($chunk, '<!--AUTOP')) {
                $out .= $chunk . "\n";
            } else {
                $out .= '<p>' . nl2br($chunk, false) . "</p>\n";
            }
        }
        return strtr($out, $protected);
    }

    private function captions(string $html): string
    {
        return (string) preg_replace_callback('#\[caption([^\]]*)\](.*?)\[/caption\]#is', function ($m) {
            $inner = trim($m[2]);
            $caption = '';
            if (preg_match('#^((?:<a\b[^>]*>\s*)?<img\b[^>]*>(?:\s*</a>)?)(.*)$#is', $inner, $parts)) {
                $inner = $parts[1];
                $caption = trim($parts[2]);
            }
            if ($caption === '' && preg_match('/caption="([^"]*)"/', $m[1], $c)) {
                $caption = $c[1];
            }
            return '<figure>' . $inner . ($caption !== '' ? '<figcaption>' . $caption . '</figcaption>' : '') . '</figure>';
        }, $html);
    }

    private function embeds(string $html): string
    {
        $html = (string) preg_replace_callback('#\[embed[^\]]*\](.*?)\[/embed\]#is', fn($m) => video_embed($m[1]) ?? $m[1], $html);
        // URL sozinha numa linha (é assim que o WordPress guarda embeds)
        return (string) preg_replace_callback('#^\s*(https?://[^\s<>"]+)\s*$#m', fn($m) => video_embed($m[1]) ?? $m[0], $html);
    }

    private function rewriteUrls(string $html): string
    {
        if ($this->oldUrl === '') {
            return $html;
        }
        $root = url('/');
        foreach ($this->urlVariants() as $base) {
            $html = str_replace(['="' . $base . '/', "='" . $base . '/'], ['="' . $root, "='" . $root], $html);
            $html = str_replace(['="' . $base . '"', "='" . $base . "'"], ['="' . $root . '"', "='" . $root . "'"], $html);
        }
        return $html;
    }

    private function localUrl(string $url): string
    {
        foreach ($this->urlVariants() as $base) {
            if (str_starts_with($url, $base . '/')) {
                return url(substr($url, strlen($base)));
            }
        }
        return $url;
    }

    /** https://site.com, http://site.com, https://www.site.com, http://www.site.com */
    private function urlVariants(): array
    {
        if ($this->oldUrl === '') {
            return [];
        }
        $rest = (string) preg_replace('#^https?://(www\.)?#i', '', $this->oldUrl);
        $variants = [];
        foreach (['https://', 'http://'] as $scheme) {
            foreach (['', 'www.'] as $www) {
                $variants[] = $scheme . $www . $rest;
            }
        }
        return $variants;
    }

    private function date(string $gmt, string $local): string
    {
        try {
            if ($gmt !== '' && !str_starts_with($gmt, '0000')) {
                return (new DateTime($gmt, new DateTimeZone('UTC')))->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('c');
            }
            if ($local !== '' && !str_starts_with($local, '0000')) {
                return (new DateTime($local))->format('c');
            }
        } catch (Exception) {
        }
        return '';
    }
}
