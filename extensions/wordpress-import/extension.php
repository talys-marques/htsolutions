<?php
/**
 * Extensão opcional: importar conteúdo do WordPress.
 * Para remover, apague a pasta extensions/wordpress-import/. O resto do blog
 * (inclusive o que já foi importado) continua funcionando.
 */
return [
    'name' => 'Importar do WordPress',
    'routes' => function (Router $router): void {
        $router->any('/admin/import', fn() => WordPressImportPage::handle());
    },
    'menu' => [
        ['key' => 'import', 'url' => '/admin/import', 'label' => 'Importar WordPress', 'role' => 'admin'],
    ],
];
