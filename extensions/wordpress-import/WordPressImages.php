<?php
/**
 * Baixa do site antigo as imagens que os posts e os textos das categorias usam (/wp-content/uploads/...)
 * para public/wp-content/uploads/, no mesmo caminho. Alternativa a copiar a
 * pasta wp-content/uploads do WordPress por FTP.
 *
 * Só baixa o que falta, então pode ser interrompido e rodado de novo.
 */
final class WordPressImages
{
    /** Caminhos usados nos posts e nos textos das categorias e tags que ainda não existem aqui, em ordem alfabética. */
    public static function missing(): array
    {
        $prefix = '#["\']' . preg_quote(rtrim(url('/'), '/'), '#') . '(/wp-content/uploads/[^"\'?\#]+)#';
        $paths = [];
        $scan = function (string $html) use ($prefix, &$paths) {
            if (!preg_match_all($prefix, $html, $m)) {
                return;
            }
            foreach ($m[1] as $path) {
                $path = rawurldecode(html_entity_decode($path, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if (self::valid($path) && !is_file(PUBLIC_DIR . $path)) {
                    $paths[$path] = true;
                }
            }
        };
        foreach (array_keys(Posts::items()) as $id) {
            $post = Posts::find((int) $id);
            if ($post) {
                $scan($post['content'] . ' "' . $post['cover'] . '"');
            }
        }
        foreach (Terms::all() as $terms) {
            foreach ((array) $terms as $term) {
                $scan((string) ($term['description'] ?? ''));
            }
        }
        $paths = array_keys($paths);
        sort($paths, SORT_STRING);
        return $paths;
    }

    /**
     * Baixa $paths de $oldUrl (ex.: https://blog-antigo.com.br). Com $seconds,
     * para quando o tempo acaba. Devolve [baixadas, com erro, último caminho tentado].
     */
    public static function download(string $oldUrl, array $paths, float $seconds = 0): array
    {
        $start = microtime(true);
        $context = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'Mozilla/5.0 (importador de blog)']]);
        $ok = 0;
        $failed = 0;
        $last = '';
        foreach ($paths as $path) {
            if ($seconds > 0 && microtime(true) - $start > $seconds) {
                break;
            }
            $last = $path;
            $url = rtrim($oldUrl, '/') . implode('/', array_map('rawurlencode', explode('/', $path)));
            self::fetch($url, PUBLIC_DIR . $path, $context) ? $ok++ : $failed++;
        }
        return [$ok, $failed, $last];
    }

    private static function fetch(string $url, string $target, $context): bool
    {
        $data = @file_get_contents($url, false, $context, 0, 64 * 1048576);
        $dir = dirname($target);
        if (!$data || (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir))) {
            return false;
        }
        $tmp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
        // Página de erro com status 200, ou algo que não é imagem: descarta.
        if (@file_put_contents($tmp, $data) === false || !Media::looksLikeImage($tmp) || !@rename($tmp, $target)) {
            @unlink($tmp);
            return false;
        }
        return true;
    }

    /** Só imagens dentro de /wp-content/uploads/, sem "..", com nome que o Windows aceita. */
    private static function valid(string $path): bool
    {
        return (bool) preg_match('#^/wp-content/uploads/[^\\\\:*?"<>|\x00-\x1f]+\.(?:' . implode('|', Media::IMAGES) . ')$#i', $path)
            && !str_contains($path, '//')
            && !preg_match('#/\.\.?(/|$)#', $path);
    }
}
