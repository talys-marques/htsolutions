<?php
/**
 * Página fixa (Sobre, Contato...).
 * @var array $post  @var bool $preview  @var bool $is_home  esta é a página escolhida como inicial
 */
$isHome = !empty($is_home);
?>
<?php if ($isHome) slot('home-top', get_defined_vars()) ?>
<?php if ($preview): ?>
  <div class="preview-bar">Pré-visualização · esta página ainda não está publicada</div>
<?php endif ?>

<article class="post page">
  <header class="post-header narrow">
    <h1 class="post-title"><?= e($post['title']) ?></h1>
  </header>
  <?php if ($post['cover']): ?>
    <figure class="post-cover"><?= image_tag($post['cover'], '(max-width: 1000px) 100vw, 960px', ['alt' => $post['cover_alt'], 'fetchpriority' => 'high']) ?></figure>
  <?php endif ?>
  <div class="prose narrow">
    <?= $post['content'] ?>
  </div>
  <?php slot('page-after-content', get_defined_vars()) ?>
</article>
<?php if ($isHome) slot('home-bottom', get_defined_vars()) ?>
