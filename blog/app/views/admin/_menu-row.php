<?php
/** Um item do menu em Configurações. @var int|string $i  @var array $row  Menu::describe() + sub */
$name = fn(string $field) => e("menu[$i][$field]");
?>
<li class="menu-row<?= $row['sub'] ? ' is-sub' : '' ?>" data-menu-row>
  <input type="hidden" name="<?= $name('type') ?>" value="<?= e($row['type']) ?>" data-menu-type>
  <input type="hidden" name="<?= $name('sub') ?>" value="<?= $row['sub'] ? '1' : '' ?>" data-menu-sub>
  <span class="menu-handle" data-menu-handle title="Arraste para mudar a ordem" aria-hidden="true"></span>
  <div class="menu-main">
    <div class="menu-target">
      <span class="menu-type" data-menu-type-label><?= e($row['type_label']) ?></span>
      <?php if ($row['type'] === 'link'): ?>
        <input name="<?= $name('target') ?>" value="<?= e($row['target']) ?>" placeholder="https://... (vazio = só título)" aria-label="Endereço do link" autocomplete="off" spellcheck="false" data-menu-target>
      <?php else: ?>
        <input type="hidden" name="<?= $name('target') ?>" value="<?= e($row['target']) ?>" data-menu-target>
        <span class="menu-title" data-menu-title><?= e($row['title']) ?></span>
      <?php endif ?>
    </div>
    <input class="menu-label" name="<?= $name('label') ?>" value="<?= e($row['label']) ?>" placeholder="<?= e($row['auto'] ?: 'Nome no menu') ?>" aria-label="Nome no menu" autocomplete="off" data-menu-label>
    <?php // Escondida em "Categorias" e em link sem endereço, que só abrem a lista (admin.js atualiza ao digitar). ?>
    <label class="menu-newtab" data-menu-newtab<?= $row['type'] === 'categories' || ($row['type'] === 'link' && $row['target'] === '') ? ' hidden' : '' ?>>
      <input type="checkbox" name="<?= $name('new_tab') ?>" value="1"<?= $row['new_tab'] ? ' checked' : '' ?>> Nova aba
    </label>
    <span class="hint menu-note" data-menu-note<?= $row['note'] ? '' : ' hidden' ?>><?= e($row['note']) ?></span>
  </div>
  <div class="menu-actions">
    <button type="button" class="btn btn-sm btn-ghost" data-menu-move="up" title="Subir" aria-label="Subir">↑</button>
    <button type="button" class="btn btn-sm btn-ghost" data-menu-move="down" title="Descer" aria-label="Descer">↓</button>
    <button type="button" class="btn btn-sm btn-ghost" data-menu-indent>→</button>
    <button type="button" class="btn btn-sm btn-ghost" data-menu-remove title="Remover" aria-label="Remover">✕</button>
  </div>
</li>
