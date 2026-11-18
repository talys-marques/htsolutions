<?php
return [
    'label' => 'Canais de transparência (Certigov)',
    'order' => 7,
    'fields' => [
        'intro' => Field::group('Abertura', ['titulo' => Field::heading('Título'), 'texto' => Field::textarea('Texto'), 'imagem' => Field::image('Imagem')]),
        'fabricantes' => Field::group('Fabricantes', [
            'imagem' => Field::image('Faixa de logos (computador)', 'Logos brancos: aparecem sobre a faixa azul-escura.'),
            'imagem_mobile' => Field::image('Faixa de logos (celular)'),
            'texto' => Field::textarea('Texto'),
        ]),
        'certificado' => Field::image('Certificado'),
        'comite' => Field::group('Comitê de Ética', ['titulo' => Field::text('Título'), 'texto' => Field::textarea('Texto'), 'imagem' => Field::image('Imagem')]),
        'diretor' => Field::group('Fale com o diretor', ['titulo' => Field::text('Título'), 'texto' => Field::textarea('Texto'), 'email' => Field::text('E-mail do diretor'), 'botao' => Field::text('Texto do botão')]),
        'etica' => Field::group('Canal de Ética', ['titulo' => Field::text('Título'), 'texto' => Field::html('Texto', 'Telefone e site do canal, com links.')]),
    ],
];
