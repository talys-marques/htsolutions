<?php
/**
 * Extensão opcional: /ultimos.json com os posts mais recentes, para o site institucional
 * (fora do blog) mostrar as últimas notícias e cases. Para remover, apague a pasta.
 */
return [
    'name' => 'Últimos posts em JSON',
    'routes' => function (Router $router): void {
        $router->get('/ultimos.json', fn() => LatestPosts::json());
    },
];
