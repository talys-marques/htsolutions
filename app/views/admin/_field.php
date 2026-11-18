<?php
/**
 * Um campo do formulário de Conteúdo do site (content-edit.php), montado a partir da definição
 * (tipos em app/Field.php). Grupos e listas chamam este arquivo de novo para cada campo de dentro.
 * Os campos não têm name: admin.js lê o valor de cada [data-field] (a chave) pelo data-kind (o tipo).
 * @var string $key  @var array $field  definição  @var mixed $value  valor atual (Field::fill)
 */
$kind = $field['type'] ?? 'text';
$attrs = 'data-field="' . e($key) . '" data-kind="' . e($kind) . '"';
$label = '<span class="cf-label">' . e($field['label'] ?? $key) . '</span>';
$hint = ($field['hint'] ?? '') !== '' ? '<span class="hint">' . e($field['hint']) . '</span>' : '';

// Campos de dentro de um grupo ou de um item de lista.
$children = function (array $fields, $values): string {
    $html = '';
    foreach ($fields as $k => $f) {
        $html .= render_file(__FILE__, ['key' => (string) $k, 'field' => $f, 'value' => is_array($values) ? ($values[$k] ?? null) : null]);
    }
    return $html;
};

// Um item de lista: fechado, mostra o nome (o valor do campo 'summary', que admin.js atualiza) e os botões.
$item = function (array $values) use ($field, $children): string {
    ob_start(); ?>
    <details class="cf-item">
      <summary>
        <span class="cf-item-name"><?= e($field['item']) ?></span>
        <span class="cf-item-actions">
          <button type="button" class="btn btn-sm btn-ghost" data-cf-up title="Mover para cima" aria-label="Mover para cima">↑</button>
          <button type="button" class="btn btn-sm btn-ghost" data-cf-down title="Mover para baixo" aria-label="Mover para baixo">↓</button>
          <button type="button" class="btn btn-sm btn-ghost" data-cf-remove>Remover</button>
        </span>
      </summary>
      <div class="cf-item-fields"><?= $children($field['fields'], $values) ?></div>
    </details>
    <?php return (string) ob_get_clean();
};
?>
<?php if ($kind === 'group'): ?>
  <fieldset class="cf-group" <?= $attrs ?>>
    <legend><?= e($field['label']) ?></legend>
    <?= $hint ?>
    <?= $children($field['fields'], $value) ?>
  </fieldset>

<?php elseif ($kind === 'list'): ?>
  <div class="cf-list" <?= $attrs ?> data-summary="<?= e($field['summary'] ?? '') ?>">
    <div class="cf-list-head"><?= $label . $hint ?></div>
    <div class="cf-items">
      <?php foreach (is_array($value) ? $value : [] as $values): ?><?= $item((array) $values) ?><?php endforeach ?>
    </div>
    <template><?= $item(Field::fill($field['fields'], [])) ?></template>
    <button type="button" class="btn btn-sm" data-cf-add>+ Adicionar <?= e(mb_strtolower($field['item'])) ?></button>
  </div>

<?php elseif ($kind === 'html'): ?>
  <div class="field cf-field" <?= $attrs ?>>
    <?= $label . $hint ?>
    <?= render_file(__DIR__ . '/_editor.php', ['name' => '', 'value' => (string) $value, 'placeholder' => '', 'compact' => true]) ?>
  </div>

<?php elseif ($kind === 'image' || $kind === 'file'): ?>
  <?php // Mesmo campo da capa do post (admin.js, "Campos de imagem"); data-files = qualquer arquivo da Mídia. ?>
  <div class="field cf-field cf-media" <?= $attrs ?> data-image-field<?= $kind === 'file' ? ' data-files' : '' ?>>
    <?= $label . $hint ?>
    <div class="image-preview"></div>
    <input type="hidden" value="<?= e((string) $value) ?>">
    <div class="row">
      <button type="button" class="btn btn-sm" data-image-pick><?= $kind === 'file' ? 'Escolher arquivo' : 'Escolher imagem' ?></button>
      <button type="button" class="btn btn-sm btn-ghost" data-image-remove>Remover</button>
    </div>
  </div>

<?php elseif ($kind === 'textarea'): ?>
  <label class="field cf-field" <?= $attrs ?>>
    <?= $label . $hint ?>
    <textarea rows="3"><?= e((string) $value) ?></textarea>
  </label>

<?php elseif ($kind === 'select'): ?>
  <label class="field cf-field" <?= $attrs ?>>
    <?= $label . $hint ?>
    <select>
      <?php foreach ($field['choices'] as $v => $text): ?>
        <option value="<?= e($v) ?>"<?= (string) $v === (string) $value ? ' selected' : '' ?>><?= e($text) ?></option>
      <?php endforeach ?>
    </select>
  </label>

<?php elseif ($kind === 'post'): ?>
  <?php
  // Os 300 posts mais recentes, e o escolhido mesmo que seja mais antigo.
  $posts = array_slice(Posts::query(['type' => 'post', 'public' => true]), 0, 300);
  if ($value !== '' && !in_array($value, array_column($posts, 'slug'), true) && ($chosen = Posts::findBySlug((string) $value))) {
      $posts[] = $chosen;
  }
  ?>
  <label class="field cf-field" <?= $attrs ?>>
    <?= $label . $hint ?>
    <select>
      <option value="">(nenhum)</option>
      <?php foreach ($posts as $p): ?>
        <option value="<?= e($p['slug']) ?>"<?= $p['slug'] === $value ? ' selected' : '' ?>><?= e($p['title']) ?> · <?= e(format_date($p['published_at'])) ?></option>
      <?php endforeach ?>
    </select>
  </label>

<?php else: // text, heading, link ?>
  <label class="field cf-field" <?= $attrs ?>>
    <?= $label . $hint ?>
    <input value="<?= e((string) $value) ?>" autocomplete="off"<?= $kind === 'link' ? ' inputmode="url" spellcheck="false"' : '' ?>>
  </label>
<?php endif ?>
