<?php
/**
 * Paginação das listagens.
 * @var array $pagination  @var callable $link  fn(int $page): string
 */
if ($pagination['pages'] <= 1) {
    return;
}
$page = $pagination['page'];
?>
<nav class="pagination" aria-label="Paginação">
  <?php if ($page > 1): ?>
    <a href="<?= e($link($page - 1)) ?>" rel="prev">← Mais recentes</a>
  <?php else: ?><span></span><?php endif ?>
  <span class="pagination-info">Página <?= $page ?> de <?= $pagination['pages'] ?></span>
  <?php if ($page < $pagination['pages']): ?>
    <a href="<?= e($link($page + 1)) ?>" rel="next">Mais antigos →</a>
  <?php else: ?><span></span><?php endif ?>
</nav>
