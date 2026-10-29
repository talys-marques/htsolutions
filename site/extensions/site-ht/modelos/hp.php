<?php
return [
    'titulo' => 'HP Inc',
    'ordem' => 8,
    'descricao' => 'A vitrine de produtos abaixo do texto é da própria HP e se atualiza sozinha.',
    'campos' => [
        'titulo' => Campo::titulo('Título'),
        'texto' => Campo::longo('Texto'),
        'botao' => Campo::texto('Texto do botão'),
    ],
];
