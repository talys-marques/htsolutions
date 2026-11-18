<?php
/**
 * /admin/content/{id}: formulário de uma página do Conteúdo do site, montado a partir do modelo
 * (fields/{modelo}.php do tema). Os campos do modelo não têm name: admin.js (seção "Conteúdo do
 * site") junta todos num JSON, no campo "fields", ao salvar.
 * @var array $post  @var array|null $item  @var array $template  @var bool $structure  mostra endereço e situação
 * @var array $values  campos completos (Field::fill)  @var array $revisions  Posts::revisions()
 */
$shared = $template['shared'];
$isPublic = $item && Posts::isPublic($item);
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <div class="head-actions">
    <a class="btn btn-ghost" href="<?= e(url('/admin/content')) ?>">← Conteúdo do site</a>
    <?php if (!$shared): ?>
      <a class="btn btn-ghost" href="<?= e($isPublic ? post_url($post) : url('/admin/preview/' . $post['id'])) ?>" target="_blank" rel="noopener"><?= $isPublic ? 'Ver no site ↗' : 'Pré-visualizar ↗' ?></a>
    <?php endif ?>
  </div>
</div>

<?php if ($template['description'] !== ''): ?><p class="muted"><?= e($template['description']) ?></p><?php endif ?>

<form method="post" class="cf-form" data-content-form>
  <?= csrf_field() ?>
  <input type="hidden" name="fields" value="">

  <?php if (!$shared): ?>
    <fieldset class="cf-group">
      <legend>Página</legend>
      <label class="field">Título da página
        <input name="title" value="<?= e($post['title']) ?>" maxlength="150" required autocomplete="off">
        <span class="hint">Aparece no alto da página, na aba do navegador e no Google.</span>
      </label>
      <?php if ($structure): ?>
        <label class="field">Endereço
          <?php // Enquanto a página está em rascunho e o endereço não foi mexido, ele acompanha o título (admin.js). ?>
          <input name="slug" value="<?= e($post['slug']) ?>" maxlength="80" pattern="[a-z0-9\-]+" autocomplete="off"
                 data-auto="<?= $post['status'] !== 'published' && $post['slug'] === slugify($post['title']) ? '1' : '' ?>">
          <span class="hint"><?= e(absolute_url('/')) ?><strong><?= e($post['slug']) ?></strong>/ · só letras minúsculas, números e hífens. Ao mudar o endereço de uma página publicada, o antigo continua levando para cá.</span>
        </label>
        <label class="field">Situação
          <select name="status">
            <option value="published"<?= $post['status'] === 'published' ? ' selected' : '' ?>>Publicada: aparece no site</option>
            <option value="draft"<?= $post['status'] !== 'published' ? ' selected' : '' ?>>Rascunho: fora do site</option>
          </select>
        </label>
      <?php endif ?>
      <label class="field">Descrição para o Google
        <textarea name="seo_description" rows="2" maxlength="300"><?= e($post['seo_description']) ?></textarea>
        <span class="hint">Uma ou duas frases sobre a página (até uns 160 caracteres). Vazio = começo do texto da página.</span>
      </label>
    </fieldset>
  <?php endif ?>

  <div class="cf-fields">
    <?php foreach ($template['fields'] as $key => $field): ?>
      <?= render_file(__DIR__ . '/_field.php', ['key' => $key, 'field' => $field, 'value' => $values[$key] ?? null]) ?>
    <?php endforeach ?>
  </div>

  <div class="cf-save">
    <button class="btn btn-primary" data-busy="Salvando…">Salvar</button>
    <span class="hint"><?= $shared || $isPublic ? 'Entra no ar assim que você salva.' : 'Rascunho: só aparece no site quando a situação for "Publicada".' ?>
      Última alteração: <?= e(format_date($post['updated_at'], true)) ?>.</span>
  </div>
</form>

<?php if ($revisions): ?>
  <details class="cf-group cf-revisions">
    <summary><strong>Versões anteriores (<?= count($revisions) ?>)</strong> <span class="muted">· cada vez que alguém salva, a versão que estava antes fica guardada aqui</span></summary>
    <ul>
      <?php foreach ($revisions as $rev): ?>
        <li>
          <span><?= e(format_date($rev['date'], true)) ?></span>
          <form method="post" action="<?= e(url('/admin/posts/' . $post['id'] . '/revision')) ?>" data-confirm="Voltar a página para esta versão? A versão de agora continua na lista.">
            <?= csrf_field() ?>
            <input type="hidden" name="revision" value="<?= e($rev['name']) ?>">
            <input type="hidden" name="from" value="content">
            <button class="link">Restaurar</button>
          </form>
        </li>
      <?php endforeach ?>
    </ul>
  </details>
<?php endif ?>
