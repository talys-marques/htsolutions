<?php
/**
 * Importa os posts do Strapi pelo terminal.
 *
 *   php extensions/strapi-import/cli.php ../dados/strapi-2026-10-29
 *   php extensions/strapi-import/cli.php https://ht-strapi.azurewebsites.net/api --autor=usuario
 *
 * O primeiro lê uma pasta com blogs*.json (respostas salvas de /api/blogs); o segundo
 * lê a API direto. Precisa da conta de administrador (abra /admin antes).
 */
if (PHP_SAPI !== 'cli') {
    exit("Use pela linha de comando.\n");
}
require dirname(__DIR__, 2) . '/app/bootstrap.php';

$source = null;
$options = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, '1');
        $options[$key] = $value;
    } else {
        $source = $arg;
    }
}
if (!$source) {
    fwrite(STDERR, "Uso: php extensions/strapi-import/cli.php <pasta com blogs*.json | https://.../api> [--autor=usuario]\n");
    exit(1);
}

$author = $options['autor'] ?? (Auth::admin()['username'] ?? null);
if (!$author || !Auth::find($author)) {
    fwrite(STDERR, "Crie a conta de administrador antes (abra /admin no navegador) ou informe --autor=usuario.\n");
    exit(1);
}
if (!function_exists('imagescale')) {
    echo "Aviso: PHP sem a extensão gd. As imagens serão copiadas no tamanho original.\n";
}

$start = microtime(true);
$entries = StrapiImporter::load($source);
echo count($entries) . " posts encontrados. Baixando imagens e importando...\n";
$report = (new StrapiImporter($author))->import($entries);

echo "Importação concluída em " . number_format(microtime(true) - $start, 1, ',', '') . "s\n";
echo "  Posts:             {$report['posts']}\n";
echo "  Atualizados:       {$report['updated']}\n";
echo "  Com capa:          {$report['with_cover']}\n";
echo "  Arquivos baixados: {$report['files']}\n";
echo "  Redirecionamentos: {$report['redirects']}\n";
if ($report['missing']) {
    echo "\nArquivos que não puderam ser baixados (removidos do texto):\n";
    foreach (array_unique($report['missing']) as $url) {
        echo "  $url\n";
    }
}
