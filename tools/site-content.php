<?php
/**
 * Conteúdo do site (páginas com modelo, app/SiteContent.php) pelo terminal, com o tema ativo:
 *
 *   php tools/site-content.php            cria as páginas que faltam a partir de content/ do tema
 *   php tools/site-content.php --update   também sobrescreve as que já existem com o que está em content/
 *   php tools/site-content.php --export   grava em content/ do tema o que está nas páginas (para
 *                                         versionar no Git ou levar o conteúdo junto com o tema)
 *
 * O mesmo "criar as que faltam" tem um botão em Painel → Conteúdo do site.
 */
if (PHP_SAPI !== 'cli') {
    exit("Use pela linha de comando.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';

$options = array_slice($argv, 1);
echo 'Tema ativo: ' . Themes::current()['name'] . "\n";

if (in_array('--export', $options, true)) {
    try {
        $files = SiteContent::export();
    } catch (RuntimeException $e) {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
    foreach ($files as $file) {
        echo "  $file\n";
    }
    echo count($files) . " arquivo(s) gravado(s).\n";
    exit(0);
}

$admin = Auth::admin();
if (!$admin) {
    fwrite(STDERR, "Crie a conta de administrador antes (abra /admin no navegador).\n");
    exit(1);
}
$log = SiteContent::install($admin['username'], in_array('--update', $options, true));
foreach (['created' => 'criada', 'updated' => 'atualizada', 'skipped' => 'já existe'] as $key => $label) {
    foreach ($log[$key] as $path) {
        echo "  $label: $path\n";
    }
}
echo count($log['created']) . ' criada(s), ' . count($log['updated']) . ' atualizada(s), ' . count($log['skipped']) . " sem mudança.\n";
