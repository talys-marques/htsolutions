<?php
/**
 * Importa a coleção "blogs" de um Strapi v4 (campos: titulo, slug, data, conteudo,
 * resumo, destaque, imagem, categoria).
 *
 * - Mantém slug, data e categoria: com o blog na pasta /blog/, o /blog/{slug} do site
 *   antigo cai em /{slug}/ daqui.
 * - Baixa a capa e as imagens e vídeos do texto para public/uploads/strapi/. Imagens mais
 *   largas que image_max_width (config.php) são reduzidas, como no Media::upload().
 * - Limpa o HTML colado do Google Docs, ChatGPT e YouTube: estilos, classes, data-*,
 *   spans e divs vazios. h1 no texto vira h2 (o título já é o h1).
 * - Rodar de novo atualiza os mesmos posts (pelo strapi_id), sem duplicar.
 */
final class StrapiImporter
{
    private const UPLOAD_FOLDER = 'uploads/strapi';

    /** Hosts cujos arquivos são baixados. O IP interno aparece em posts antigos e não responde mais. */
    private const MEDIA_HOSTS = ['htsolutions.blob.core.windows.net', '172.28.0.11'];

    /** Onde os arquivos do IP interno ainda podem existir. */
    private const BLOB_FALLBACK = 'https://htsolutions.blob.core.windows.net/uploads/uploads/';

    private array $report = [];

    /** URL remota => caminho local ('' se o download falhou), para baixar cada arquivo uma vez só. */
    private array $downloaded = [];

    public function __construct(private string $author)
    {
    }

    /**
     * Posts do Strapi a partir de uma pasta com blogs*.json (respostas salvas de /api/blogs)
     * ou da própria API (https://.../api).
     */
    public static function load(string $source): array
    {
        $entries = [];
        if (is_dir($source)) {
            foreach (glob(rtrim($source, '/\\') . '/blogs*.json') ?: [] as $file) {
                $json = json_decode((string) file_get_contents($file), true);
                $entries = array_merge($entries, $json['data'] ?? []);
            }
            if (!$entries) {
                throw new RuntimeException("Nenhum blogs*.json com posts em $source.");
            }
        } elseif (preg_match('#^https?://#', $source)) {
            for ($page = 1, $pages = 1; $page <= $pages; $page++) {
                $url = rtrim($source, '/') . '/blogs?populate=*&sort=data:desc&pagination[pageSize]=100&pagination[page]=' . $page;
                $json = json_decode((string) self::fetch($url), true);
                if (!isset($json['data'])) {
                    throw new RuntimeException("Resposta inesperada de $url");
                }
                $entries = array_merge($entries, $json['data']);
                $pages = (int) ($json['meta']['pagination']['pageCount'] ?? 1);
            }
        } else {
            throw new RuntimeException("Informe uma pasta com blogs*.json ou o endereço da API: $source");
        }
        // O mesmo post em duas páginas salvas: fica um.
        $byId = [];
        foreach ($entries as $entry) {
            $byId[(int) $entry['id']] = $entry;
        }
        return array_values($byId);
    }

    /** Devolve as contagens e a lista de arquivos que não puderam ser baixados. */
    public function import(array $entries): array
    {
        $this->report = ['posts' => 0, 'updated' => 0, 'with_cover' => 0, 'files' => 0, 'missing' => [], 'redirects' => 0];
        @ini_set('memory_limit', '1024M');

        // Os downloads vêm antes do batch, que segura a trava dos dados.
        $posts = [];
        foreach ($entries as $entry) {
            $posts[] = $this->convert((int) $entry['id'], $entry['attributes'] ?? []);
        }

        $redirects = [];
        Posts::batch(function () use ($posts, &$redirects) {
            $imported = [];
            // O índice não guarda o strapi_id (só o wp_id): precisa abrir cada post.
            foreach (Posts::items() as $item) {
                $strapiId = Posts::find($item['id'])['strapi_id'] ?? null;
                if ($strapiId !== null) {
                    $imported[$strapiId] = $item['id'];
                }
            }
            foreach ($posts as $data) {
                $existing = $imported[$data['strapi_id']] ?? null;
                $data['id'] = $existing ?? (Posts::isFreeId($data['strapi_id']) ? $data['strapi_id'] : 0);
                $oldSlug = $data['old_slug'];
                unset($data['old_slug']);
                $post = Posts::save($data, false);
                $this->report['posts']++;
                if ($existing) {
                    $this->report['updated']++;
                }
                // Só diferença de maiúsculas (blog-Tecnologias-...) não vira redirecionamento:
                // Redirects compara em minúsculas.
                if (Redirects::normalize('/' . $oldSlug) !== Redirects::normalize('/' . $post['slug'])) {
                    $redirects['/' . $oldSlug] = '/' . $post['slug'] . '/';
                }
            }
        });

        // /blog/categoria/cases do site antigo → /blog/category/cases/
        foreach (Posts::terms('categories', false) as $slug => $name) {
            $redirects["/categoria/$slug"] = "/category/$slug/";
        }
        Redirects::addMany($redirects);
        $this->report['redirects'] = count($redirects);
        return $this->report;
    }

    private function convert(int $strapiId, array $a): array
    {
        $title = trim((string) ($a['titulo'] ?? ''));
        $category = trim((string) ($a['categoria']['data']['attributes']['nome'] ?? ''));
        // O Strapi guarda só o dia; meio-dia evita que o fuso mude a data.
        $date = !empty($a['data']) ? date('c', strtotime($a['data'] . ' 12:00:00')) : '';
        $published = !empty($a['publishedAt']);

        $cover = '';
        $coverAlt = '';
        $image = $a['imagem']['data']['attributes'] ?? null;
        if ($image && !empty($image['url'])) {
            $cover = $this->localize((string) $image['url']);
            $coverAlt = trim((string) ($image['alternativeText'] ?? ''));
            if ($cover !== '') {
                $this->report['with_cover']++;
            }
        }

        return [
            'strapi_id' => $strapiId,
            'old_slug' => (string) ($a['slug'] ?? ''),
            'type' => 'post',
            'status' => $published ? 'published' : 'draft',
            'title' => $title !== '' ? $title : '(sem título)',
            'slug' => (string) ($a['slug'] ?? ''),
            'content' => $this->cleanContent((string) ($a['conteudo'] ?? '')),
            'excerpt' => trim(plain_text((string) ($a['resumo'] ?? ''))),
            'cover' => $cover,
            'cover_alt' => $coverAlt,
            'categories' => $category !== '' ? [$category] : [],
            // A home antiga mostrava os posts com "destaque"; vira tag para o tema poder usar.
            'tags' => !empty($a['destaque']) ? ['Destaque'] : [],
            'author' => $this->author,
            'published_at' => $published ? $date : '',
            'created_at' => $date ?: (string) ($a['createdAt'] ?? ''),
            'updated_at' => (string) ($a['updatedAt'] ?? ''),
        ];
    }

    private function cleanContent(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $html = Html::clean($html);

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><html><body>' . $html . '</body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $body = $doc->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        $xpath = new DOMXPath($doc);
        foreach (iterator_to_array($xpath->query('//*', $body)) as $el) {
            /** @var DOMElement $el */
            $tag = strtolower($el->nodeName);
            foreach (iterator_to_array($el->attributes) as $attr) {
                $name = strtolower($attr->nodeName);
                // Largura de imagens e vídeos é do conteúdo; o resto é formatação do app de origem.
                $keep = match ($tag) {
                    'a' => in_array($name, ['href', 'target', 'rel'], true),
                    'img' => in_array($name, ['src', 'alt', 'width', 'height'], true),
                    'iframe' => in_array($name, ['src', 'width', 'height', 'allow', 'allowfullscreen', 'title'], true),
                    'video' => in_array($name, ['src', 'controls', 'width', 'height', 'poster'], true),
                    'source' => in_array($name, ['src', 'type'], true),
                    'ol' => in_array($name, ['start', 'type'], true),
                    'td', 'th' => in_array($name, ['colspan', 'rowspan'], true),
                    default => false,
                };
                if (!$keep) {
                    $el->removeAttribute($attr->nodeName);
                }
            }
            if (in_array($tag, ['img', 'source', 'video'], true) && $el->hasAttribute('src')) {
                $src = $this->localize($el->getAttribute('src'));
                if ($src === '' && $this->isMedia($el->getAttribute('src'))) {
                    $el->parentNode->removeChild($el);
                    continue;
                }
                if ($src !== '') {
                    $el->setAttribute('src', $src);
                }
            }
            if ($tag === 'h1') {
                $this->rename($el, 'h2');
            }
        }

        // Span e div sem atributos só embrulham texto; blocos vazios são sobra da colagem.
        foreach (array_reverse(iterator_to_array($xpath->query('//span | //div', $body))) as $el) {
            $this->unwrap($el);
        }
        foreach (array_reverse(iterator_to_array($xpath->query('//p | //h2 | //h3 | //h4 | //h5 | //h6 | //li | //strong | //i | //em | //u | //figure', $body))) as $el) {
            if (trim(str_replace("\xC2\xA0", ' ', $el->textContent)) === ''
                && !$xpath->query('.//img | .//iframe | .//video', $el)->length) {
                $el->parentNode->removeChild($el);
            }
        }

        $out = '';
        foreach ($body->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return trim((string) preg_replace('/\n{3,}/', "\n\n", $out));
    }

    private function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }

    private function rename(DOMElement $el, string $tag): void
    {
        $new = $el->ownerDocument->createElement($tag);
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);
    }

    private function isMedia(string $url): bool
    {
        return in_array(strtolower((string) parse_url($url, PHP_URL_HOST)), self::MEDIA_HOSTS, true);
    }

    /**
     * Baixa um arquivo do Strapi para public/uploads/strapi/ e devolve o caminho (/uploads/strapi/x.jpg).
     * Devolve a URL como está se não for do Strapi, e '' se o download falhou.
     */
    private function localize(string $url): string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (!$this->isMedia($url)) {
            return $url;
        }
        if (isset($this->downloaded[$url])) {
            return $this->downloaded[$url];
        }

        $name = basename((string) parse_url($url, PHP_URL_PATH));
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $ext = $ext === 'jpeg' ? 'jpg' : $ext;
        $base = trim(substr(slugify(pathinfo($name, PATHINFO_FILENAME)), 0, 100), '-') ?: 'arquivo';
        $relative = self::UPLOAD_FOLDER . "/$base.$ext";
        $target = PUBLIC_DIR . '/' . $relative;

        if (!in_array($ext, Media::EXTENSIONS, true)) {
            $this->report['missing'][] = "$url (tipo .$ext não aceito)";
            return $this->downloaded[$url] = '';
        }
        if (!is_file($target)) {
            $sources = [$url];
            if (str_contains($url, '172.28.0.11')) {
                $sources = [self::BLOB_FALLBACK . $name];
            }
            $bytes = null;
            foreach ($sources as $source) {
                $bytes = self::fetch($source);
                if ($bytes !== null) {
                    break;
                }
            }
            if ($bytes === null) {
                $this->report['missing'][] = $url;
                return $this->downloaded[$url] = '';
            }
            if (!is_dir(dirname($target))) {
                @mkdir(dirname($target), 0775, true);
            }
            file_put_contents($target, $bytes);
            if (in_array($ext, Media::IMAGES, true)) {
                if (!Media::looksLikeImage($target)) {
                    @unlink($target);
                    $this->report['missing'][] = "$url (não é uma imagem)";
                    return $this->downloaded[$url] = '';
                }
                self::shrink($target, $ext);
            }
            $this->report['files']++;
        }
        return $this->downloaded[$url] = '/' . $relative;
    }

    /** Mesma regra do Media::upload(): imagens mais largas que image_max_width são reduzidas. */
    private static function shrink(string $path, string $ext): void
    {
        $max = (int) config('image_max_width', 2000);
        $size = @getimagesize($path);
        $read = ['jpg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng', 'webp' => 'imagecreatefromwebp'][$ext] ?? null;
        if ($max <= 0 || !$size || $size[0] <= $max || !$read || !function_exists($read)) {
            return;
        }
        $image = @$read($path);
        if (!$image) {
            return;
        }
        $resized = imagescale($image, $max, (int) round($size[1] * $max / $size[0]), IMG_BICUBIC);
        if ($resized) {
            if ($ext === 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagepng($resized, $path, 6);
            } elseif ($ext === 'webp') {
                imagewebp($resized, $path, 82);
            } else {
                imagejpeg($resized, $path, 82);
            }
        }
    }

    /** GET com tempo limite. null em qualquer falha (DNS, 404, tempo esgotado). */
    private static function fetch(string $url): ?string
    {
        // Sem ignore_errors, qualquer 4xx/5xx faz o file_get_contents devolver false.
        $context = stream_context_create(['http' => [
            'timeout' => 60,
            'header' => "User-Agent: blog-system strapi-import\r\n",
        ]]);
        $body = @file_get_contents($url, false, $context);
        return $body !== false && $body !== '' ? $body : null;
    }
}
