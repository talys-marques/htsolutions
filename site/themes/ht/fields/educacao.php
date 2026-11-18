<?php
return [
    'label' => 'Google for Education',
    'order' => 6,
    'fields' => [
        'intro' => Field::group('Abertura', ['logo' => Field::image('Logo'), 'titulo' => Field::heading('Título'), 'texto' => Field::html('Texto'), 'imagem' => Field::image('Imagem')]),
        'destaques' => Field::list('Números', 'Número', ['titulo' => Field::text('Destaque'), 'texto' => Field::text('Texto')], 'titulo'),
        'definicao' => Field::group('O que é', [
            'titulo' => Field::heading('Título'),
            'texto' => Field::html('Texto'),
            'botao' => ht_campo_botao(),
            'blocos' => Field::list('Ferramentas', 'Ferramenta', ['imagem' => Field::image('Logo'), 'texto' => Field::html('Texto')]),
        ]),
        'motivos' => Field::group('Por que escolher', [
            'titulo' => Field::heading('Título'),
            'blocos' => Field::list('Motivos', 'Motivo', ['icone' => Field::image('Ícone'), 'titulo' => Field::text('Título'), 'texto' => Field::html('Texto')], 'titulo'),
        ]),
        'playlist' => Field::group('Vídeos', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::heading('Título'),
            'videos' => Field::list('Vídeos', 'Vídeo', ['titulo' => Field::text('Título'), 'link' => Field::link('Link do YouTube'), 'duracao' => Field::text('Duração', 'Ex.: 1:43')], 'titulo'),
        ]),
        'case' => Field::group('Case', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::heading('Título'),
            'post' => Field::post('Post do blog'),
            'video' => Field::link('Vídeo do YouTube'),
            'botao' => ht_campo_botao(),
        ]),
    ],
];
