<?php
return [
    'label' => 'Google para Empresas',
    'order' => 5,
    'fields' => [
        'intro' => Field::group('Abertura', ['logo' => Field::image('Logo'), 'titulo' => Field::heading('Título'), 'texto' => Field::heading('Texto'), 'imagem' => Field::image('Imagem')]),
        'definicao' => Field::group('O que é', [
            'texto' => Field::html('Texto'),
            'blocos' => Field::list('Públicos', 'Público', ['titulo' => Field::text('Nome'), 'imagem' => Field::image('Foto')], 'titulo'),
        ]),
        'conteudo' => Field::list('Produtos', 'Produto', ['titulo' => Field::text('Título'), 'texto' => Field::html('Texto'), 'imagem' => Field::image('Imagem')], 'titulo'),
        'cases' => Field::group('Cases', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::heading('Título'),
            'posts' => Field::list('Posts', 'Post', ['post' => Field::post('Post do blog')]),
        ]),
        'ebook' => Field::group('E-book', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::heading('Título'),
            'texto' => Field::html('Texto'),
            'imagem' => Field::image('Capa'),
            'arquivo' => Field::link('Arquivo do e-book (PDF)', 'Envie o PDF em Mídia e cole o endereço aqui.'),
            'botao' => Field::text('Texto do botão'),
        ]),
        'blog' => Field::group('Últimas notícias', ['sobretitulo' => Field::text('Texto acima do título'), 'titulo' => Field::heading('Título')]),
    ],
];
