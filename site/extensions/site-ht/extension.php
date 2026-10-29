<?php
/**
 * Conteúdo do site da HT Solutions: formulários de campos fixos para as páginas do site
 * (textos, imagens, links, menus). Autores também editam. Detalhes no README.md.
 */
return [
    'name' => 'Conteúdo do site',
    'routes' => function (Router $router): void {
        $router->get('/admin/site', fn() => SiteHT::lista());
        $router->any('/admin/site/{id:\d+}', fn($p) => SiteHT::editar((int) $p['id']));
        $router->post('/admin/site/{id:\d+}/versao', fn($p) => SiteHT::restaurar((int) $p['id']));
    },
    'menu' => [
        ['key' => 'site', 'url' => '/admin/site', 'label' => 'Conteúdo do site', 'role' => 'author', 'group' => 'Conteúdo'],
    ],
];
