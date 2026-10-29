<?php
return [
    'titulo' => 'Canais de transparência (Certigov)',
    'ordem' => 7,
    'campos' => [
        'intro' => Campo::grupo('Abertura', ['titulo' => Campo::titulo('Título'), 'texto' => Campo::longo('Texto'), 'imagem' => Campo::imagem('Imagem')]),
        'fabricantes' => Campo::grupo('Fabricantes', [
            'imagem' => Campo::imagem('Faixa de logos (computador)', 'Logos brancos: aparecem sobre a faixa azul-escura.'),
            'imagem_mobile' => Campo::imagem('Faixa de logos (celular)'),
            'texto' => Campo::longo('Texto'),
        ]),
        'certificado' => Campo::imagem('Certificado'),
        'comite' => Campo::grupo('Comitê de Ética', ['titulo' => Campo::texto('Título'), 'texto' => Campo::longo('Texto'), 'imagem' => Campo::imagem('Imagem')]),
        'diretor' => Campo::grupo('Fale com o diretor', ['titulo' => Campo::texto('Título'), 'texto' => Campo::longo('Texto'), 'email' => Campo::texto('E-mail do diretor'), 'botao' => Campo::texto('Texto do botão')]),
        'etica' => Campo::grupo('Canal de Ética', ['titulo' => Campo::texto('Título'), 'texto' => Campo::html('Texto', 'Telefone e site do canal, com links.')]),
    ],
];
