<?php
/**
 * Leitura e escrita dos arquivos JSON em data/.
 *
 * Toda gravação é atômica: o conteúdo vai para um arquivo temporário que
 * depois substitui o original (rename). Assim um arquivo nunca fica pela
 * metade, mesmo se o PHP morrer no meio da gravação.
 *
 * Operações do tipo "ler, alterar, gravar" devem rodar dentro de
 * Storage::lock() para que duas requisições não se atropelem.
 */
final class Storage
{
    private static int $lockDepth = 0;

    /** Caminho absoluto de um arquivo dentro de data/. */
    public static function path(string $relative = ''): string
    {
        return DATA_DIR . ($relative === '' ? '' : '/' . ltrim($relative, '/'));
    }

    /** Lê um JSON e devolve como array. Se não existir, devolve $default. */
    public static function read(string $relative, $default = null)
    {
        $file = self::path($relative);
        if (!is_file($file)) {
            return $default;
        }
        $raw = @file_get_contents($file);
        if ($raw === false || trim($raw) === '') {
            return $default;
        }
        try {
            return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Arquivo JSON inválido: data/$relative ({$e->getMessage()})");
        }
    }

    /** Grava $data como JSON. $pretty = false gera arquivos menores (índices). */
    public static function write(string $relative, $data, bool $pretty = true): void
    {
        $file = self::path($relative);
        $dir = dirname($file);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Não foi possível criar a pasta $dir. Verifique as permissões.");
        }

        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        $json = json_encode($data, $flags);

        $tmp = $file . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($tmp, $json) === false) {
            throw new RuntimeException("Não foi possível gravar data/$relative. Verifique as permissões.");
        }
        // No Windows o rename falha enquanto outro processo lê o destino: espera a
        // leitura terminar. Apagar o destino antes deixaria quem lê sem arquivo.
        for ($try = 1; !@rename($tmp, $file); $try++) {
            if ($try === 20) {
                @unlink($tmp);
                throw new RuntimeException("Não foi possível gravar data/$relative.");
            }
            usleep(10000 * $try);
        }
    }

    public static function delete(string $relative): void
    {
        $file = self::path($relative);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Executa $fn com uma trava exclusiva (data/.lock). Chamadas aninhadas
     * reaproveitam a mesma trava. Devolve o retorno de $fn.
     */
    public static function lock(callable $fn)
    {
        if (self::$lockDepth > 0) {
            self::$lockDepth++;
            try {
                return $fn();
            } finally {
                self::$lockDepth--;
            }
        }

        $handle = @fopen(self::path('.lock'), 'c');
        if (!$handle || !flock($handle, LOCK_EX)) {
            throw new RuntimeException('Não foi possível travar data/.lock. Verifique as permissões da pasta data/.');
        }
        self::$lockDepth = 1;
        try {
            return $fn();
        } finally {
            self::$lockDepth = 0;
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
