<?php
return [
    'label' => 'Consulta de garantia',
    'order' => 9,
    'description' => 'Os contatos do suporte vêm de "Topo, rodapé e contatos". A lista de equipamentos é o arquivo public/assets/db_943hf3.bin.',
    'fields' => [
        'texto' => Field::textarea('Texto da abertura'),
        'suporte_titulo' => Field::text('Título do bloco de suporte'),
    ],
];
