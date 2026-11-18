<?php
$icones = ['carrinho' => 'Carrinho', 'documento' => 'Documento', 'capelo' => 'Educação', 'parceria' => 'Aperto de mãos', 'predio' => 'Prédio', 'seta' => 'Seta'];

return [
    'label' => 'Página inicial',
    'order' => 1,
    'fields' => [
        'abertura' => Field::group('Abertura', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::text('Título', 'A última palavra aparece em azul.'),
            'texto' => Field::textarea('Texto'),
            'botao' => ht_campo_botao('Botão principal'),
            'botao_secundario' => ht_campo_botao('Botão secundário'),
            'marcas' => Field::text('Linha de parceiros', 'Ex.: Dell · HPE · HP · Google'),
            'foto_1' => Field::image('Foto grande'),
            'foto_2' => Field::image('Foto de cima'),
            'foto_3' => Field::image('Foto de baixo'),
            'selo_valor' => Field::text('Selo: número', 'Ex.: +19 anos'),
            'selo_texto' => Field::text('Selo: texto'),
        ]),
        'atalhos' => Field::list('Atalhos', 'Atalho', [
            'icone' => Field::select('Ícone', $icones),
            'titulo' => Field::text('Título'),
            'texto' => Field::textarea('Texto'),
            'link' => Field::link('Link'),
            'rotulo' => Field::text('Texto do link', 'Ex.: Saiba mais'),
        ], 'titulo'),
        'numeros' => Field::list('Números', 'Número', [
            'valor' => Field::text('Número', 'Ex.: +50 mil'),
            'texto' => Field::text('Texto'),
        ], 'valor'),
        'solucoes' => Field::group('Soluções', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::heading('Título'),
            'texto' => Field::html('Texto'),
            'link' => ht_campo_botao('Link para todas as soluções'),
            'itens' => Field::list('Cartões', 'Cartão', [
                'titulo' => Field::text('Título'),
                'texto' => Field::textarea('Texto'),
                'imagem' => Field::image('Foto'),
                'link' => Field::link('Link'),
            ], 'titulo', 'O primeiro ocupa o espaço maior.'),
        ]),
        'quem_somos' => Field::group('Quem somos', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::heading('Título'),
            'texto' => Field::html('Texto'),
            'destaques' => Field::list('Destaques', 'Destaque', ['texto' => Field::text('Texto')], 'texto'),
            'video' => Field::link('Vídeo (arquivo .mp4)', 'Endereço do vídeo.'),
            'botao' => ht_campo_botao(),
        ]),
        'parceiros' => Field::group('Parceiros', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::heading('Título', 'Os logos vêm de "Topo, rodapé e contatos".'),
            'link' => ht_campo_botao('Link abaixo dos logos'),
        ]),
        'cases' => Field::group('Cases', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::heading('Título', 'Os 3 posts mais recentes da categoria Cases aparecem sozinhos.'),
            'botao' => ht_campo_botao(),
        ]),
        'blog' => Field::group('Últimas notícias', [
            'sobretitulo' => Field::text('Texto acima do título'),
            'titulo' => Field::heading('Título', 'Os 3 posts mais recentes do blog aparecem sozinhos.'),
            'botao' => ht_campo_botao(),
        ]),
    ],
];
