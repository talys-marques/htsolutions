<?php
/**
 * Página de apresentação: abertura com botões, blocos de texto e imagem, cartões, perguntas
 * frequentes e uma chamada no fim. Várias páginas usam este modelo (campanhas, serviços...).
 * Template: theme/templates/landing.php · CSS: "Página de apresentação" em public/assets/site.css.
 * Também é o exemplo de modelo para quem cria os de um site (themes/README.md, "Páginas com campos").
 */
return [
    'label' => 'Página de apresentação',
    'description' => 'Para campanhas, serviços e produtos. Seção sem conteúdo não aparece na página.',
    'order' => 90,
    'repeatable' => true,
    'fields' => [
        'hero' => Field::group('Abertura', [
            'eyebrow' => Field::text('Texto acima do título', 'Opcional. O título é o título da página.'),
            'text' => Field::textarea('Texto abaixo do título'),
            'image' => Field::image('Imagem', 'Fica ao lado do texto (no celular, embaixo).'),
            'image_alt' => Field::text('Descrição da imagem', 'Para o Google e para quem usa leitor de tela.'),
            'button' => Field::button('Botão principal'),
            'button_2' => Field::button('Botão secundário'),
        ]),
        'blocks' => Field::list('Blocos de texto e imagem', 'Bloco', [
            'title' => Field::text('Título'),
            'text' => Field::html('Texto'),
            'image' => Field::image('Imagem'),
            'image_alt' => Field::text('Descrição da imagem'),
            'side' => Field::select('Lado da imagem', ['right' => 'Direita', 'left' => 'Esquerda']),
        ], 'title', 'Cada bloco tem um texto e, se quiser, uma imagem ao lado.'),
        'cards' => Field::group('Cartões', [
            'title' => Field::text('Título da seção'),
            'items' => Field::list('Cartões', 'Cartão', [
                'image' => Field::image('Imagem', 'Opcional. Fica no alto do cartão.'),
                'title' => Field::text('Título'),
                'text' => Field::textarea('Texto'),
                'link' => Field::link('Link', 'Opcional: o cartão inteiro leva para este endereço.'),
            ], 'title', 'Benefícios, serviços, produtos, unidades...'),
        ]),
        'faq' => Field::group('Perguntas frequentes', [
            'title' => Field::text('Título da seção'),
            'items' => Field::list('Perguntas', 'Pergunta', [
                'question' => Field::text('Pergunta'),
                'answer' => Field::html('Resposta'),
            ], 'question'),
        ]),
        'cta' => Field::group('Chamada no fim da página', [
            'title' => Field::heading('Título'),
            'text' => Field::textarea('Texto'),
            'button' => Field::button(),
        ]),
    ],
];
