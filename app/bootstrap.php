<?php
/*
 * Ponto de partida do sistema: define as pastas, carrega as classes
 * e inicializa a aplicação. Usado por public/index.php e pelos scripts
 * de linha de comando em tools/.
 */

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('Este blog precisa de PHP 8.1 ou superior. Versão atual: ' . PHP_VERSION);
}

define('ROOT_DIR', dirname(__DIR__));
define('APP_DIR', __DIR__);
define('PUBLIC_DIR', ROOT_DIR . '/public');
define('THEME_DIR', ROOT_DIR . '/theme');
define('EXTENSIONS_DIR', ROOT_DIR . '/extensions');

// Cada classe fica em app/NomeDaClasse.php (ou extensions/{nome}/NomeDaClasse.php)
// e é carregada quando usada.
spl_autoload_register(function (string $class): void {
    $file = APP_DIR . '/' . $class . '.php';
    if (is_file($file)) {
        require $file;
        return;
    }
    foreach (glob(EXTENSIONS_DIR . '/*/' . $class . '.php') ?: [] as $file) {
        require $file;
        return;
    }
});

require APP_DIR . '/helpers.php';

App::boot(require ROOT_DIR . '/config.php');
