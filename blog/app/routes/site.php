<?php
/**
 * Rotas públicas. Handlers em app/Site.php.
 * A ordem importa: a rota genérica /{slug} fica por último.
 * As URLs seguem o padrão do WordPress para não perder SEO na migração.
 *
 * @var Router $router
 */

Auth::resumeSession();

$router->get('/', fn() => Site::home());
$router->get('/page/{n:\d+}', fn($p) => Site::home((int) $p['n']));
$router->get('/sitemap.xml', fn() => Site::sitemap());
$router->get('/robots.txt', fn() => Site::robots());
$router->get('/search.json', fn() => Site::suggest());
$router->get('/favicon.ico', fn() => Site::favicon());
// Sitemaps do Yoast, Rank Math e do próprio WordPress, que ficam cadastrados no Search Console.
$router->get('/{file:(?:sitemap_index|wp-sitemap[\w-]*|[a-z_]+-sitemap\d*)\.xml}', fn() => redirect('/sitemap.xml', 301));
// Versões menores das imagens (srcset), geradas na primeira visita. Veja Media::thumb().
$router->get('/thumbs/{width:\d+}/{path:.+}', fn($p) => Media::thumb((int) $p['width'], $p['path']));

foreach (['category', 'tag', 'author'] as $kind) {
    $router->get("/$kind/{slug}", fn($p) => Site::archive($kind, $p['slug']));
    $router->get("/$kind/{slug}/page/{n:\d+}", fn($p) => Site::archive($kind, $p['slug'], (int) $p['n']));
}

// Página dos posts (Configurações → Página inicial): /blog/page/2/
$router->get('/{slug}/page/{n:\d+}', fn($p) => Site::blog((int) $p['n'], $p['slug']));
$router->get('/{slug}', fn($p) => Site::single($p['slug']));
