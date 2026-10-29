<?php
/*
 * Configurações técnicas do sistema.
 *
 * Título do blog, menu, cores, códigos de analytics etc. são editados no
 * painel (/admin/settings) e ficam salvos em data/settings.json.
 */
return [
    // Mostra erros detalhados na tela. Fica ligado automaticamente no
    // servidor de desenvolvimento do PHP (php -S) e desligado em produção.
    'debug' => PHP_SAPI === 'cli-server',

    // Pasta dos dados (posts em JSON, usuários, sessões, logs).
    // Precisa ter permissão de escrita e NÃO pode ficar acessível pela web.
    // Pode ser movida para fora da pasta do site, ex.: '/home/usuario/blog-data'.
    // A variável de ambiente BLOG_DATA_DIR, se existir, tem prioridade (útil para testes).
    'data_dir' => getenv('BLOG_DATA_DIR') ?: __DIR__ . '/data',

    // Tamanho máximo de cada arquivo enviado, em MB.
    // Os limites do PHP (upload_max_filesize e post_max_size) também valem;
    // eles ficam em public/.user.ini (hospedagem) e no run.bat (seu computador).
    'max_upload_mb' => 20,

    // Imagens mais largas que isso são reduzidas no envio (0 = não reduzir).
    'image_max_width' => 2000,

    // Quantas versões anteriores de cada post são guardadas.
    'revisions' => 20,
];
