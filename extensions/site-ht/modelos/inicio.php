<?php
$icones = ['carrinho' => 'Carrinho', 'documento' => 'Documento', 'capelo' => 'Educação', 'parceria' => 'Aperto de mãos', 'predio' => 'Prédio', 'seta' => 'Seta'];

return [
    'titulo' => 'Página inicial',
    'ordem' => 1,
    'campos' => [
        'abertura' => Campo::grupo('Abertura', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::texto('Título', 'A última palavra aparece em azul.'),
            'texto' => Campo::longo('Texto'),
            'botao' => Campo::botao('Botão principal'),
            'botao_secundario' => Campo::botao('Botão secundário'),
            'marcas' => Campo::texto('Linha de parceiros', 'Ex.: Dell · HPE · HP · Google'),
            'foto_1' => Campo::imagem('Foto grande'),
            'foto_2' => Campo::imagem('Foto de cima'),
            'foto_3' => Campo::imagem('Foto de baixo'),
            'selo_valor' => Campo::texto('Selo: número', 'Ex.: +19 anos'),
            'selo_texto' => Campo::texto('Selo: texto'),
        ]),
        'atalhos' => Campo::lista('Atalhos', 'Atalho', [
            'icone' => Campo::selecao('Ícone', $icones),
            'titulo' => Campo::texto('Título'),
            'texto' => Campo::longo('Texto'),
            'link' => Campo::link('Link'),
            'rotulo' => Campo::texto('Texto do link', 'Ex.: Saiba mais'),
        ], 'titulo'),
        'numeros' => Campo::lista('Números', 'Número', [
            'valor' => Campo::texto('Número', 'Ex.: +50 mil'),
            'texto' => Campo::texto('Texto'),
        ], 'valor'),
        'solucoes' => Campo::grupo('Soluções', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::titulo('Título'),
            'texto' => Campo::html('Texto'),
            'link' => Campo::botao('Link para todas as soluções'),
            'itens' => Campo::lista('Cartões', 'Cartão', [
                'titulo' => Campo::texto('Título'),
                'texto' => Campo::longo('Texto'),
                'imagem' => Campo::imagem('Foto'),
                'link' => Campo::link('Link'),
            ], 'titulo', 'O primeiro ocupa o espaço maior.'),
        ]),
        'quem_somos' => Campo::grupo('Quem somos', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::titulo('Título'),
            'texto' => Campo::html('Texto'),
            'destaques' => Campo::lista('Destaques', 'Destaque', ['texto' => Campo::texto('Texto')], 'texto'),
            'video' => Campo::link('Vídeo (arquivo .mp4)', 'Endereço do vídeo.'),
            'botao' => Campo::botao(),
        ]),
        'parceiros' => Campo::grupo('Parceiros', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::titulo('Título', 'Os logos vêm de "Topo, rodapé e contatos".'),
            'link' => Campo::botao('Link abaixo dos logos'),
        ]),
        'cases' => Campo::grupo('Cases', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::titulo('Título', 'Os 3 posts mais recentes da categoria Cases aparecem sozinhos.'),
            'botao' => Campo::botao(),
        ]),
        'blog' => Campo::grupo('Últimas notícias', [
            'sobretitulo' => Campo::texto('Texto acima do título'),
            'titulo' => Campo::titulo('Título', 'Os 3 posts mais recentes do blog aparecem sozinhos.'),
            'botao' => Campo::botao(),
        ]),
    ],
];
