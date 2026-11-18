<?php
return [
    'label' => 'HP Inc',
    'order' => 8,
    'description' => 'A vitrine de produtos abaixo do texto é da própria HP e se atualiza sozinha.',
    'fields' => [
        'titulo' => Field::heading('Título'),
        'texto' => Field::textarea('Texto'),
        'botao' => Field::text('Texto do botão'),
    ],
];
