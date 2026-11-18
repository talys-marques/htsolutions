<?php
/**
 * Configuração técnica do tema da HT Solutions. O conteúdo editável (menu, contatos, rodapé,
 * parceiros) fica em Conteúdo do site → "Topo, rodapé e contatos".
 *
 * Formulários da Emarsys (link.htsolutions.com.br): cada página manda para o formulário dela,
 * que define a lista e quem recebe o contato. 'campo' é o campo extra de cada formulário.
 */
return [
    'emarsys' => [
        'acao' => 'https://link.htsolutions.com.br/u/register.php',
        'cid' => '1090780807',
        'formularios' => [
            'empresa' => ['f' => '1128', 'campo' => ['inp_18', 'Empresa'], 'mensagem' => 'inp_15463'],
            'atas' => ['f' => '1193', 'campo' => ['inp_15895', 'Órgão Público'], 'mensagem' => 'inp_15896'],
            'educacao' => ['f' => '1129', 'campo' => ['inp_15462', 'Instituição de Ensino'], 'mensagem' => 'inp_15464'],
            'enterprise' => ['f' => '1194', 'campo' => ['inp_18', 'Empresa'], 'mensagem' => 'inp_15898'],
        ],
    ],
    // Formulário de cada página do site (modelo); as demais e o blog usam 'empresa'
    'formulario_por_modelo' => [
        'atas' => 'atas',
        'certigov' => 'atas',
        'educacao' => 'educacao',
        'googleparaempresas' => 'enterprise',
    ],
];
