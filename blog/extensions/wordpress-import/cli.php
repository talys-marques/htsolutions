<?php
/**
 * Importa a exportação do WordPress pelo terminal (bom para arquivos grandes).
 *
 *   php extensions/wordpress-import/cli.php exportacao.xml
 *   php extensions/wordpress-import/cli.php exportacao.xml --url=https://blog-antigo.com.br --autor=fabiano --sem-rascunhos
 *   php extensions/wordpress-import/cli.php --imagens [--url=https://blog-antigo.com.br]
 *   php extensions/wordpress-import/cli.php exportacao.xml --so-seo
 *
 * No WordPress: Ferramentas → Exportar → Todo o conteúdo.
 * Depois traga as imagens: --imagens baixa do site antigo as que os posts usam,
 * ou copie wp-content/uploads do WordPress para public/wp-content/uploads.
 * --so-seo: para blogs importados antes desta versão. Completa nos posts já importados
 * o SEO do Yoast/Rank Math, a categoria principal e o texto alternativo das capas, sem mexer no resto.
 */
if (PHP_SAPI !== 'cli') {
    exit("Use pela linha de comando.\n");
}
require dirname(__DIR__, 2) . '/app/bootstrap.php';

$file = null;
$options = [];
foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--')) {
        [$key, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, '1');
        $options[$key] = $value;
    } else {
        $file = $arg;
    }
}
if (isset($options['imagens'])) {
    $xml = $file ?? (glob(Storage::path('import') . '/*.xml') ?: [''])[0];
    $url = rtrim($options['url'] ?? ($xml ? WordPressImporter::siteUrl($xml) : ''), '/');
    if ($url === '') {
        fwrite(STDERR, "Informe o site antigo: --url=https://blog-antigo.com.br\n");
        exit(1);
    }
    $paths = WordPressImages::missing();
    echo count($paths) . " imagens para baixar de $url\n";
    $ok = 0;
    $failed = 0;
    foreach (array_chunk($paths, 10) as $chunk) {
        [$a, $b] = WordPressImages::download($url, $chunk);
        $ok += $a;
        $failed += $b;
        if ($ok === 0) {
            fwrite(STDERR, "Nenhuma imagem pôde ser baixada de $url. Confira o endereço e se o site antigo ainda está no ar.\n");
            exit(1);
        }
        echo "\r  " . ($ok + $failed) . ' de ' . count($paths) . ($failed ? " ($failed não encontradas)" : '');
    }
    echo "\nPronto: $ok imagens baixadas para public/wp-content/uploads\n";
    exit(0);
}
if (!$file) {
    fwrite(STDERR, "Uso: php extensions/wordpress-import/cli.php exportacao.xml [--url=https://blog-antigo.com] [--autor=usuario] [--sem-rascunhos]\n"
        . "     php extensions/wordpress-import/cli.php --imagens [--url=https://blog-antigo.com]\n"
        . "     php extensions/wordpress-import/cli.php exportacao.xml --so-seo\n");
    exit(1);
}

$author = $options['autor'] ?? (Auth::admin()['username'] ?? null);
if (!$author || !Auth::find($author)) {
    fwrite(STDERR, "Crie a conta de administrador antes (abra /admin no navegador) ou informe --autor=usuario.\n");
    exit(1);
}

if (isset($options['so-seo'])) {
    $report = (new WordPressImporter($author))->updateSeo($file);
    echo "SEO atualizado em {$report['updated']} posts e páginas · {$report['redirects']} redirecionamento(s) de categorias e tags\n";
    exit(0);
}

$start = microtime(true);
$report = (new WordPressImporter($author, !isset($options['sem-rascunhos'])))->import($file, $options['url'] ?? '');

echo "Importação concluída em " . number_format(microtime(true) - $start, 1, ',', '') . "s\n";
echo "  Posts:             {$report['posts']}\n";
echo "  Páginas:           {$report['pages']}\n";
echo "  Com capa:          {$report['with_cover']}\n";
echo "  Atualizados:       {$report['updated']}\n";
echo "  Ignorados:         {$report['skipped']}\n";
echo "  Redirecionamentos: {$report['redirects']}\n";
if ($report['old_url']) {
    echo "  Site antigo:       {$report['old_url']}\n";
}
if ($report['authors']) {
    echo "\nAutores sem usuário aqui (posts ficaram com $author): " . implode(', ', array_keys($report['authors'])) . "\n";
}
if ($report['shortcodes']) {
    echo "\nShortcodes que continuam no texto:\n";
    foreach (array_slice($report['shortcodes'], 0, 20, true) as $name => $count) {
        echo "  [$name] ×$count\n";
    }
}
echo "\nNão esqueça as imagens: php extensions/wordpress-import/cli.php --imagens\n";
echo "(ou copie wp-content/uploads do WordPress para public/wp-content/uploads)\n";
