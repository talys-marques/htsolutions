<?php
return [
    'titulo' => 'Quem somos',
    'ordem' => 3,
    'campos' => [
        'resumo' => Campo::html('Texto da abertura'),
        'texto' => Campo::html('História'),
        'video' => Campo::grupo('Vídeo', ['titulo' => Campo::texto('Título'), 'url' => Campo::link('Vídeo (arquivo .mp4)')]),
        'premiacoes' => Campo::grupo('Premiações', [
            'titulo' => Campo::titulo('Título'),
            'itens' => Campo::lista('Prêmios', 'Prêmio', ['foto' => Campo::imagem('Foto'), 'texto' => Campo::html('Texto')]),
        ]),
        'titulo_parceiros' => Campo::titulo('Título da lista de parceiros', 'Os parceiros vêm de "Topo, rodapé e contatos".'),
    ],
];
