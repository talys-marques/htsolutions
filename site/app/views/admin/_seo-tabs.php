<?php /** Abas das telas de SEO. @var string $tab 'general' | 'terms'  @var int $missing  endereços com 404 sem destino */ ?>
<nav class="tabs seo-tabs" aria-label="Seções de SEO">
  <a href="<?= e(url('/admin/seo')) ?>"<?= $tab === 'general' ? ' class="active" aria-current="page"' : '' ?>>Geral</a>
  <a href="<?= e(url('/admin/seo/terms')) ?>"<?= $tab === 'terms' ? ' class="active" aria-current="page"' : '' ?>>Categorias e tags</a>
  <a href="<?= e(url('/admin/redirects')) ?>">Não encontrados (404) <span class="count"><?= (int) $missing ?></span></a>
</nav>
