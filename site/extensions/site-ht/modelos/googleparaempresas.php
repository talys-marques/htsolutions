<?php
return [
    'titulo' => 'Google para Empresas',
    'ordem' => 5,
    'campos' => [
        'intro' => Campo::grupo('Abertura', ['logo' => Campo::imagem('Logo'), 'titulo' => Campo::titulo('Título'), 'texto' => Campo::titulo('Texto'), 'imagem' => Campo::imagem('Imagem')]),
        'definicao' => Campo::grupo('O que é', [
            'texto' => Campo::html('Texto'),
            'blocos' => Campo::lista('Públicos', 'Público', ['titulo' => Campo::texto('Nome'), 'imagem' => Campo::imagem('Foto')], 'titulo'),
        ]),
        'conteudo' => Campo::lista('Produtos', 'Produto', ['titulo' => Campo::texto('Título'), 'texto' => Campo::html('Texto'), 'imagem' => Campo::imagem('Imagem')], 'titulo'),
        'cases' => Campo::grupo('Cases', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::titulo('Título'),
            'posts' => Campo::lista('Posts', 'Post', ['post' => Campo::post('Post do blog')]),
        ]),
        'ebook' => Campo::grupo('E-book', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::titulo('Título'),
            'texto' => Campo::html('Texto'),
            'imagem' => Campo::imagem('Capa'),
            'arquivo' => Campo::link('Arquivo do e-book (PDF)', 'Envie o PDF em Mídia e cole o endereço aqui.'),
            'botao' => Campo::texto('Texto do botão'),
        ]),
        'blog' => Campo::grupo('Últimas notícias', ['sobretitulo' => Campo::texto('Texto acima do título'), 'titulo' => Campo::titulo('Título')]),
    ],
];
