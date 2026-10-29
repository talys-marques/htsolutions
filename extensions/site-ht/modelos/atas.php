<?php
return [
    'titulo' => 'Atas de Registro de Preços',
    'ordem' => 4,
    'campos' => [
        'resumo' => Campo::html('Texto da abertura'),
        'texto' => Campo::html('Texto'),
        'titulo_atas' => Campo::texto('Título ao lado do botão'),
        'botao_atas' => Campo::botao('Botão das atas'),
        'duvida' => Campo::grupo('Está em dúvida?', ['titulo' => Campo::titulo('Título'), 'texto' => Campo::html('Texto'), 'botao' => Campo::botao()]),
        'saiba_mais' => Campo::grupo('Por que a HT', ['sobretitulo' => Campo::texto('Texto acima do título'), 'titulo' => Campo::titulo('Título'), 'texto' => Campo::html('Texto'), 'imagem' => Campo::imagem('Imagem')]),
        'transparencia' => Campo::grupo('Transparência', ['sobretitulo' => Campo::texto('Texto acima do título'), 'titulo' => Campo::titulo('Título'), 'texto' => Campo::longo('Texto'), 'botao' => Campo::botao()]),
    ],
];
