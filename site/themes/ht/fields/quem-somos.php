<?php
return [
    'label' => 'Quem somos',
    'order' => 3,
    'fields' => [
        'resumo' => Field::html('Texto da abertura'),
        'texto' => Field::html('História'),
        'video' => Field::group('Vídeo', ['titulo' => Field::text('Título'), 'url' => Field::link('Vídeo (arquivo .mp4)')]),
        'premiacoes' => Field::group('Premiações', [
            'titulo' => Field::heading('Título'),
            'itens' => Field::list('Prêmios', 'Prêmio', ['foto' => Field::image('Foto'), 'texto' => Field::html('Texto')]),
        ]),
        'titulo_parceiros' => Field::heading('Título da lista de parceiros', 'Os parceiros vêm de "Topo, rodapé e contatos".'),
    ],
];
