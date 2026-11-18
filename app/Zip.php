<?php
/**
 * Leitor mínimo de arquivos .zip em PHP puro: não depende da extensão zip,
 * que falta em algumas hospedagens. Lê arquivos guardados sem compressão ou
 * com "deflate", que é o que Windows, macOS, 7-Zip e o comando zip usam.
 *
 *   $zip = new Zip('/caminho/tema.zip');
 *   foreach ($zip->files() as $name => $size) {
 *       $conteudo = $zip->read($name);
 *   }
 */
final class Zip
{
    /** @var resource */
    private $handle;

    /** nome => dados do diretório central (offset, method, csize, size, crc, flags) */
    private array $entries = [];

    public function __construct(string $file)
    {
        $handle = @fopen($file, 'rb');
        if (!$handle) {
            throw new RuntimeException('Não foi possível abrir o arquivo .zip.');
        }
        $this->handle = $handle;

        // O fim do diretório central fica nos últimos 22 bytes, mais o comentário (até 64 KB).
        $size = (int) fstat($handle)['size'];
        $tail = min($size, 22 + 65535);
        fseek($handle, $size - $tail);
        $data = (string) fread($handle, $tail);
        $pos = strrpos($data, "PK\x05\x06");
        if ($pos === false || strlen($data) - $pos < 22) {
            throw new RuntimeException('O arquivo não é um .zip válido.');
        }
        $end = unpack('vdisk/vstart/vcount/vtotal/Vsize/Voffset', substr($data, $pos + 4, 16));
        if ($end['total'] === 0xFFFF || $end['offset'] === 0xFFFFFFFF) {
            throw new RuntimeException('O .zip está no formato ZIP64, que não é aceito. Compacte de novo com menos arquivos.');
        }

        fseek($handle, $end['offset']);
        $dir = (string) fread($handle, $end['size']);
        $p = 0;
        for ($i = 0; $i < $end['total']; $i++) {
            if (substr($dir, $p, 4) !== "PK\x01\x02") {
                throw new RuntimeException('O arquivo .zip está corrompido.');
            }
            $h = unpack('vmade/vneed/vflags/vmethod/vtime/vdate/Vcrc/Vcsize/Vsize/vname/vextra/vcomment/vdisk/vinternal/Vexternal/Voffset',
                substr($dir, $p + 4, 42));
            $name = str_replace('\\', '/', substr($dir, $p + 46, $h['name']));
            $p += 46 + $h['name'] + $h['extra'] + $h['comment'];
            if (!str_ends_with($name, '/')) { // pastas não interessam
                $this->entries[$name] = $h;
            }
        }
    }

    public function __destruct()
    {
        fclose($this->handle);
    }

    /**
     * Cria um .zip. $files: [nome no zip => arquivo no disco]; $contents: [nome no zip => conteúdo].
     * Compacta com deflate, que qualquer sistema abre.
     */
    public static function create(string $target, array $files, array $contents = []): void
    {
        $out = @fopen($target, 'wb');
        if (!$out) {
            throw new RuntimeException('Não foi possível criar o arquivo .zip.');
        }
        $central = '';
        $count = 0;
        $add = function (string $name, string $data, int $mtime) use ($out, &$central, &$count): void {
            $deflated = (string) gzdeflate($data, 6);
            [$method, $stored] = strlen($deflated) < strlen($data) ? [8, $deflated] : [0, $data];
            $d = getdate($mtime);
            $time = ($d['hours'] << 11) | ($d['minutes'] << 5) | intdiv($d['seconds'], 2);
            $date = (max(0, $d['year'] - 1980) << 9) | ($d['mon'] << 5) | $d['mday'];
            // Versão 2.0, nome em UTF-8 (bit 11), método, data, CRC e tamanhos.
            $common = pack('vvvvvVVVv', 20, 0x0800, $method, $time, $date, crc32($data), strlen($stored), strlen($data), strlen($name));
            $offset = ftell($out);
            fwrite($out, "PK\x03\x04" . $common . pack('v', 0) . $name . $stored);
            $central .= "PK\x01\x02" . pack('v', 20) . $common . pack('vvvvVV', 0, 0, 0, 0, 0, $offset) . $name;
            $count++;
        };
        foreach ($files as $name => $path) {
            $data = @file_get_contents($path);
            if ($data === false) {
                fclose($out);
                throw new RuntimeException('Não foi possível ler ' . basename($path) . '.');
            }
            $add((string) $name, $data, (int) filemtime($path));
        }
        foreach ($contents as $name => $data) {
            $add((string) $name, (string) $data, time());
        }
        $offset = ftell($out);
        fwrite($out, $central . "PK\x05\x06" . pack('vvvvVVv', 0, 0, $count, $count, strlen($central), $offset, 0));
        fclose($out);
    }

    /** Arquivos do .zip: [caminho => tamanho descompactado]. */
    public function files(): array
    {
        return array_map(fn($h) => $h['size'], $this->entries);
    }

    /** Conteúdo de um arquivo do .zip. */
    public function read(string $name): string
    {
        $h = $this->entries[$name] ?? null;
        if ($h === null) {
            throw new RuntimeException("O arquivo $name não está no .zip.");
        }
        if ($h['flags'] & 1) {
            throw new RuntimeException('O .zip tem senha. Compacte de novo sem senha.');
        }
        // O cabeçalho local repete o nome e pode ter um campo extra de tamanho diferente.
        fseek($this->handle, $h['offset']);
        $local = (string) fread($this->handle, 30);
        if (substr($local, 0, 4) !== "PK\x03\x04") {
            throw new RuntimeException('O arquivo .zip está corrompido.');
        }
        $sizes = unpack('vname/vextra', substr($local, 26, 4));
        fseek($this->handle, $h['offset'] + 30 + $sizes['name'] + $sizes['extra']);
        $data = $h['csize'] > 0 ? (string) fread($this->handle, $h['csize']) : '';

        $content = match ($h['method']) {
            0 => $data,
            8 => $data === '' ? '' : @gzinflate($data),
            default => throw new RuntimeException("$name usa um tipo de compressão não aceito. Compacte de novo no formato padrão (deflate)."),
        };
        if ($content === false || strlen($content) !== $h['size'] || crc32($content) !== $h['crc']) {
            throw new RuntimeException("$name está corrompido dentro do .zip.");
        }
        return $content;
    }
}
