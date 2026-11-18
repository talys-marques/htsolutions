<?php
$itens = fn(string $rotulo, string $item) => Field::list($rotulo, $item, ['titulo' => Field::text('Nome'), 'imagem' => Field::image('Ícone')], 'titulo');

return [
    'label' => 'Soluções e serviços',
    'order' => 2,
    'fields' => [
        'intro' => Field::group('Abertura', ['titulo' => Field::heading('Título'), 'texto' => Field::html('Texto'), 'imagem' => Field::image('Imagem')]),
        'google' => Field::group('Soluções Google', [
            'titulo' => Field::heading('Título'),
            'texto' => Field::html('Texto'),
            'logos' => Field::list('Logos', 'Logo', ['imagem' => Field::image('Logo')]),
            'botoes' => Field::list('Botões', 'Botão', ['texto' => Field::text('Texto'), 'link' => Field::link('Link')], 'texto'),
        ]),
        'client' => Field::group('Soluções Client', [
            'titulo' => Field::heading('Título'),
            'texto' => Field::html('Texto'),
            'itens' => Field::list('Produtos', 'Produto', ['titulo' => Field::text('Título'), 'texto' => Field::textarea('Texto'), 'imagem' => Field::image('Imagem')], 'titulo'),
        ]),
        'enterprise' => Field::group('Soluções Enterprise', [
            'titulo' => Field::heading('Título'),
            'texto' => Field::html('Texto'),
            'imagem' => Field::image('Imagem'),
            'lista' => Field::list('Lista', 'Item', ['texto' => Field::heading('Texto', 'Use <strong>...</strong> para negrito.')], 'texto'),
        ]),
        'servicos_enterprise' => Field::group('Serviços Enterprise', ['titulo' => Field::heading('Título'), 'texto' => Field::html('Texto'), 'itens' => $itens('Serviços', 'Serviço')]),
        'servicos_client' => Field::group('Serviços Client', ['titulo' => Field::heading('Título'), 'texto' => Field::html('Texto'), 'itens' => $itens('Serviços', 'Serviço')]),
        'chamado' => Field::group('Chamado técnico', ['titulo' => Field::text('Texto'), 'botao' => ht_campo_botao()]),
    ],
];
