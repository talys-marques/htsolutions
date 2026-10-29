<?php
/**
 * Resultados da busca (/?s=termo).
 * @var string $query  @var array $posts  @var array $pagination  @var callable $page_link
 */
?>
<div class="wrap">
  <header class="archive-header">
    <p class="kicker">Busca</p>
    <form class="search-big" action="<?= e(url('/')) ?>" method="get" role="search" data-suggest="<?= e(url('/search.json')) ?>">
      <input type="search" name="s" value="<?= e($query) ?>" placeholder="O que você procura?" aria-label="Buscar" autofocus>
      <button>Buscar</button>
    </form>
    <?php if ($query !== ''): ?>
      <p class="muted"><?= (int) $pagination['total'] ?> resultado(s) para “<?= e($query) ?>”</p>
    <?php endif ?>
  </header>
  <?php if ($query !== '' && !$posts): ?>
    <div class="empty"><p>Nada encontrado. Tente outras palavras.</p></div>
  <?php endif ?>
  <div class="post-list">
    <?php foreach ($posts as $post): ?>
      <?php partial('card', ['post' => $post]) ?>
    <?php endforeach ?>
  </div>
  <?php partial('pagination', ['pagination' => $pagination, 'link' => $page_link]) ?>
</div>
