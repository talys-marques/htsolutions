<?php
/**
 * Temas → Personalizar: formulário montado a partir das "options" do theme.json.
 * Cada tipo de Themes::OPTION_TYPES tem um campo aqui.
 * @var array $theme  @var array $values  Themes::options()  @var array $categories  nomes  @var array|null $previewItem
 */
$groups = [];
foreach ($theme['options'] as $key => $field) {
    $groups[$field['group'] ?: 'Opções'][$key] = $field;
}
$host = parse_url(site_url(), PHP_URL_HOST) ?: 'meublog.com.br';
$frame = url('/admin/themes/' . $theme['slug'] . '/frame');
$isActive = $theme['slug'] === Themes::activeSlug();
?>
<div class="page-head">
  <h1>Personalizar: <?= e($theme['name']) ?></h1>
  <div class="head-actions">
    <a class="btn btn-ghost" href="<?= e(url('/admin/themes')) ?>">← Temas</a>
    <?php if (!$isActive): ?>
      <a class="btn btn-ghost" href="<?= e(url('/admin/themes/preview/' . $theme['slug'])) ?>" target="_blank" rel="noopener">Pré-visualizar no site ↗</a>
    <?php endif ?>
  </div>
</div>
<?php if (!$isActive): ?>
  <p class="muted">Este tema não está ativo. As opções ficam guardadas e aparecem no site quando ele for ativado em Temas.</p>
<?php endif ?>

<form method="post" class="settings">
  <?= csrf_field() ?>
  <div class="panel sections">
    <?php foreach ($groups as $label => $fields): ?>
      <section class="section">
        <header class="section-head"><h2><?= e($label) ?></h2></header>
        <div class="stack">
          <?php foreach ($fields as $key => $f): ?>
            <?php
            $name = 'options[' . $key . ']';
            $value = $values[$key] ?? $f['default'];
            $hint = $f['hint'] !== '' ? '<span class="hint">' . e($f['hint']) . '</span>' : '';
            $placeholder = $f['placeholder'] !== '' ? ' placeholder="' . e($f['placeholder']) . '"' : '';
            ?>
            <?php if ($f['type'] === 'checkbox'): ?>
              <div class="field">
                <label class="check"><input type="checkbox" name="<?= e($name) ?>"<?= $value ? ' checked' : '' ?>> <?= e($f['label']) ?></label>
                <?= $hint ?>
              </div>
            <?php elseif ($f['type'] === 'image'): ?>
              <div class="field" data-image-field>
                <span><?= e($f['label']) ?></span>
                <div class="image-preview small"></div>
                <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
                <div class="row"><button type="button" class="btn btn-sm" data-image-pick>Escolher</button><button type="button" class="btn btn-sm btn-ghost" data-image-remove>Remover</button></div>
                <?= $hint ?>
              </div>
            <?php elseif ($f['type'] === 'color'): ?>
              <div class="field">
                <div class="field-inline">
                  <label for="opt-<?= e($key) ?>"><?= e($f['label']) ?></label>
                  <span class="color-input" data-color-input>
                    <input type="text" id="opt-<?= e($key) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>" placeholder="#2f5bea" spellcheck="false" autocomplete="off">
                    <input type="color" value="<?= e($value ?: '#000000') ?>" aria-label="Escolher a cor">
                  </span>
                </div>
                <?= $hint ?>
              </div>
            <?php else: ?>
              <label class="field"><?= e($f['label']) ?>
                <?php if ($f['type'] === 'textarea' || $f['type'] === 'html'): ?>
                  <textarea name="<?= e($name) ?>" rows="<?= $f['type'] === 'html' ? 5 : 3 ?>"<?= $f['type'] === 'html' ? ' class="mono"' : '' ?><?= $placeholder ?>><?= e($value) ?></textarea>
                <?php elseif ($f['type'] === 'select' || $f['type'] === 'category'): ?>
                  <?php
                  $choices = $f['choices'];
                  if ($f['type'] === 'category') {
                      $choices = ['' => '— Nenhuma —'] + array_combine($categories, $categories);
                      if ($value !== '' && !isset($choices[$value])) {
                          $choices[$value] = $value . ' (sem posts publicados)';
                      }
                  }
                  ?>
                  <select name="<?= e($name) ?>">
                    <?php foreach ($choices as $choice => $choiceLabel): ?>
                      <option value="<?= e($choice) ?>"<?= (string) $choice === (string) $value ? ' selected' : '' ?>><?= e($choiceLabel) ?></option>
                    <?php endforeach ?>
                  </select>
                <?php elseif ($f['type'] === 'datetime'): ?>
                  <input type="datetime-local" name="<?= e($name) ?>" value="<?= e($value) ?>">
                <?php elseif ($f['type'] === 'number'): ?>
                  <input type="number" name="<?= e($name) ?>" value="<?= (int) $value ?>"<?= $f['min'] !== null ? ' min="' . $f['min'] . '"' : '' ?><?= $f['max'] !== null ? ' max="' . $f['max'] . '"' : '' ?>>
                <?php else: ?>
                  <?php // url aceita caminhos do blog (/contato/), que o type="url" do navegador recusaria. ?>
                  <input name="<?= e($name) ?>" value="<?= e($value) ?>"<?= $f['type'] === 'url' ? ' inputmode="url" spellcheck="false"' : '' ?><?= $placeholder ?>>
                <?php endif ?>
                <?= $hint ?>
              </label>
            <?php endif ?>
          <?php endforeach ?>
        </div>
      </section>
    <?php endforeach ?>

    <section class="section">
      <header class="section-head">
        <h2>Prévia</h2>
        <p>O site com este tema e as opções desta tela, antes de salvar.</p>
      </header>
      <div class="stack">
        <?= render_file(__DIR__ . '/_site-preview.php', ['src' => $frame, 'address' => $host, 'title' => 'Prévia da página inicial']) ?>
        <?php if ($previewItem): ?>
          <?= render_file(__DIR__ . '/_site-preview.php', [
              'src' => $frame . '?page=post',
              'address' => $host . '/' . $previewItem['slug'] . '/',
              'title' => 'Prévia de um post',
          ]) ?>
        <?php endif ?>
        <span class="hint">Os links da prévia não abrem; use a rolagem para ver o resto da página.</span>
      </div>
    </section>
  </div>

  <div class="sticky-actions"><button class="btn btn-primary">Salvar opções</button></div>
</form>
