<?php
/**
 * Arquivos enviados (imagens, PDFs...). Ficam em public/uploads/AAAA/MM/.
 * Não existe "banco de mídia": a biblioteca é a própria pasta (mais a cópia
 * de wp-content/uploads, quando o blog veio do WordPress).
 */
final class Media
{
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'pdf', 'mp3', 'm4a', 'ogg', 'mp4', 'webm', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'txt', 'csv'];
    public const IMAGES = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    /** Larguras das versões menores usadas no srcset (veja thumb()). A última é a maior que o site entrega. */
    public const THUMB_WIDTHS = [400, 800, 1200, 1600, 2000];

    /** Formatos que o PHP (GD) redimensiona. GIF fica de fora para não perder a animação. */
    private const READERS = ['jpg' => 'imagecreatefromjpeg', 'jpeg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng', 'webp' => 'imagecreatefromwebp'];

    /** Pasta onde os envios novos são salvos. */
    public static function dir(): string
    {
        return PUBLIC_DIR . '/uploads';
    }

    /** Pastas que aparecem na biblioteca. */
    public static function roots(): array
    {
        return [self::dir(), PUBLIC_DIR . '/wp-content/uploads'];
    }

    /* ------------------------------------------------- Versões menores (srcset) */

    /** Largura e altura de uma imagem do site (/uploads/... ou /wp-content/uploads/...), ou null. */
    public static function size(string $url): ?array
    {
        static $cache = [];
        $relative = self::relative($url);
        if ($relative === null) {
            return null;
        }
        if (!array_key_exists($relative, $cache)) {
            $info = @getimagesize(PUBLIC_DIR . '/' . $relative);
            $cache[$relative] = $info ? [(int) $info[0], (int) $info[1]] : null;
        }
        return $cache[$relative];
    }

    /**
     * srcset com as versões menores de uma imagem do site. Com WebP, a largura original também
     * vai em WebP (bem mais leve que PNG e JPEG); imagens com mais de 2000px param em 2000.
     * '' quando a imagem é de fora ou o servidor não consegue gerar as versões.
     */
    public static function srcset(string $url): string
    {
        $relative = self::relative($url);
        $size = $relative !== null && isset(self::READERS[self::ext($relative)]) && self::canThumb() ? self::size($url) : null;
        if (!$size) {
            return '';
        }
        $thumb = fn(int $width) => url("/thumbs/$width/" . self::encode($relative) . (self::webp() ? '.webp' : '')) . " {$width}w";
        $parts = array_map($thumb, array_filter(self::THUMB_WIDTHS, fn($width) => $width < $size[0]));
        if ($size[0] <= max(self::THUMB_WIDTHS)) {
            $parts[] = self::webp() && self::ext($relative) !== 'webp' ? $thumb($size[0]) : url('/' . self::encode($relative)) . " {$size[0]}w";
        }
        return count($parts) > 1 || (self::webp() && self::ext($relative) !== 'webp') ? implode(', ', $parts) : '';
    }

    /**
     * Imagens do texto de um post: carregamento só quando chegam perto da tela (loading="lazy")
     * e versões menores (srcset). $eagerFirst: a primeira carrega já (post sem capa, ela é o destaque).
     * $sizes: largura em que as imagens aparecem, quando o layout não é a coluna de texto do post.
     */
    public static function optimizeHtml(string $html, bool $eagerFirst = false, string $sizes = ''): string
    {
        $first = true;
        return (string) preg_replace_callback('/<img\b[^>]*>/i', function ($m) use (&$first, $eagerFirst, $sizes) {
            $tag = $m[0];
            $attrs = [];
            if (!($first && $eagerFirst) && !preg_match('/\sloading=/i', $tag)) {
                $attrs[] = 'loading="lazy"';
            }
            $first = false;
            if (!preg_match('/\sdecoding=/i', $tag)) {
                $attrs[] = 'decoding="async"';
            }
            if (!preg_match('/\ssrcset=/i', $tag) && preg_match('/\ssrc=(["\'])(.*?)\1/i', $tag, $src)
                && ($srcset = self::srcset(html_entity_decode($src[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'))) !== '') {
                // A coluna de texto tem uns 800px; imagens com largura menor no HTML aparecem menores.
                $width = preg_match('/\swidth=["\']?(\d+)/i', $tag, $w) ? min((int) $w[1], 800) : 800;
                $attrs[] = 'srcset="' . e($srcset) . '" sizes="' . e($sizes ?: "(max-width: {$width}px) 100vw, {$width}px") . '"';
            }
            return $attrs ? (string) preg_replace('#\s*/?>$#', ' ' . implode(' ', $attrs) . '>', $tag, 1) : $tag;
        }, $html);
    }

    /**
     * /thumbs/{largura}/{caminho da imagem}[.webp]: na primeira visita gera a versão menor e
     * grava em public/thumbs/, de onde o servidor passa a entregá-la direto, sem o PHP.
     * Sai em WebP (bem mais leve) quando o PHP consegue gerar; senão, no formato original.
     * Larguras aceitas: as de THUMB_WIDTHS menores que a imagem e, em WebP, a largura da própria imagem.
     */
    public static function thumb(int $width, string $path)
    {
        $source = self::webp() ? (str_ends_with($path, '.webp') ? substr($path, 0, -5) : '') : $path;
        $relative = self::checked($source);
        $file = $relative !== null ? PUBLIC_DIR . '/' . $relative : '';
        $size = isset(self::READERS[self::ext($source)]) && is_file($file) ? @getimagesize($file) : false;
        if (!$size || (!in_array($width, self::THUMB_WIDTHS, true) && $width !== $size[0])) {
            return false;
        }
        $format = self::webp() ? 'webp' : str_replace('jpeg', 'jpg', self::ext($source));
        if ($width > $size[0] || ($width === $size[0] && ($format !== 'webp' || $width > max(self::THUMB_WIDTHS)))) {
            redirect('/' . self::encode($relative)); // não há versão menor que faça sentido: a original serve
        }
        $target = self::thumbsDir() . "/$width/$relative" . ($format === 'webp' ? '.webp' : '');
        if (!is_file($target)) {
            @set_time_limit(60);
            $dir = dirname($target);
            $tmp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
            $ok = (is_dir($dir) || @mkdir($dir, 0775, true))
                && self::resize($file, $tmp, $width, $format)
                && (@rename($tmp, $target) || is_file($target));
            @unlink($tmp);
            if (!$ok) {
                redirect('/' . self::encode($relative)); // não deu para gerar: a original serve
            }
            self::protectThumbs();
        }
        header('Content-Type: image/' . ($format === 'jpg' ? 'jpeg' : $format));
        header('Content-Length: ' . filesize($target));
        header('Cache-Control: public, max-age=2592000');
        readfile($target);
        return true;
    }

    /** O servidor consegue gerar as versões menores? (GD instalado e public/thumbs/ gravável) */
    public static function canThumb(): bool
    {
        static $can = null;
        $dir = self::thumbsDir();
        return $can ??= function_exists('imagecreatetruecolor') && (is_dir($dir) ? is_writable($dir) : is_writable(PUBLIC_DIR));
    }

    private static function thumbsDir(): string
    {
        return PUBLIC_DIR . '/thumbs';
    }

    private static function webp(): bool
    {
        static $webp = null;
        return $webp ??= function_exists('imagewebp') && !empty(gd_info()['WebP Support']);
    }

    /** As versões menores ganham a mesma proteção de public/uploads (nada ali é executado). */
    private static function protectThumbs(): void
    {
        $source = self::dir() . '/.htaccess';
        if (is_file($source) && !is_file(self::thumbsDir() . '/.htaccess')) {
            @copy($source, self::thumbsDir() . '/.htaccess');
        }
    }

    /** URL de uma imagem do site (caminho ou endereço completo) → caminho dentro de public/, ou null. */
    private static function relative(string $url): ?string
    {
        $origin = site_origin();
        if (str_starts_with($url, $origin . '/')) {
            $url = substr($url, strlen($origin));
        }
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return null;
        }
        $path = rawurldecode((string) parse_url($url, PHP_URL_PATH));
        if (App::$basePath !== '') {
            if (!str_starts_with($path, App::$basePath . '/')) {
                return null;
            }
            $path = substr($path, strlen(App::$basePath));
        }
        return self::checked(ltrim($path, '/'));
    }

    /** Só caminhos dentro das pastas de uploads, sem "..", arquivos ocultos ou barras invertidas. */
    private static function checked(string $path): ?string
    {
        return preg_match('#^(?:uploads|wp-content/uploads)/#', $path) && !preg_match('#(?:^|/)\.|\\\\|\x00#', $path) ? $path : null;
    }

    private static function ext(string $path): string
    {
        return strtolower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /** Codifica cada parte do caminho para usar numa URL (espaços, acentos, vírgulas). */
    private static function encode(string $path): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    /** Maior arquivo aceito, considerando config.php e os limites do PHP. */
    public static function maxBytes(): int
    {
        $limits = array_filter([
            (int) config('max_upload_mb', 20) * 1048576,
            self::serverMaxBytes(),
        ], fn($v) => $v > 0);
        return $limits ? min($limits) : 20 * 1048576;
    }

    /** Maior envio que o PHP aceita (upload_max_filesize e post_max_size). 0 = sem limite. */
    public static function serverMaxBytes(): int
    {
        $limits = array_filter([
            self::iniBytes(ini_get('upload_max_filesize')),
            self::iniBytes(ini_get('post_max_size')),
        ], fn($v) => $v > 0);
        return $limits ? min($limits) : 0;
    }

    /** Salva um arquivo de $_FILES. Devolve os dados do arquivo salvo. */
    public static function upload(array $file): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'arquivo maior que o permitido pelo servidor.',
                UPLOAD_ERR_PARTIAL => 'o envio foi interrompido.',
                UPLOAD_ERR_NO_FILE => 'nenhum arquivo enviado.',
                default => 'falha no envio (código ' . $error . ').',
            });
        }
        if ((int) $file['size'] > self::maxBytes()) {
            throw new RuntimeException('arquivo maior que ' . format_bytes(self::maxBytes()) . '.');
        }

        $original = (string) $file['name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (!in_array($ext, self::EXTENSIONS, true)) {
            throw new RuntimeException("tipo de arquivo não permitido (.$ext).");
        }
        $isImage = in_array($ext, self::IMAGES, true);
        if ($isImage && !self::looksLikeImage($file['tmp_name'])) {
            throw new RuntimeException('o arquivo não é uma imagem válida.');
        }

        $dir = self::dir() . '/' . date('Y/m');
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('não foi possível criar a pasta de uploads. Verifique as permissões de public/uploads.');
        }
        $base = trim(substr(slugify(pathinfo($original, PATHINFO_FILENAME)), 0, 80), '-') ?: 'arquivo';
        $target = "$dir/$base.$ext";
        for ($n = 2; file_exists($target); $n++) {
            $target = "$dir/$base-$n.$ext";
        }
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('não foi possível salvar o arquivo.');
        }
        @chmod($target, 0644);
        if ($isImage) {
            self::shrink($target, $ext);
        }
        return self::info($target);
    }

    /** Lista os arquivos, do mais novo para o mais antigo. Devolve [arquivos, total]. */
    public static function list(int $page = 1, int $perPage = 48, string $query = '', bool $imagesOnly = false): array
    {
        $query = normalize_text($query);
        $found = [];
        foreach (self::roots() as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
            foreach ($files as $file) {
                $name = $file->getFilename();
                if (!$file->isFile() || $name[0] === '.') {
                    continue;
                }
                $ext = strtolower($file->getExtension());
                if (!in_array($ext, $imagesOnly ? self::IMAGES : self::EXTENSIONS, true)) {
                    continue;
                }
                if ($query !== '' && !str_contains(normalize_text($name), $query)) {
                    continue;
                }
                $found[] = [$file->getMTime(), $file->getPathname()];
            }
        }
        usort($found, fn($a, $b) => $b[0] <=> $a[0] ?: strcmp($b[1], $a[1]));
        $offset = (max(1, $page) - 1) * $perPage; // vira float se ?page= for enorme
        $slice = $offset < count($found) ? array_slice($found, (int) $offset, $perPage) : [];
        return [array_map(fn($f) => self::info($f[1]), $slice), count($found)];
    }

    /** 'path' é relativo a public/ (ex.: uploads/2024/05/foto.jpg). */
    public static function info(string $absolute): array
    {
        $relative = ltrim(str_replace('\\', '/', substr($absolute, strlen(PUBLIC_DIR))), '/');
        $ext = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        return [
            'name' => basename($absolute),
            'path' => $relative,
            'url' => url('/' . $relative),
            'size' => (int) @filesize($absolute),
            'mtime' => (int) @filemtime($absolute),
            'ext' => $ext,
            'is_image' => in_array($ext, self::IMAGES, true),
        ];
    }

    /** Exclui um arquivo (o 'path' de info(), relativo a public/). Só dentro de roots(). */
    public static function delete(string $relative): bool
    {
        $file = realpath(PUBLIC_DIR . '/' . $relative);
        if (!$file || !is_file($file) || str_starts_with(basename($file), '.')) {
            return false;
        }
        foreach (self::roots() as $dir) {
            $root = realpath($dir);
            if ($root && str_starts_with($file, $root . DIRECTORY_SEPARATOR)) {
                $size = @getimagesize($file);
                foreach ([...self::THUMB_WIDTHS, $size[0] ?? 0] as $width) {
                    $thumb = self::thumbsDir() . "/$width/" . ltrim(str_replace('\\', '/', $relative), '/');
                    @unlink($thumb);
                    @unlink($thumb . '.webp');
                }
                return @unlink($file);
            }
        }
        return false;
    }

    public static function looksLikeImage(string $path): bool
    {
        if (class_exists('finfo')) {
            $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
            return str_starts_with($mime, 'image/') && $mime !== 'image/svg+xml';
        }
        return @getimagesize($path) !== false;
    }

    /** Reduz imagens muito largas (economiza espaço e deixa o site mais rápido). */
    private static function shrink(string $path, string $ext): void
    {
        $max = (int) config('image_max_width', 2000);
        $size = @getimagesize($path);
        if ($max > 0 && $size && $size[0] > $max) {
            self::resize($path, $path, $max, $ext);
        }
    }

    /**
     * Grava em $to a imagem $from com a largura dada, mantendo a proporção.
     * $format: jpg, png ou webp. Devolve false se não deu (formato, memória...).
     */
    private static function resize(string $from, string $to, int $width, string $format): bool
    {
        $reader = self::READERS[self::ext($from)] ?? '';
        $writer = ['jpg' => 'imagejpeg', 'png' => 'imagepng', 'webp' => 'imagewebp'][$format] ?? '';
        $size = @getimagesize($from);
        if (!$size || !function_exists($reader) || !function_exists($writer)) {
            return false;
        }
        [$oldWidth, $oldHeight] = $size;
        $height = max(1, (int) round($oldHeight * $width / $oldWidth));

        // Não tenta se a imagem for grande demais para a memória do PHP.
        $needed = ($oldWidth * $oldHeight + $width * $height) * 5 + 10 * 1048576;
        $limit = self::iniBytes(ini_get('memory_limit'));
        if ($limit > 0 && memory_get_usage() + $needed > $limit) {
            return false;
        }

        $source = @$reader($from);
        if (!$source) {
            return false;
        }
        // Fotos de celular guardam a rotação no EXIF; a cópia sai sem EXIF, então já sai girada.
        $orientation = $size[2] === IMAGETYPE_JPEG && function_exists('exif_read_data') ? (int) (@exif_read_data($from)['Orientation'] ?? 1) : 1;
        if (in_array($orientation, [3, 6, 8], true)) {
            $source = imagerotate($source, [3 => 180, 6 => -90, 8 => 90][$orientation], 0) ?: $source;
            if ($orientation !== 3) {
                $height = max(1, (int) round($oldWidth * $width / $oldHeight));
                [$oldWidth, $oldHeight] = [$oldHeight, $oldWidth];
            }
        }
        $resized = imagecreatetruecolor($width, $height);
        if ($format !== 'jpg') {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
        }
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $width, $height, $oldWidth, $oldHeight);
        return match ($format) {
            'jpg' => imagejpeg($resized, $to, 85),
            'png' => imagepng($resized, $to, 6),
            'webp' => imagewebp($resized, $to, 80),
        };
    }

    private static function iniBytes($value): int
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-1') {
            return 0;
        }
        $number = (float) $value;
        return (int) match (strtolower(substr($value, -1))) {
            'g' => $number * 1073741824,
            'm' => $number * 1048576,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
