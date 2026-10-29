<?php
/**
 * Refaz data/index.json e data/search.json a partir de data/posts/.
 * Use depois de editar, copiar ou apagar arquivos de posts na mão.
 *
 *   php tools/reindex.php
 */
if (PHP_SAPI !== 'cli') {
    exit("Use pela linha de comando.\n");
}
require dirname(__DIR__) . '/app/bootstrap.php';

$index = Posts::rebuildIndex();
echo 'Índice reconstruído: ' . count($index['items']) . " item(ns).\n";
