<?php
/*
 * Toda requisição passa por aqui (ver .htaccess).
 */

// Servidor embutido do PHP (php -S): arquivos que existem são servidos direto.
// Menos .php (ex.: um que veio na cópia de wp-content/), que ele executaria.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    if ($file !== __FILE__ && is_file($file) && !preg_match('/\.(php\d?|phtml|phar)/i', $file)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

App::run();
