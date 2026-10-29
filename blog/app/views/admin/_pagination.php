<?php
/** @var array $pagination  @var callable $link  fn(int $page): string */
if ($pagination['pages'] <= 1) {
    return;
}
$page = $pagination['page'];
?>
<nav class="pager">
  <?php if ($page > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e($link($page - 1)) ?>">← Anterior</a><?php endif ?>
  <span class="muted">Página <?= $page ?> de <?= $pagination['pages'] ?> · <?= $pagination['total'] ?> itens</span>
  <?php if ($page < $pagination['pages']): ?><a class="btn btn-ghost btn-sm" href="<?= e($link($page + 1)) ?>">Próxima →</a><?php endif ?>
</nav>
