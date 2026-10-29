<?php
/**
 * Inicialização e despacho das requisições.
 */
final class App
{
    public static array $config = [];

    /** Subpasta onde o blog está instalado ('' quando está na raiz do domínio). */
    public static string $basePath = '';

    /** Caminho da requisição atual, sem a subpasta. Ex.: /admin/posts */
    public static string $path = '/';

    public static function boot(array $config): void
    {
        self::$config = $config + [
            'debug' => false,
            'data_dir' => ROOT_DIR . '/data',
            'max_upload_mb' => 20,
            'image_max_width' => 2000,
            'revisions' => 20,
        ];
        define('DATA_DIR', rtrim(str_replace('\\', '/', (string) self::$config['data_dir']), '/'));

        error_reporting(E_ALL);
        ini_set('display_errors', self::$config['debug'] ? '1' : '0');
        ini_set('log_errors', '1');
        mb_internal_encoding('UTF-8');

        self::prepareDataDir();
        if (PHP_SAPI !== 'cli') {
            ini_set('error_log', DATA_DIR . '/logs/php-errors.log');
        }
        set_exception_handler([self::class, 'handleException']);
        self::$basePath = self::detectBasePath(); // antes de ler os dados (veja add_base())

        $timezone = (string) Settings::get('timezone');
        if ($timezone === '' || !@date_default_timezone_set($timezone)) {
            date_default_timezone_set('America/Sao_Paulo');
        }
    }

    public static function run(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        if (self::$basePath !== '' && str_starts_with($path, self::$basePath)) {
            $path = substr($path, strlen(self::$basePath));
        }
        $path = '/' . trim($path, '/');
        self::$path = $path;

        send_security_headers();

        // Endereço do site preenchido em Configurações: http://, www e outros domínios vão para ele.
        if (($method === 'GET' || $method === 'HEAD') && ($to = Seo::hostRedirect($path))) {
            redirect($to, 301);
        }

        $router = new Router();
        require APP_DIR . '/routes/admin.php';
        Extensions::routes($router); // antes das rotas públicas, que terminam com /{slug}
        require APP_DIR . '/routes/site.php';

        if ($router->dispatch($method, $path)) {
            return;
        }

        // Endereços antigos (ex.: do WordPress) cadastrados em Redirecionamentos.
        if (($method === 'GET' || $method === 'HEAD') && ($to = Redirects::match($path))) {
            redirect($to, 301);
        }

        if ($method === 'GET') {
            Redirects::logMiss($path); // aparece em Redirecionamentos, para virar redirecionamento
        }
        Site::notFound();
    }

    public static function handleException(Throwable $e): void
    {
        error_log((string) $e);
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, 'Erro: ' . $e->getMessage() . PHP_EOL);
            exit(1);
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }
        $detail = self::$config['debug']
            ? '<pre style="white-space:pre-wrap;background:#f4f4f5;padding:1rem;border-radius:8px">' . e((string) $e) . '</pre>'
            : '<p>Algo deu errado. Os detalhes foram registrados em <code>data/logs/php-errors.log</code>.</p>';
        echo '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>Erro</title><body style="font-family:system-ui,sans-serif;max-width:760px;margin:4rem auto;padding:0 1rem;color:#1d2026">'
            . '<h1>Erro interno</h1>' . $detail . '</body>';
    }

    private static function prepareDataDir(): void
    {
        foreach (['', '/posts', '/revisions', '/cache', '/logs', '/sessions'] as $sub) {
            if (!is_dir(DATA_DIR . $sub)) {
                @mkdir(DATA_DIR . $sub, 0775, true);
            }
        }
        if (!is_dir(DATA_DIR) || !is_writable(DATA_DIR)) {
            http_response_code(500);
            exit('A pasta de dados (' . DATA_DIR . ') não existe ou não tem permissão de escrita. '
                . 'Ajuste as permissões (ex.: chmod 775) e recarregue a página.');
        }
        if (!is_file(DATA_DIR . '/.htaccess')) {
            // Mesmo formato dos outros .htaccess: funciona no Apache 2.2 e 2.4.
            @file_put_contents(DATA_DIR . '/.htaccess', "# Nunca servir estes arquivos pela web\n"
                . "<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n"
                . "<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n");
        }
    }

    private static function detectBasePath(): string
    {
        // No servidor embutido (php -S ... -t public) o blog está sempre na raiz, e
        // ele preenche SCRIPT_NAME de forma diferente para URLs com ponto.
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') {
            return '';
        }
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        if ($dir !== '' && $uri !== $dir && !str_starts_with($uri, $dir . '/')) {
            // O .htaccess da raiz reescreveu a URL para public/ sem que ela apareça no endereço.
            $dir = (string) preg_replace('#/public$#', '', $dir);
        }
        return $dir;
    }
}
