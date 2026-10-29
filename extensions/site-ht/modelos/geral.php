<?php
// Topo, rodapé, contatos e parceiros: aparecem em todas as páginas (página em rascunho site-geral)
$links = fn(string $rotulo) => Campo::lista($rotulo, 'Link', ['titulo' => Campo::texto('Texto'), 'link' => Campo::link('Link')], 'titulo');

return [
    'titulo' => 'Topo, rodapé e contatos',
    'descricao' => 'Menu do topo, contatos, links do rodapé, redes sociais e a lista de parceiros (usada na página inicial e em Quem somos).',
    'ordem' => 100,
    'geral' => true,
    'campos' => [
        'menu' => Campo::lista('Menu do topo', 'Item do menu', [
            'titulo' => Campo::texto('Texto'),
            'link' => Campo::link('Link'),
            'submenu' => Campo::lista('Submenu', 'Subitem', ['titulo' => Campo::texto('Texto'), 'link' => Campo::link('Link')], 'titulo', 'Opcional: abre ao passar o mouse.'),
        ], 'titulo'),
        'contato' => Campo::grupo('Contato', [
            'titulo' => Campo::texto('Chamada do formulário', 'Título da faixa de contato no fim das páginas.'),
            'texto' => Campo::longo('Texto abaixo da chamada'),
            'telefone' => Campo::texto('Telefone', 'Ex.: (51) 4020 1585'),
            'email' => Campo::texto('E-mail'),
            'horario' => Campo::longo('Horário de atendimento'),
            'endereco' => Campo::longo('Endereço'),
        ]),
        'suporte' => Campo::grupo('Suporte técnico (consulta de garantias)', [
            'telefone' => Campo::texto('Telefone'),
            'email' => Campo::texto('E-mail'),
            'horario' => Campo::texto('Horário'),
        ]),
        'redes' => Campo::grupo('Redes sociais', [
            'facebook' => Campo::link('Facebook'),
            'linkedin' => Campo::link('LinkedIn'),
            'instagram' => Campo::link('Instagram'),
        ]),
        'rodape' => Campo::grupo('Rodapé', [
            'texto' => Campo::longo('Texto ao lado do logo'),
            'institucional' => $links('Coluna Institucional'),
            'solucoes' => $links('Coluna Soluções'),
            'links' => $links('Links abaixo das redes sociais'),
        ]),
        'newsletter' => Campo::grupo('Newsletter', [
            'titulo' => Campo::texto('Título'),
            'texto' => Campo::longo('Texto'),
        ]),
        'parceiros' => Campo::lista('Parceiros', 'Parceiro', [
            'nome' => Campo::texto('Nome da marca'),
            'logo' => Campo::imagem('Logo'),
            'texto' => Campo::longo('O que fazemos com ele', 'Aparece em Quem somos.'),
            'link' => Campo::link('Link do "Saiba mais"'),
        ], 'nome'),
    ],
];
