<?php
/*
 * Funções utilitárias globais, usadas pelo sistema e pelos templates.
 */

/* =================================================================== Básico */

/** Escapa texto para HTML. Use sempre que imprimir dados: <?= e($x) ?> */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Valor de config.php. */
function config(string $key, $default = null)
{
    return App::$config[$key] ?? $default;
}

/** Valor das Configurações do painel (data/settings.json). */
function setting(string $key, $default = null)
{
    return Settings::get($key, $default);
}

/* ===================================================================== URLs */

/** Caminho interno → URL, respeitando instalação em subpasta. Ex.: url('/sitemap.xml') */
function url(string $path = '/'): string
{
    if (preg_match('#^([a-z][a-z0-9+.-]*:|//|\#)#i', $path)) {
        return $path;
    }
    return App::$basePath . '/' . ltrim($path, '/');
}

/** Endereço público do blog, sem barra no final. Ex.: https://meublog.com.br */
function site_url(): string
{
    $configured = rtrim((string) setting('url'), '/');
    if ($configured !== '') {
        return $configured;
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[a-z0-9.\-]+(:\d+)?$/i', $host)) {
        $host = 'localhost';
    }
    return (is_https() ? 'https' : 'http') . '://' . $host . App::$basePath;
}

/** Protocolo + domínio do blog. Ex.: https://meublog.com.br */
function site_origin(): string
{
    $p = parse_url(site_url());
    return ($p['scheme'] ?? 'http') . '://' . ($p['host'] ?? 'localhost') . (isset($p['port']) ? ':' . $p['port'] : '');
}

/** URL absoluta de um caminho interno. Ex.: absolute_url('/meu-post/') */
function absolute_url(string $path = '/'): string
{
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return site_url() . '/' . ltrim($path, '/');
}

/** Torna absoluta uma URL que já passou por url() (ex.: /uploads/foto.jpg). */
function full_url(string $value): string
{
    if ($value === '' || preg_match('#^https?://#i', $value)) {
        return $value;
    }
    if (str_starts_with($value, '//')) {
        return (is_https() ? 'https:' : 'http:') . $value;
    }
    return site_origin() . '/' . ltrim($value, '/');
}

/*
 * Endereços internos gravados em data/ (imagens, links dos posts) ficam sem a pasta
 * do blog (/uploads/foto.jpg), para o blog funcionar em qualquer pasta sem mexer nos
 * dados. Posts, Settings e Terms colocam a pasta atual ao ler (/blog/uploads/foto.jpg)
 * e tiram ao gravar; o resto do sistema só vê o endereço completo.
 */

/** '/uploads/foto.jpg' → '/blog/uploads/foto.jpg'. Endereços de fora ficam como estão. */
function add_base(string $url): string
{
    return App::$basePath !== '' && str_starts_with($url, '/') && !str_starts_with($url, '//') ? App::$basePath . $url : $url;
}

/** '/blog/uploads/foto.jpg' → '/uploads/foto.jpg' (o contrário de add_base()). */
function strip_base(string $url): string
{
    $base = App::$basePath;
    if ($base !== '' && ($url === $base || str_starts_with($url, $base . '/'))) {
        return substr($url, strlen($base)) ?: '/';
    }
    return $url;
}

/** Aplica add_base() ou strip_base() em cada src, href e poster de um HTML. */
function html_base(string $html, callable $fn): string
{
    if (App::$basePath === '' || $html === '') {
        return $html;
    }
    return (string) preg_replace_callback('#(\s(?:src|href|poster)=)(["\'])(.*?)\2#is',
        fn($m) => $m[1] . $m[2] . $fn($m[3]) . $m[2], $html);
}

/** Troca src="/..." e href="/..." por URLs absolutas (usado na exportação para o WordPress). */
function absolutize_urls(string $html): string
{
    return (string) preg_replace('#(\s(?:src|href)=["\'])/(?!/)#i', '$1' . site_origin() . '/', $html);
}

/** URL de um arquivo em public/assets/, com versão para evitar cache velho. */
function asset(string $file): string
{
    $path = PUBLIC_DIR . '/assets/' . $file;
    return url('/assets/' . $file) . '?v=' . (is_file($path) ? filemtime($path) : 0);
}

/** Ícone da aba: o escolhido em Configurações ou o padrão. */
function favicon_url(): string
{
    return (string) setting('favicon') ?: asset('favicon.svg');
}

function post_url(array $post): string
{
    return url(post_path($post));
}

/** Caminho de um post sem a pasta do blog: /slug/, ou / para a página escolhida como inicial. */
function post_path(array $post): string
{
    return ($post['id'] ?? 0) === (Posts::homePage()['id'] ?? null) ? '/' : '/' . $post['slug'] . '/';
}

/** $kind: 'category' ou 'tag'. */
function term_url(string $kind, string $name): string
{
    return url('/' . ($kind === 'tag' ? 'tag' : 'category') . '/' . slugify($name) . '/');
}

function author_url(string $username): string
{
    return url('/author/' . rawurlencode($username) . '/');
}

/**
 * <img> de uma imagem com largura, altura e srcset (versões menores, veja Media::thumb()).
 * $sizes: largura em que ela aparece na tela, ex.: '(max-width: 640px) 100vw, 360px'.
 * Atributos com valor null ficam de fora; alt vazio ('') é mantido.
 */
function image_tag(string $src, string $sizes, array $attrs = []): string
{
    $size = Media::size($src);
    $srcset = Media::srcset($src);
    $attrs = ['src' => $src]
        + ($srcset !== '' ? ['srcset' => $srcset, 'sizes' => $sizes] : [])
        + ($size ? ['width' => $size[0], 'height' => $size[1]] : [])
        + $attrs;
    $html = '<img';
    foreach ($attrs as $name => $value) {
        if ($value !== null) {
            $html .= ' ' . $name . '="' . e($value) . '"';
        }
    }
    return $html . '>';
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
        || (string) ($_SERVER['SERVER_PORT'] ?? '') === '443';
}

/* ==================================================================== Texto */

function accents_map(): array
{
    static $map = null;
    return $map ??= [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u',
        'ç' => 'c', 'ñ' => 'n', 'ý' => 'y', 'ÿ' => 'y', 'ß' => 'ss', 'æ' => 'ae', 'œ' => 'oe',
    ];
}

/** "Olá, Mundo!" → "ola-mundo" */
function slugify(string $text): string
{
    $text = strtr(mb_strtolower(trim($text), 'UTF-8'), accents_map());
    $text = str_replace('&', ' e ', $text);
    $text = (string) preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim(substr($text, 0, 120), '-');
}

/** Minúsculas, sem acentos e com espaços simples (usado na busca). */
function normalize_text(string $text): string
{
    $text = strtr(mb_strtolower($text, 'UTF-8'), accents_map());
    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

/** HTML → texto puro. */
function plain_text(string $html): string
{
    $html = (string) preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $html);
    $html = (string) preg_replace('#<(br|/p|/h[1-6]|/li|/div|/blockquote|/figcaption|/tr|/td|/th)\b[^>]*>#i', '$0 ', $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string) preg_replace('/\s+/u', ' ', $text));
}

/** Resumo automático a partir do conteúdo. */
function excerpt_from(string $html, int $length = 180): string
{
    $text = plain_text($html);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    $cut = mb_substr($text, 0, $length);
    $space = mb_strrpos($cut, ' ');
    if ($space !== false && $space > $length * 0.6) {
        $cut = mb_substr($cut, 0, $space);
    }
    return rtrim($cut, " ,.;:!?-–—") . '…';
}

/** Minutos de leitura (200 palavras por minuto). */
function reading_time(string $html): int
{
    $text = plain_text($html);
    $words = $text === '' ? 0 : count(preg_split('/\s+/u', $text));
    return max(1, (int) ceil($words / 200));
}

/** "a, b, B" ou ['a', 'b'] → ['a', 'b'] (sem vazios e sem repetidos). */
function clean_list($value): array
{
    if (is_string($value)) {
        $value = explode(',', $value);
    }
    $out = [];
    foreach ((array) $value as $item) {
        $item = trim((string) preg_replace('/\s+/u', ' ', (string) $item));
        $key = slugify($item);
        if ($key !== '' && !isset($out[$key])) {
            $out[$key] = $item;
        }
    }
    return array_values($out);
}

/** "2026-10-23T14:30:00-03:00" → "23 de outubro de 2026" */
function format_date(?string $iso, bool $withTime = false): string
{
    $ts = $iso ? strtotime($iso) : false;
    if (!$ts) {
        return '';
    }
    $months = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $text = date('j', $ts) . ' de ' . $months[(int) date('n', $ts) - 1] . ' de ' . date('Y', $ts);
    return $withTime ? $text . ', ' . date('H:i', $ts) : $text;
}

/** ISO 8601 → valor de <input type="datetime-local">. */
function iso_to_input(?string $iso): string
{
    $ts = $iso ? strtotime($iso) : false;
    return $ts ? date('Y-m-d\TH:i', $ts) : '';
}

/** Valor de <input type="datetime-local"> → ISO 8601 no fuso do blog. */
function input_to_iso(string $value): string
{
    $ts = trim($value) === '' ? false : strtotime($value);
    return $ts ? date('c', $ts) : '';
}

function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}

/** Contraste entre duas cores #rrggbb, de 1 a 21 (WCAG). Texto comum pede 4.5 ou mais. */
function contrast_ratio(string $a, string $b): float
{
    $luminance = function (string $hex): float {
        $sum = 0.0;
        foreach ([0.2126, 0.7152, 0.0722] as $i => $weight) {
            $c = intval(substr($hex, 1 + $i * 2, 2), 16) / 255;
            $sum += $weight * ($c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4);
        }
        return $sum;
    };
    $la = $luminance($a);
    $lb = $luminance($b);
    return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

/** Cor de texto legível sobre um fundo #rrggbb: branco ou quase preto. */
function text_color_on(string $background): string
{
    return contrast_ratio($background, '#ffffff') >= contrast_ratio($background, '#1b1d22') ? '#ffffff' : '#1b1d22';
}

/** Link do YouTube/Vimeo → HTML do player. Devolve null se não reconhecer. */
function video_embed(string $url): ?string
{
    $url = html_entity_decode(trim($url));
    if (preg_match('~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([\w-]{11})~i', $url, $m)) {
        $src = 'https://www.youtube-nocookie.com/embed/' . $m[1];
    } elseif (preg_match('~vimeo\.com/(?:video/)?(\d+)~i', $url, $m)) {
        $src = 'https://player.vimeo.com/video/' . $m[1];
    } else {
        return null;
    }
    return '<figure class="embed"><iframe src="' . e($src) . '" title="Vídeo" loading="lazy" allowfullscreen '
        . 'allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe></figure>';
}

/* ======================================================= Requisição / resposta */

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/** Campo de formulário (POST), sem espaços nas pontas. */
function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/** Campo de formulário (POST) exatamente como veio (para HTML e código). */
function input_raw(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? str_replace("\r\n", "\n", $value) : '';
}

/** Parâmetro da URL (?chave=valor). */
function get_param(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

function wants_json(): bool
{
    return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

/** Organiza $_FILES['campo'] (um ou vários arquivos) em uma lista simples. */
function normalize_files($files): array
{
    if (!is_array($files) || !isset($files['name'])) {
        return [];
    }
    if (!is_array($files['name'])) {
        return [$files];
    }
    $out = [];
    foreach (array_keys($files['name']) as $i) {
        $out[] = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i],
        ];
    }
    return $out;
}

/** Redireciona para um caminho interno (ou URL completa) e encerra. */
function redirect(string $to, int $status = 302): never
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    header('Location: ' . url($to), true, $status);
    exit;
}

function json_response($data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: SAMEORIGIN');
    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

/* =================================================================== Sessão */

/** flash('success', 'Salvo!') guarda uma mensagem; flash() devolve e limpa as mensagens. */
function flash(?string $type = null, string $message = ''): array
{
    Auth::startSession();
    if ($type !== null) {
        $_SESSION['_flash'][] = [$type, $message];
        return [];
    }
    $messages = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $messages;
}

function csrf_token(): string
{
    Auth::startSession();
    return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
}

/** Campo oculto obrigatório em todo formulário POST do painel. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if (empty($_POST) && empty($_FILES) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        fail_request('O envio foi maior que o limite do servidor (post_max_size = ' . ini_get('post_max_size') . ').', 413);
    }
    $token = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        fail_request('Sua sessão expirou ou o formulário é inválido. Volte, recarregue a página e tente de novo. '
            . 'Se você estava escrevendo, o texto ficou guardado neste navegador.', 419);
    }
}

function fail_request(string $message, int $status = 400): never
{
    if (wants_json()) {
        json_response(['error' => $message], $status);
    }
    http_response_code($status);
    admin_view('message', ['title' => 'Não foi possível concluir', 'message' => $message], 'auth');
    exit;
}

/* ================================================================ Templates */

/** Renderiza um arquivo PHP com as variáveis dadas e devolve o HTML. */
function render_file(string $__file, array $__vars = []): string
{
    extract($__vars, EXTR_SKIP);
    ob_start();
    try {
        include $__file;
        return (string) ob_get_clean();
    } catch (Throwable $__e) {
        ob_end_clean();
        throw $__e;
    }
}

/** Renderiza theme/{template}.php dentro de theme/layout.php. */
function theme(string $template, array $vars = [], int $status = 200): void
{
    $vars += ['meta' => [], 'body_class' => $template];
    if ($status === 200 && !empty($vars['meta']['canonical'])) {
        Seo::redirectToCanonical($vars['meta']['canonical']); // ex.: /post sem a barra → /post/
    }
    $vars['site'] = Settings::all();
    $vars['content'] = render_file(THEME_DIR . '/' . $template . '.php', $vars);
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    if (Auth::user()) {
        // Quem está logado vê rascunhos e a barra do painel: nada de cache compartilhado.
        header('Cache-Control: private, no-store');
    }
    echo render_file(THEME_DIR . '/layout.php', $vars);
}

/** Inclui theme/partials/{name}.php. */
function partial(string $name, array $vars = []): void
{
    echo render_file(THEME_DIR . '/partials/' . $name . '.php', $vars + ['site' => Settings::all()]);
}

/**
 * Renderiza app/views/admin/{template}.php dentro do layout do painel.
 * Extensões passam o caminho completo do arquivo: admin_view(__DIR__ . '/views/x.php').
 */
function admin_view(string $template, array $vars = [], string $layout = 'layout'): void
{
    $file = str_ends_with($template, '.php') ? $template : APP_DIR . '/views/admin/' . $template . '.php';
    $vars['user'] = Auth::user();
    $vars['content'] = render_file($file, $vars);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    header('X-Robots-Tag: noindex, nofollow');
    // O editor mostra HTML dos posts dentro do painel: scripts só do próprio site,
    // nada de onerror="..." ou javascript: mesmo que algo escape do Html::clean().
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
        . "img-src * data: blob:; media-src * data: blob:; frame-src https:; object-src 'none'; "
        . "base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    echo render_file(APP_DIR . '/views/admin/' . $layout . '.php', $vars);
}

/** Fatia uma lista em páginas. */
function paginate(array $items, int $page, int $perPage): array
{
    $perPage = max(1, $perPage);
    $total = count($items);
    $page = max(1, $page);
    $pages = max(1, (int) ceil($total / $perPage));
    return [
        // Página além do fim (ex.: /page/99999999999999999999) = lista vazia, sem estourar o inteiro.
        'items' => $page <= $pages ? array_slice($items, ($page - 1) * $perPage, $perPage) : [],
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
    ];
}
