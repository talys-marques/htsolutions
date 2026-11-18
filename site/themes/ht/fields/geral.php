<?php
// Topo, rodapé, contatos e parceiros: aparecem em todas as páginas (página em rascunho site-geral)
$links = fn(string $rotulo) => Field::list($rotulo, 'Link', ['titulo' => Field::text('Texto'), 'link' => Field::link('Link')], 'titulo');

return [
    'label' => 'Topo, rodapé e contatos',
    'description' => 'Menu do topo, contatos, links do rodapé, redes sociais e a lista de parceiros (usada na página inicial e em Quem somos).',
    'order' => 100,
    'shared' => true,
    'fields' => [
        'menu' => Field::list('Menu do topo', 'Item do menu', [
            'titulo' => Field::text('Texto'),
            'link' => Field::link('Link'),
            'submenu' => Field::list('Submenu', 'Subitem', ['titulo' => Field::text('Texto'), 'link' => Field::link('Link')], 'titulo', 'Opcional: abre ao passar o mouse.'),
        ], 'titulo'),
        'contato' => Field::group('Contato', [
            'titulo' => Field::text('Chamada do formulário', 'Título da faixa de contato no fim das páginas.'),
            'texto' => Field::textarea('Texto abaixo da chamada'),
            'telefone' => Field::text('Telefone', 'Ex.: (51) 4020 1585'),
            'email' => Field::text('E-mail'),
            'horario' => Field::textarea('Horário de atendimento'),
            'endereco' => Field::textarea('Endereço'),
        ]),
        'suporte' => Field::group('Suporte técnico (consulta de garantias)', [
            'telefone' => Field::text('Telefone'),
            'email' => Field::text('E-mail'),
            'horario' => Field::text('Horário'),
        ]),
        'redes' => Field::group('Redes sociais', [
            'facebook' => Field::link('Facebook'),
            'linkedin' => Field::link('LinkedIn'),
            'instagram' => Field::link('Instagram'),
        ]),
        'rodape' => Field::group('Rodapé', [
            'texto' => Field::textarea('Texto ao lado do logo'),
            'institucional' => $links('Coluna Institucional'),
            'solucoes' => $links('Coluna Soluções'),
            'links' => $links('Links abaixo das redes sociais'),
        ]),
        'newsletter' => Field::group('Newsletter', [
            'titulo' => Field::text('Título'),
            'texto' => Field::textarea('Texto'),
        ]),
        'parceiros' => Field::list('Parceiros', 'Parceiro', [
            'nome' => Field::text('Nome da marca'),
            'logo' => Field::image('Logo'),
            'texto' => Field::textarea('O que fazemos com ele', 'Aparece em Quem somos.'),
            'link' => Field::link('Link do "Saiba mais"'),
        ], 'nome'),
    ],
];
