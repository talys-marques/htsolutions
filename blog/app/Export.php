<?php
/**
 * Exportação no formato do WordPress (WXR 1.2), o mesmo arquivo de
 * WordPress → Ferramentas → Exportar. Serve para levar o blog para o
 * WordPress (Ferramentas → Importar → WordPress) ou para outras plataformas
 * que aceitam esse formato.
 *
 * - Posts e páginas, inclusive rascunhos e agendados (a lixeira fica de fora),
 *   com slug, datas, autor, categorias, tags, resumo e título/descrição SEO
 *   (nos campos do Yoast e do Rank Math).
 * - Os ids são mantidos: /?p=123 continua levando ao mesmo post.
 * - Capas e arquivos enviados que aparecem nos posts viram anexos. O importador
 *   do WordPress baixa cada um do endereço deste site e troca os links no
 *   conteúdo, então o site precisa estar no ar na hora de importar.
 * - Configurações, menu e senhas ficam de fora (para isso existe o Backup).
 *
 * O arquivo é escrito aos poucos, um post por vez, sem montar tudo na memória.
 */
final class Export
{
    public const CONTENT = ['all' => 'Todo o conteúdo', 'post' => 'Só os posts', 'page' => 'Só as páginas'];

    /** URL absoluta do arquivo => anexo, na ordem em que aparecem nos posts. */
    private array $attachments = [];
    private int $nextId = 1;

    /** @param resource $out */
    private function __construct(private $out)
    {
    }

    /** Envia o arquivo para download e encerra. $content: chave de CONTENT. */
    public static function send(string $content = 'all'): never
    {
        $name = (slugify((string) setting('title')) ?: 'blog') . '.WordPress.' . date('Y-m-d') . '.xml';
        header('Content-Type: application/xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        self::write($out, $content);
        fclose($out);
        exit;
    }

    /** Escreve o XML em $out (um arquivo aberto com fopen). */
    public static function write($out, string $content = 'all'): void
    {
        (new self($out))->run(isset(self::CONTENT[$content]) ? $content : 'all');
    }

    private function run(string $content): void
    {
        $items = [];
        foreach (Posts::items() as $id => $item) {
            if ($item['status'] !== 'trash' && ($content === 'all' || $item['type'] === $content)) {
                $items[$id] = $item;
            }
        }
        ksort($items);
        // Anexos recebem ids depois do último post, para não colidir.
        $this->nextId = (int) Posts::index()['next_id'];

        $this->head($items);
        foreach (array_keys($items) as $id) {
            $post = Posts::find($id);
            if ($post) {
                $this->post($post);
            }
        }
        foreach ($this->attachments as $url => $file) {
            $this->attachment($url, $file);
        }
        fwrite($this->out, "</channel>\n</rss>\n");
    }

    /** Dados do blog, autores, categorias e tags. */
    private function head(array $items): void
    {
        $authors = [];
        $categories = [];
        $tags = [];
        foreach ($items as $item) {
            if ($item['author'] !== '') {
                $authors[$item['author']] = true;
            }
            foreach ($item['categories'] as $name) {
                $categories[slugify($name)] ??= $name;
            }
            foreach ($item['tags'] as $name) {
                $tags[slugify($name)] ??= $name;
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<!-- Exportação no formato do WordPress (WXR), gerada em ' . date('d/m/Y H:i') . ".\n"
            . "     Para importar no WordPress: Ferramentas → Importar → WordPress. -->\n"
            . '<rss version="2.0"' . "\n"
            . "\txmlns:excerpt=\"http://wordpress.org/export/1.2/excerpt/\"\n"
            . "\txmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n"
            . "\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n"
            . "\txmlns:wp=\"http://wordpress.org/export/1.2/\"\n>\n<channel>\n"
            . "\t<title>" . self::x(setting('title')) . "</title>\n"
            . "\t<link>" . self::x(site_url()) . "</link>\n"
            . "\t<description>" . self::x(setting('tagline') ?: setting('description')) . "</description>\n"
            . "\t<pubDate>" . date(DATE_RSS) . "</pubDate>\n"
            . "\t<language>" . self::x(setting('language')) . "</language>\n"
            . "\t<wp:wxr_version>1.2</wp:wxr_version>\n"
            . "\t<wp:base_site_url>" . self::x(site_url()) . "</wp:base_site_url>\n"
            . "\t<wp:base_blog_url>" . self::x(site_url()) . "</wp:base_blog_url>\n\n";

        $n = 0;
        foreach (array_keys($authors) as $login) {
            $user = Auth::find((string) $login);
            $xml .= "\t<wp:author><wp:author_id>" . ++$n . '</wp:author_id>'
                . '<wp:author_login>' . self::cdata((string) $login) . '</wp:author_login>'
                . '<wp:author_email>' . self::cdata($user['email'] ?? '') . '</wp:author_email>'
                . '<wp:author_display_name>' . self::cdata($user['name'] ?? (string) $login) . '</wp:author_display_name>'
                . '<wp:author_first_name><![CDATA[]]></wp:author_first_name>'
                . "<wp:author_last_name><![CDATA[]]></wp:author_last_name></wp:author>\n";
        }
        $n = 0;
        foreach ($categories as $slug => $name) {
            $xml .= "\t<wp:category><wp:term_id>" . ++$n . '</wp:term_id>'
                . '<wp:category_nicename>' . self::cdata((string) $slug) . '</wp:category_nicename>'
                . '<wp:category_parent><![CDATA[]]></wp:category_parent>'
                . '<wp:cat_name>' . self::cdata($name) . "</wp:cat_name></wp:category>\n";
        }
        foreach ($tags as $slug => $name) {
            $xml .= "\t<wp:tag><wp:term_id>" . ++$n . '</wp:term_id>'
                . '<wp:tag_slug>' . self::cdata((string) $slug) . '</wp:tag_slug>'
                . '<wp:tag_name>' . self::cdata($name) . "</wp:tag_name></wp:tag>\n";
        }
        fwrite($this->out, $xml . "\n");
    }

    private function post(array $post): void
    {
        $published = $post['published_at'] !== '' ? (int) strtotime($post['published_at']) : 0;
        $date = $published ?: ((int) strtotime($post['created_at'] ?: $post['updated_at']) ?: time());
        $modified = (int) strtotime($post['updated_at']) ?: $date;

        $thumbnail = $post['cover'] !== '' ? $this->register($post['cover'], $post, true) : null;
        preg_match_all('#\s(?:src|href)=["\']([^"\']+)["\']#i', $post['content'], $m);
        foreach ($m[1] as $url) {
            $this->register(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $post);
        }

        $terms = [];
        foreach ($post['categories'] as $name) {
            $terms[] = ['category', $name];
        }
        foreach ($post['tags'] as $name) {
            $terms[] = ['post_tag', $name];
        }

        // Aqui o título SEO não leva o nome do site (ele é somado na página); no Yoast e no Rank Math, leva.
        $seoTitle = $post['seo_title'];
        $meta = array_filter([
            '_thumbnail_id' => (string) $thumbnail,
            '_yoast_wpseo_title' => $seoTitle !== '' ? $seoTitle . ' %%sep%% %%sitename%%' : '',
            '_yoast_wpseo_metadesc' => $post['seo_description'],
            'rank_math_title' => $seoTitle !== '' ? $seoTitle . ' %sep% %sitename%' : '',
            'rank_math_description' => $post['seo_description'],
        ], fn($value) => $value !== '');

        $this->item([
            'title' => $post['title'],
            'link' => absolute_url('/' . $post['slug'] . '/'),
            'author' => $post['author'],
            'guid' => absolute_url('/?p=' . $post['id']),
            // Links e imagens com o endereço completo: é assim que o WordPress os encontra para trocar.
            'content' => absolutize_urls($post['content']),
            'excerpt' => $post['excerpt'],
            'id' => $post['id'],
            'date' => $date,
            // Rascunho sem data definida: o WordPress usa a data de quando for publicado.
            'date_gmt' => $published ? gmdate('Y-m-d H:i:s', $published) : '0000-00-00 00:00:00',
            'modified' => $modified,
            'slug' => $post['slug'],
            'status' => match (true) {
                $post['status'] === 'draft' => 'draft',
                $published > time() => 'future',
                default => 'publish',
            },
            'parent' => 0,
            'type' => $post['type'],
            'terms' => $terms,
            'meta' => $meta,
        ]);
    }

    private function attachment(string $url, array $file): void
    {
        $this->item([
            'title' => pathinfo(rawurldecode((string) parse_url($url, PHP_URL_PATH)), PATHINFO_FILENAME),
            'link' => $url,
            'author' => $file['author'],
            'guid' => $url,
            'content' => '',
            'excerpt' => '',
            'id' => $file['id'],
            'date' => $file['date'],
            'date_gmt' => gmdate('Y-m-d H:i:s', $file['date']),
            'modified' => $file['date'],
            'slug' => '',
            'status' => 'inherit',
            'parent' => $file['parent'],
            'type' => 'attachment',
            'attachment_url' => $url,
            'terms' => [],
            // O WordPress usa a pasta AAAA/MM daqui para guardar o arquivo no mesmo lugar.
            'meta' => $file['path'] !== '' ? ['_wp_attached_file' => $file['path']] : [],
        ]);
    }

    private function item(array $f): void
    {
        $xml = "\t<item>\n"
            . "\t\t<title>" . self::cdata($f['title']) . "</title>\n"
            . "\t\t<link>" . self::x($f['link']) . "</link>\n"
            . "\t\t<pubDate>" . date(DATE_RSS, $f['date']) . "</pubDate>\n"
            . "\t\t<dc:creator>" . self::cdata($f['author']) . "</dc:creator>\n"
            . "\t\t<guid isPermaLink=\"false\">" . self::x($f['guid']) . "</guid>\n"
            . "\t\t<description></description>\n"
            . "\t\t<content:encoded>" . self::cdata($f['content']) . "</content:encoded>\n"
            . "\t\t<excerpt:encoded>" . self::cdata($f['excerpt']) . "</excerpt:encoded>\n"
            . "\t\t<wp:post_id>" . (int) $f['id'] . "</wp:post_id>\n"
            . "\t\t<wp:post_date>" . self::cdata(date('Y-m-d H:i:s', $f['date'])) . "</wp:post_date>\n"
            . "\t\t<wp:post_date_gmt>" . self::cdata($f['date_gmt']) . "</wp:post_date_gmt>\n"
            . "\t\t<wp:post_modified>" . self::cdata(date('Y-m-d H:i:s', $f['modified'])) . "</wp:post_modified>\n"
            . "\t\t<wp:post_modified_gmt>" . self::cdata(gmdate('Y-m-d H:i:s', $f['modified'])) . "</wp:post_modified_gmt>\n"
            . "\t\t<wp:comment_status><![CDATA[closed]]></wp:comment_status>\n"
            . "\t\t<wp:ping_status><![CDATA[closed]]></wp:ping_status>\n"
            . "\t\t<wp:post_name>" . self::cdata($f['slug']) . "</wp:post_name>\n"
            . "\t\t<wp:status>" . self::cdata($f['status']) . "</wp:status>\n"
            . "\t\t<wp:post_parent>" . (int) $f['parent'] . "</wp:post_parent>\n"
            . "\t\t<wp:menu_order>0</wp:menu_order>\n"
            . "\t\t<wp:post_type>" . self::cdata($f['type']) . "</wp:post_type>\n"
            . "\t\t<wp:post_password><![CDATA[]]></wp:post_password>\n"
            . "\t\t<wp:is_sticky>0</wp:is_sticky>\n";
        if (isset($f['attachment_url'])) {
            $xml .= "\t\t<wp:attachment_url>" . self::cdata($f['attachment_url']) . "</wp:attachment_url>\n";
        }
        foreach ($f['terms'] as [$domain, $name]) {
            $xml .= "\t\t<category domain=\"$domain\" nicename=\"" . self::x(slugify($name)) . '">' . self::cdata($name) . "</category>\n";
        }
        foreach ($f['meta'] as $key => $value) {
            $xml .= "\t\t<wp:postmeta>\n"
                . "\t\t\t<wp:meta_key>" . self::cdata((string) $key) . "</wp:meta_key>\n"
                . "\t\t\t<wp:meta_value>" . self::cdata((string) $value) . "</wp:meta_value>\n"
                . "\t\t</wp:postmeta>\n";
        }
        fwrite($this->out, $xml . "\t</item>\n");
    }

    /**
     * Guarda um arquivo do post para virar anexo e devolve o id dele.
     * Entram arquivos enviados que existem aqui e, se $cover, capas de outros sites
     * (o WordPress baixa e usa como imagem destacada).
     */
    private function register(string $url, array $post, bool $cover = false): ?int
    {
        $path = self::uploadedFile($url);
        if ($path === null && !($cover && preg_match('#^https?://#i', $url))) {
            return null;
        }
        $url = full_url($url);
        $this->attachments[$url] ??= [
            'id' => $this->nextId++,
            'parent' => $post['id'],
            'author' => $post['author'],
            'date' => (int) strtotime($post['published_at'] ?: ($post['created_at'] ?: $post['updated_at'])) ?: time(),
            'path' => (string) $path,
        ];
        return $this->attachments[$url]['id'];
    }

    /**
     * "/uploads/2026/10/foto.jpg" → "2026/10/foto.jpg", se for um arquivo de
     * public/uploads ou public/wp-content/uploads que existe. Senão, null.
     */
    private static function uploadedFile(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (is_string($host) && strcasecmp($host, (string) parse_url(site_url(), PHP_URL_HOST)) !== 0) {
            return null;
        }
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (!str_starts_with($path, App::$basePath . '/')) {
            return null;
        }
        $path = rawurldecode(substr($path, strlen(App::$basePath)));
        if (str_contains($path, '/../') || !in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), Media::EXTENSIONS, true)) {
            return null;
        }
        foreach (Media::roots() as $root) {
            $prefix = substr($root, strlen(PUBLIC_DIR)) . '/';
            if (str_starts_with($path, $prefix) && is_file(PUBLIC_DIR . $path)) {
                return substr($path, strlen($prefix));
            }
        }
        return null;
    }

    private static function x($text): string
    {
        return htmlspecialchars((string) $text, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function cdata(string $text): string
    {
        // Tira caracteres de controle que o XML não aceita (vêm às vezes de textos colados).
        $text = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', mb_scrub($text, 'UTF-8'));
        return '<![CDATA[' . str_replace(']]>', ']]]]><![CDATA[>', $text) . ']]>';
    }
}
