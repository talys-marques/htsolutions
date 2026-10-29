<?php
$itens = fn(string $rotulo, string $item) => Campo::lista($rotulo, $item, ['titulo' => Campo::texto('Nome'), 'imagem' => Campo::imagem('Ícone')], 'titulo');

return [
    'titulo' => 'Soluções e serviços',
    'ordem' => 2,
    'campos' => [
        'intro' => Campo::grupo('Abertura', ['titulo' => Campo::titulo('Título'), 'texto' => Campo::html('Texto'), 'imagem' => Campo::imagem('Imagem')]),
        'google' => Campo::grupo('Soluções Google', [
            'titulo' => Campo::titulo('Título'),
            'texto' => Campo::html('Texto'),
            'logos' => Campo::lista('Logos', 'Logo', ['imagem' => Campo::imagem('Logo')]),
            'botoes' => Campo::lista('Botões', 'Botão', ['texto' => Campo::texto('Texto'), 'link' => Campo::link('Link')], 'texto'),
        ]),
        'client' => Campo::grupo('Soluções Client', [
            'titulo' => Campo::titulo('Título'),
            'texto' => Campo::html('Texto'),
            'itens' => Campo::lista('Produtos', 'Produto', ['titulo' => Campo::texto('Título'), 'texto' => Campo::longo('Texto'), 'imagem' => Campo::imagem('Imagem')], 'titulo'),
        ]),
        'enterprise' => Campo::grupo('Soluções Enterprise', [
            'titulo' => Campo::titulo('Título'),
            'texto' => Campo::html('Texto'),
            'imagem' => Campo::imagem('Imagem'),
            'lista' => Campo::lista('Lista', 'Item', ['texto' => Campo::titulo('Texto', 'Use <strong>...</strong> para negrito.')], 'texto'),
        ]),
        'servicos_enterprise' => Campo::grupo('Serviços Enterprise', ['titulo' => Campo::titulo('Título'), 'texto' => Campo::html('Texto'), 'itens' => $itens('Serviços', 'Serviço')]),
        'servicos_client' => Campo::grupo('Serviços Client', ['titulo' => Campo::titulo('Título'), 'texto' => Campo::html('Texto'), 'itens' => $itens('Serviços', 'Serviço')]),
        'chamado' => Campo::grupo('Chamado técnico', ['titulo' => Campo::texto('Texto'), 'botao' => Campo::botao()]),
    ],
];
