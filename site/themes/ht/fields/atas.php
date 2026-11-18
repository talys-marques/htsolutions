<?php
return [
    'label' => 'Atas de Registro de Preços',
    'order' => 4,
    'fields' => [
        'resumo' => Field::html('Texto da abertura'),
        'texto' => Field::html('Texto'),
        'titulo_atas' => Field::text('Título ao lado do botão'),
        'botao_atas' => ht_campo_botao('Botão das atas'),
        'duvida' => Field::group('Está em dúvida?', ['titulo' => Field::heading('Título'), 'texto' => Field::html('Texto'), 'botao' => ht_campo_botao()]),
        'saiba_mais' => Field::group('Por que a HT', ['sobretitulo' => Field::text('Texto acima do título'), 'titulo' => Field::heading('Título'), 'texto' => Field::html('Texto'), 'imagem' => Field::image('Imagem')]),
        'transparencia' => Field::group('Transparência', ['sobretitulo' => Field::text('Texto acima do título'), 'titulo' => Field::heading('Título'), 'texto' => Field::textarea('Texto'), 'botao' => ht_campo_botao()]),
    ],
];
