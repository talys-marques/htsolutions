<?php
/**
 * Dados do site institucional da HT Solutions usados no topo e no rodapé do blog.
 * O site fica na raiz do domínio e o blog em /blog/, por isso os links do site são
 * caminhos a partir da raiz (/servicos/), sem url(). Mantenha igual a site/src/config.mjs.
 * HT_SITE_BASE (variável de ambiente) põe o site numa pasta, ex.: /htsolutions na
 * apresentação do GitHub Pages (site/scripts/blog-estatico.mjs).
 */
$base = rtrim((string) getenv('HT_SITE_BASE'), '/');
$s = fn(string $path) => $base . $path;
$atas = 'https://atas.htsolutions.com.br/a/atas/atas/';

return [
    'inicio' => $s('/'),
    'menu' => [
        ['titulo' => 'Soluções', 'href' => $s('/servicos/'), 'submenu' => [
            ['titulo' => 'Soluções para Educação', 'href' => $s('/educacao/')],
            ['titulo' => 'Google para Empresas', 'href' => $s('/googleparaempresas/')],
            ['titulo' => 'HP Inc', 'href' => $s('/hp/')],
        ]],
        ['titulo' => 'Serviços', 'href' => $s('/servicos/#servicos')],
        ['titulo' => 'Atas', 'href' => $s('/atas/'), 'submenu' => [['titulo' => 'Itens Disponíveis', 'href' => $atas]]],
        ['titulo' => 'Loja Online', 'href' => 'https://produtos.htsolutions.com.br/p/produtos/'],
        ['titulo' => 'Blog', 'href' => $s('/blog/'), 'atual' => true, 'submenu' => [['titulo' => 'Cases', 'href' => $s('/blog/category/cases/')]]],
        ['titulo' => 'Quem somos', 'href' => $s('/quem-somos/')],
        ['titulo' => 'Garantias', 'href' => $s('/consulta-garantias/')],
    ],
    'contato' => [
        'telefone' => ['texto' => '(51) 4020 1585', 'link' => 'tel:+555140201585'],
        'email' => 'evendas@htsolutions.com.br',
        'endereco' => 'Rodovia BR 116, 7070 · CEP 93950-000 · Dois Irmãos – RS',
        'horario' => 'Atendimento corporativo de segunda a quinta das 7h50 às 18h. Na sexta, das 7h50 às 17h.',
    ],
    'redes' => [
        'Facebook' => 'https://facebook.com/htsolutionsoficial',
        'LinkedIn' => 'https://linkedin.com/company/htsolutions',
        'Instagram' => 'https://instagram.com/htsolutionsbr',
    ],
    'rodape' => [
        'Institucional' => [
            'Quem somos' => $s('/quem-somos/'),
            'Serviços' => $s('/servicos/#servicos'),
            'Atas' => $s('/atas/'),
            'Cases' => $s('/blog/category/cases/'),
            'Blog' => $s('/blog/'),
        ],
        'Soluções' => [
            'Infraestrutura' => $s('/servicos/#enterprise'),
            'Redes' => $s('/servicos/#servicos'),
            'Computação pessoal' => $s('/servicos/#client'),
        ],
    ],
    'links' => [
        'Política de Privacidade' => $s('/politica-de-privacidade/'),
        'Trabalhe Conosco' => 'https://htsolutions.gupy.io/',
    ],
    // Formulário de contato da Emarsys (o mesmo das páginas do site: 1128, campo extra "Empresa")
    'emarsys' => ['acao' => 'https://link.htsolutions.com.br/u/register.php', 'cid' => '1090780807', 'f' => '1128'],
];
