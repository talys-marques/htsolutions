<?php
/**
 * Backup para download: data/ (posts, usuários, configurações) e,
 * opcionalmente, os arquivos enviados (public/uploads/ e public/wp-content/uploads/).
 * Gera .zip quando a extensão zip existe e .tar.gz caso contrário (os dois
 * abrem em qualquer sistema).
 *
 * Restaurar = descompactar por cima da pasta do blog.
 */
final class Backup
{
    public static function create(bool $withUploads): string
    {
        // Sobras de downloads que falharam (o backup tem os hashes das senhas).
        foreach (glob(Storage::path('cache') . '/backup-*') ?: [] as $old) {
            if (@filemtime($old) < time() - 3600) {
                @unlink($old);
            }
        }
        $files = self::files($withUploads);
        $base = Storage::path('cache') . '/backup-' . date('Y-m-d-His');

        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open("$base.zip", ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Não foi possível criar o arquivo de backup.');
            }
            foreach ($files as $local => $path) {
                $zip->addFile($path, $local);
            }
            $zip->close();
            return "$base.zip";
        }

        self::tarGz($files, "$base.tar.gz");
        return "$base.tar.gz";
    }

    /**
     * .tar.gz gravado direto, sem PharData: ele recusa nomes com mais de 100
     * caracteres, comuns nas imagens do WordPress. Nomes longos ou com acento
     * ganham um cabeçalho pax, que guarda o caminho completo em UTF-8.
     */
    private static function tarGz(array $files, string $target): void
    {
        $out = fopen('compress.zlib://' . $target, 'wb');
        if (!$out) {
            throw new RuntimeException('Não foi possível criar o arquivo de backup.');
        }
        $pad = fn(int $size) => str_repeat("\0", (512 - $size % 512) % 512);
        foreach ($files as $local => $path) {
            $size = filesize($path);
            if (strlen($local) > 100 || preg_match('/[^\x20-\x7e]/', $local)) {
                $record = ' path=' . $local . "\n";
                $length = strlen($record) + strlen((string) strlen($record));
                $record = (strlen($record) + strlen((string) $length)) . $record; // o tamanho conta os próprios dígitos
                fwrite($out, self::tarHeader('PaxHeader', strlen($record), 'x') . $record . $pad(strlen($record)));
            }
            fwrite($out, self::tarHeader($local, $size, '0', (int) filemtime($path)));
            $in = fopen($path, 'rb');
            if (!$in || stream_copy_to_stream($in, $out) !== $size) {
                throw new RuntimeException("Não foi possível ler $local.");
            }
            fclose($in);
            fwrite($out, $pad($size));
        }
        fwrite($out, str_repeat("\0", 1024)); // fim do arquivo: dois blocos vazios
        fclose($out);
    }

    /** Cabeçalho ustar de 512 bytes (nomes maiores que 100 bytes são cortados aqui; o pax guarda o inteiro). */
    private static function tarHeader(string $name, int $size, string $type, int $mtime = 0): string
    {
        $header = pack('a100a8a8a8a12a12a8a1a100a6a2a247', $name, '0000644', '0000000', '0000000',
            sprintf('%011o', $size), sprintf('%011o', $mtime), str_repeat(' ', 8), $type, '', 'ustar', '00', '');
        return substr_replace($header, sprintf('%06o', array_sum(unpack('C*', $header))) . "\0 ", 148, 8);
    }

    /** Envia o arquivo para download e apaga do servidor. */
    public static function send(string $file): never
    {
        $name = basename($file);
        header('Content-Type: ' . (str_ends_with($name, '.zip') ? 'application/zip' : 'application/gzip'));
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: no-store');
        ignore_user_abort(true); // apaga o arquivo mesmo se o download for cancelado
        readfile($file);
        @unlink($file);
        exit;
    }

    /** [caminho dentro do backup => caminho no disco] */
    private static function files(bool $withUploads): array
    {
        $map = [];
        // import/ guarda arquivos temporários de importações (ex.: .xml do WordPress)
        self::collect($map, DATA_DIR, 'data', ['cache', 'sessions', 'logs', 'import']);
        if ($withUploads) {
            // public/uploads e, se o blog veio do WordPress, public/wp-content/uploads
            foreach (Media::roots() as $dir) {
                self::collect($map, $dir, 'public/' . substr($dir, strlen(PUBLIC_DIR) + 1));
            }
            // A proteção da cópia do WordPress (nada ali é executado) vai junto para valer ao restaurar.
            $guard = PUBLIC_DIR . '/wp-content/.htaccess';
            if (is_file($guard)) {
                $map['public/wp-content/.htaccess'] = $guard;
            }
        }
        return $map;
    }

    private static function collect(array &$map, string $dir, string $prefix, array $skip = []): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($dir) + 1));
            if (in_array(explode('/', $relative)[0], $skip, true) || $relative === '.lock' || str_ends_with($relative, '.tmp')) {
                continue;
            }
            $map[$prefix . '/' . $relative] = $file->getPathname();
        }
    }
}
