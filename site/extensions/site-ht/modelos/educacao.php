<?php
return [
    'titulo' => 'Google for Education',
    'ordem' => 6,
    'campos' => [
        'intro' => Campo::grupo('Abertura', ['logo' => Campo::imagem('Logo'), 'titulo' => Campo::titulo('Título'), 'texto' => Campo::html('Texto'), 'imagem' => Campo::imagem('Imagem')]),
        'destaques' => Campo::lista('Números', 'Número', ['titulo' => Campo::texto('Destaque'), 'texto' => Campo::texto('Texto')], 'titulo'),
        'definicao' => Campo::grupo('O que é', [
            'titulo' => Campo::titulo('Título'),
            'texto' => Campo::html('Texto'),
            'botao' => Campo::botao(),
            'blocos' => Campo::lista('Ferramentas', 'Ferramenta', ['imagem' => Campo::imagem('Logo'), 'texto' => Campo::html('Texto')]),
        ]),
        'motivos' => Campo::grupo('Por que escolher', [
            'titulo' => Campo::titulo('Título'),
            'blocos' => Campo::lista('Motivos', 'Motivo', ['icone' => Campo::imagem('Ícone'), 'titulo' => Campo::texto('Título'), 'texto' => Campo::html('Texto')], 'titulo'),
        ]),
        'playlist' => Campo::grupo('Vídeos', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::titulo('Título'),
            'videos' => Campo::lista('Vídeos', 'Vídeo', ['titulo' => Campo::texto('Título'), 'link' => Campo::link('Link do YouTube'), 'duracao' => Campo::texto('Duração', 'Ex.: 1:43')], 'titulo'),
        ]),
        'case' => Campo::grupo('Case', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::titulo('Título'),
            'post' => Campo::post('Post do blog'),
            'video' => Campo::link('Vídeo do YouTube'),
            'botao' => Campo::botao(),
        ]),
    ],
];
