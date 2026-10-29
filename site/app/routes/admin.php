<?php
/**
 * Rotas do painel. Handlers em app/Admin.php.
 * Toda tela exige login (exceto login e configuração inicial) e todo POST
 * verifica o token CSRF dentro do handler.
 *
 * @var Router $router
 * @var string $path
 */

if (str_starts_with($path, '/admin')) {
    Auth::startSession();
}

$router->any('/admin/setup', fn() => Admin::setup());
$router->any('/admin/login', fn() => Admin::login());
$router->post('/admin/logout', fn() => Admin::logout());
$router->get('/admin', fn() => redirect('/admin/posts'));

$router->get('/admin/posts', fn() => Admin::posts());
$router->get('/admin/posts/new', fn() => Admin::newPost());
$router->post('/admin/posts/new', fn() => Admin::savePost(null));
$router->get('/admin/posts/{id:\d+}', fn($p) => Admin::editPost((int) $p['id']));
$router->post('/admin/posts/{id:\d+}', fn($p) => Admin::savePost((int) $p['id']));
$router->post('/admin/posts/{id:\d+}/revision', fn($p) => Admin::restoreRevision((int) $p['id']));
$router->post('/admin/posts/{id:\d+}/{action:trash|restore|delete}', fn($p) => Admin::postAction((int) $p['id'], $p['action']));
$router->get('/admin/preview/{id:\d+}', fn($p) => Admin::preview((int) $p['id']));

$router->get('/admin/media', fn() => Admin::media());
$router->get('/admin/media/list', fn() => Admin::mediaJson());
$router->post('/admin/media/upload', fn() => Admin::upload());
$router->post('/admin/media/delete', fn() => Admin::deleteMedia());

$router->get('/admin/users', fn() => Admin::users());
$router->any('/admin/users/new', fn() => Admin::userForm(null));
$router->any('/admin/users/{username:[a-z0-9._-]+}', fn($p) => Admin::userForm($p['username']));
$router->post('/admin/users/{username:[a-z0-9._-]+}/delete', fn($p) => Admin::deleteUser($p['username']));
$router->any('/admin/account', fn() => Admin::account());

$router->any('/admin/settings', fn() => Admin::settings());
$router->post('/admin/settings/preview', fn() => Admin::settingsPreview());
$router->get('/admin/settings/menu-items', fn() => Admin::menuItems());
$router->any('/admin/redirects', fn() => Admin::redirects());
$router->any('/admin/seo', fn() => Admin::seo());
$router->get('/admin/seo/terms', fn() => Admin::seoTerms());
$router->any('/admin/seo/terms/{kind:category|tag}/{slug}', fn($p) => Admin::seoTerm($p['kind'], $p['slug']));
$router->get('/admin/tools', fn() => Admin::tools());
$router->post('/admin/tools/backup', fn() => Admin::backup());
$router->post('/admin/tools/export', fn() => Admin::export());
$router->post('/admin/tools/reindex', fn() => Admin::reindex());