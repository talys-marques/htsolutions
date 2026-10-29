<?php
/**
 * SEO de uma categoria ou tag.
 * @var string $kind  @var array $term  [name, slug, count]  @var array $seo  Terms::get()  @var string $auto  descrição automática
 */
$path = "/$kind/" . $term['slug'] . '/';
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <div class="head-actions">
    <a class="btn btn-ghost" href="<?= e(url('/admin/seo/terms')) ?>">← Voltar</a>
    <a class="btn btn-ghost" href="<?= e(term_url($kind, $term['name'])) ?>" target="_blank" rel="noopener">Ver no site ↗</a>
  </div>
</div>

<form method="post" class="panel stack term-form" data-seo="term" data-site="<?= e(setting('title')) ?>" data-sep="<?= e(Seo::separator()) ?>"
      data-host="<?= e(preg_replace('#^https?://#', '', site_url())) ?>" data-path="<?= e($path) ?>" data-name="<?= e($term['name']) ?>" data-auto="<?= e($auto) ?>">
  <?= csrf_field() ?>
  <p class="muted"><?= (int) $term['count'] ?> <?= $term['count'] === 1 ? 'post' : 'posts' ?> · <?= e(absolute_url($path)) ?></p>
  <div class="field">
    <span>Texto de apresentação</span>
    <?= render_file(__DIR__ . '/_editor.php', ['name' => 'description', 'value' => $seo['description'], 'compact' => true,
        'placeholder' => 'Aparece no topo da página, abaixo do nome. Ex.: dicas, guias e novidades sobre…']) ?>
    <span class="hint">Alguns parágrafos sobre o assunto, com as palavras que as pessoas buscam e links para posts, produtos e outras categorias.</span>
  </div>
  <?= render_file(__DIR__ . '/_snippet.php') ?>
  <label class="field">Título para o Google
    <input name="seo_title" value="<?= e($seo['seo_title']) ?>" maxlength="120" placeholder="Vazio = <?= e($term['name']) ?>">
    <span class="meter" data-meter="title" data-field="seo_title"></span>
  </label>
  <label class="field">Descrição para o Google
    <textarea name="seo_description" rows="3" maxlength="300" placeholder="Vazio = começo do texto de apresentação ou os títulos dos posts"><?= e($seo['seo_description']) ?></textarea>
    <span class="meter" data-meter="desc" data-field="seo_description"></span>
  </label>
  <label class="check"><input type="checkbox" name="noindex" value="1"<?= $seo['noindex'] ? ' checked' : '' ?>> Esconder dos buscadores</label>
  <?php if ($kind === 'tag' && !setting('seo_index_tags')): ?>
    <p class="hint">Todas as tags já estão fora do Google<?= Auth::isAdmin() ? ' (SEO → Geral → Indexação)' : '' ?>.</p>
  <?php endif ?>
  <div><button class="btn btn-primary">Salvar</button></div>
</form>
