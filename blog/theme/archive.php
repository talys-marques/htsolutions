<?php
/**
 * Listagem de categoria, tag ou autor.
 * @var string $label  @var string $name  @var array $posts  @var array $pagination  @var callable $page_link
 * @var string $description  HTML da categoria/tag (Painel → SEO), só na primeira página
 */
$description = trim($description ?? '');
?>
<div class="wrap">
  <header class="archive-header">
    <p class="kicker"><?= e($label) ?></p>
    <h1><?= e($name) ?></h1>
    <?php // HTML escrito pelo admin no editor visual, como o conteúdo dos posts: vai sem e(). ?>
    <?php if ($description !== ''): ?><div class="archive-description prose"><?= $description ?></div><?php endif ?>
    <p class="muted"><?= (int) $pagination['total'] ?> <?= $pagination['total'] === 1 ? 'post' : 'posts' ?></p>
  </header>
  <div class="post-list">
    <?php foreach ($posts as $post): ?>
      <?php partial('card', ['post' => $post]) ?>
    <?php endforeach ?>
  </div>
  <?php partial('pagination', ['pagination' => $pagination, 'link' => $page_link]) ?>
</div>
