<?php
/**
 * Redirecionamentos 301 (ex.: endereços antigos do WordPress).
 * Ficam em data/redirects.json no formato {"/caminho-antigo": "/caminho-novo/"}.
 * Só são consultados quando nenhuma página existe no endereço pedido.
 */
final class Redirects
{
    public static function all(): array
    {
        $map = Storage::read('redirects.json', []);
        return is_array($map) ? $map : [];
    }

    /** "/Antigo/Endereço/" → "/antigo/endereço" (forma usada como chave). */
    public static function normalize(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?: '/';
        return '/' . trim(mb_strtolower(rawurldecode($path)), '/');
    }

    public static function match(string $path): ?string
    {
        $key = self::normalize($path);
        $to = self::all()[$key] ?? null;
        return is_string($to) && self::normalize($to) !== $key ? $to : null;
    }

    /* ------------------------------------------------ Endereços não encontrados */

    /** Quantos endereços com 404 ficam guardados (os vistos por último). */
    private const MISSES_MAX = 200;

    /**
     * Anota um endereço que deu 404 (data/not-found.json), para o dono redirecioná-lo em
     * Painel → Redirecionamentos. Robôs atrás de falhas (wp-login.php, .env...) ficam de fora.
     */
    public static function logMiss(string $path): void
    {
        if (str_starts_with($path . '/', '/admin/')
            || preg_match('#/\.|\.(php\d?|aspx?|jsp|cgi|env|ini|sql|bak|zip|gz|log|ya?ml)$|^/(wp-admin|wp-includes|wp-json|cgi-bin|vendor|thumbs)(/|$)#i', $path)) {
            return;
        }
        $key = mb_substr(self::normalize($path), 0, 300);
        $referer = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''));
        $from = isset($referer['host']) ? mb_substr($referer['host'] . ($referer['path'] ?? ''), 0, 200) : '';
        Storage::lock(function () use ($key, $from) {
            $misses = self::misses(false);
            $misses[$key] = [
                'count' => ($misses[$key]['count'] ?? 0) + 1,
                'last' => date('c'),
                'from' => $from !== '' ? $from : ($misses[$key]['from'] ?? ''),
            ];
            if (count($misses) > self::MISSES_MAX) {
                uasort($misses, fn($a, $b) => strcmp($b['last'], $a['last']));
                $misses = array_slice($misses, 0, self::MISSES_MAX, true);
            }
            Storage::write('not-found.json', $misses, false);
        });
    }

    /**
     * Endereços que deram 404, dos mais acessados para os menos.
     * $pending = true deixa de fora os que já ganharam redirecionamento.
     * Cada um: { count, last (data), from (página que tinha o link) }.
     */
    public static function misses(bool $pending = true): array
    {
        $misses = Storage::read('not-found.json', []);
        $misses = is_array($misses) ? $misses : [];
        if ($pending) {
            $redirects = self::all();
            $misses = array_filter($misses, fn($path) => !isset($redirects[$path]), ARRAY_FILTER_USE_KEY);
            uasort($misses, fn($a, $b) => $b['count'] <=> $a['count'] ?: strcmp($b['last'], $a['last']));
        }
        return $misses;
    }

    /** Tira endereços da lista de 404 (todos, se $paths vier vazio). */
    public static function forgetMisses(array $paths = []): void
    {
        Storage::lock(function () use ($paths) {
            $keys = array_flip(array_map([self::class, 'normalize'], $paths));
            Storage::write('not-found.json', $paths ? array_diff_key(self::misses(false), $keys) : [], false);
        });
    }

    public static function add(string $from, string $to): void
    {
        self::addMany([$from => $to]);
    }

    public static function addMany(array $pairs): void
    {
        if (!$pairs) {
            return;
        }
        Storage::lock(function () use ($pairs) {
            $map = self::all();
            foreach ($pairs as $from => $to) {
                self::put($map, (string) $from, (string) $to);
            }
            ksort($map);
            Storage::write('redirects.json', $map);
        });
    }

    public static function replaceAll(array $map): void
    {
        Storage::lock(function () use ($map) {
            $clean = [];
            foreach ($map as $from => $to) {
                self::put($clean, (string) $from, (string) $to);
            }
            ksort($clean);
            Storage::write('redirects.json', $clean);
        });
    }

    /** Texto "origem destino" (um por linha) → [mapa, linhas inválidas]. */
    public static function parse(string $text): array
    {
        $map = [];
        $invalid = [];
        foreach (preg_split('/\R/', $text) as $n => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = preg_split('/\s+(?:->\s+)?/', $line);
            if (count($parts) !== 2 || !str_starts_with($parts[0], '/')) {
                $invalid[] = $n + 1;
                continue;
            }
            $map[$parts[0]] = $parts[1];
        }
        return [$map, $invalid];
    }

    public static function toText(): string
    {
        $lines = [];
        foreach (self::all() as $from => $to) {
            $lines[] = $from . ' ' . $to;
        }
        return implode("\n", $lines);
    }

    private static function put(array &$map, string $from, string $to): void
    {
        $key = self::normalize($from);
        $target = self::normalize($to);
        if ($key === $target) {
            return;
        }
        foreach ($map as $k => $v) {
            if (self::normalize($v) === $key) {
                $map[$k] = $to; // evita cadeias A → B → C
            }
        }
        unset($map[$target]); // o destino não pode redirecionar (evita loop)
        $map[$key] = $to;
    }
}
