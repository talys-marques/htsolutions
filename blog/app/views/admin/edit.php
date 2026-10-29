<?php
/**
 * Editor de post/página.
 * @var array $post  @var array|null $item  @var string|null $error
 * @var array $categories  @var array $tags  @var array $revisions  @var array $authors
 */
$isPage = $post['type'] === 'page';
$isNew = !$post['id'];
$isPublished = $post['status'] === 'published';
$isPublic = $item && Posts::isPublic($item);
$action = $isNew ? url('/admin/posts/new') : url('/admin/posts/' . $post['id']);
$viewUrl = $isPublic ? post_url($item) : ($isNew ? null : url('/admin/preview/' . $post['id']));

$termsBox = function (string $name, string $label, array $current, array $suggestions, string $placeholder) {
    ob_start(); ?>
    <section class="box" data-terms>
      <h3><?= e($label) ?></h3>
      <input name="<?= e($name) ?>" value="<?= e(implode(', ', $current)) ?>" placeholder="<?= e($placeholder) ?>" autocomplete="off">
      <p class="hint">Separe por vírgula.</p>
      <?php if ($suggestions): ?>
        <div class="chips">
          <?php foreach ($suggestions as $term): ?><button type="button" class="chip" data-name="<?= e($term) ?>"><?= e($term) ?></button><?php endforeach ?>
        </div>
      <?php endif ?>
    </section>
    <?php return ob_get_clean();
};
?>
<form method="post" action="<?= e($action) ?>" id="post-form" class="editor-form"
      data-post-id="<?= (int) $post['id'] ?>" data-type="<?= e($post['type']) ?>" data-updated="<?= e($post['updated_at']) ?>"
      data-seo="post" data-site="<?= e(setting('title')) ?>" data-sep="<?= e(Seo::separator()) ?>" data-host="<?= e(preg_replace('#^https?://#', '', site_url())) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="type" value="<?= e($post['type']) ?>">

  <div class="page-head">
    <h1><?= e($title) ?></h1>
    <div class="head-actions">
      <a class="btn btn-ghost" href="<?= e(url('/admin/posts' . ($isPage ? '?type=page' : ''))) ?>">← Voltar</a>
      <?php if ($viewUrl): ?><a class="btn btn-ghost" href="<?= e($viewUrl) ?>" target="_blank" rel="noopener"><?= $isPublic ? 'Ver no site ↗' : 'Pré-visualizar ↗' ?></a><?php endif ?>
    </div>
  </div>

  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif ?>
  <div class="alert alert-warning draft-notice" hidden>
    Este navegador tem uma versão deste texto que não foi salva.
    <button type="button" class="btn btn-sm" data-draft-restore>Recuperar</button>
    <button type="button" class="btn btn-sm btn-ghost" data-draft-discard>Descartar</button>
  </div>

  <div class="editor-layout">
    <div class="editor-main">
      <input class="title-input" name="title" value="<?= e($post['title']) ?>" placeholder="Título" required autocomplete="off" aria-label="Título">
      <div class="slug-row">
        <span class="muted"><?= e(preg_replace('#^https?://#', '', site_url())) ?>/</span><input name="slug" class="slug-input" value="<?= e($post['slug']) ?>" placeholder="endereco-do-post" aria-label="Endereço (slug)" data-auto="<?= $post['slug'] === '' ? '1' : '' ?>" autocomplete="off"><span class="muted">/</span>
      </div>
      <?php if ($post['id'] && ($role = Admin::pageRole($post))): ?>
        <p class="hint"><?= $role === 'Página inicial'
            ? 'Esta é a página inicial do blog: ela abre no endereço principal, e o endereço acima leva para lá.'
            : 'Esta é a página dos posts: a lista de posts aparece abaixo deste texto, no endereço acima.' ?>
          Para trocar, vá em <a href="<?= e(url('/admin/settings#pagina-inicial')) ?>">Configurações → Página inicial</a>.</p>
      <?php endif ?>

      <?= render_file(__DIR__ . '/_editor.php', ['name' => 'content', 'value' => $post['content'], 'placeholder' => 'Escreva aqui… (arraste imagens para cá)']) ?>
      <p class="hint">Atalhos: <kbd>Ctrl</kbd>+<kbd>S</kbd> salva · clique numa imagem para editar texto alternativo e legenda.</p>

      <?php // Análise e campos de SEO (o que o Yoast fazia). A análise roda em admin.js, seção "SEO". ?>
      <section class="box seo-panel">
        <header class="seo-panel-head">
          <h3>SEO · Google e redes sociais</h3>
          <span class="seo-score" data-seo-score></span>
        </header>
        <div class="seo-panel-body">
          <div class="stack">
            <?= render_file(__DIR__ . '/_snippet.php') ?>
            <label class="field">Palavra-chave principal
              <input name="seo_keyword" value="<?= e($post['seo_keyword']) ?>" maxlength="80" placeholder="Ex.: como fazer um deck de madeira" autocomplete="off">
              <span class="hint">O termo que as pessoas buscariam no Google para achar este texto. Só serve para a análise ao lado.</span>
            </label>
            <label class="field">Título para o Google
              <input name="seo_title" value="<?= e($post['seo_title']) ?>" maxlength="120" placeholder="Vazio = título <?= $isPage ? 'da página' : 'do post' ?>">
              <span class="meter" data-meter="title" data-field="seo_title"></span>
            </label>
            <label class="field">Descrição para o Google
              <textarea name="seo_description" rows="3" maxlength="300" placeholder="Vazio = resumo (ou o começo do texto)"><?= e($post['seo_description']) ?></textarea>
              <span class="meter" data-meter="desc" data-field="seo_description"></span>
            </label>
            <label class="check"><input type="checkbox" name="seo_noindex" value="1"<?= $post['seo_noindex'] ? ' checked' : '' ?>> Esconder dos buscadores</label>
            <p class="hint">Continua no blog para quem tem o link, mas sai do Google e do sitemap.</p>
          </div>
          <div>
            <p class="seo-checks-title">Análise</p>
            <ul class="seo-checks" data-seo-checks></ul>
          </div>
        </div>
      </section>
    </div>

    <aside class="editor-side">
      <section class="box">
        <h3>Publicação</h3>
        <p class="status-line">Status: <?= $item ? Admin::badge($item) : '<span class="badge badge-draft">Novo</span>' ?></p>
        <label class="field">Data de publicação
          <input type="datetime-local" name="published_at" value="<?= e(iso_to_input($post['published_at'])) ?>">
          <span class="hint">Vazio = agora. Data futura = agendado.</span>
        </label>
        <div class="publish-actions">
          <?php if ($isPublished): ?>
            <button class="btn btn-primary" name="status" value="published" data-default-save>Salvar alterações</button>
            <button class="btn btn-ghost" name="status" value="draft" data-confirm="Tirar do ar e voltar para rascunho?">Voltar para rascunho</button>
          <?php else: ?>
            <button class="btn" name="status" value="draft" data-default-save>Salvar rascunho</button>
            <button class="btn btn-primary" name="status" value="published">Publicar</button>
          <?php endif ?>
        </div>
        <?php if (!$isNew && $post['updated_at']): ?>
          <p class="hint">Última alteração: <?= e(format_date($post['updated_at'], true)) ?></p>
        <?php endif ?>
      </section>

      <?php if (!$isPage): ?>
        <?= $termsBox('categories', 'Categorias', $post['categories'], $categories, 'Ex.: Notícias, Tutoriais') ?>
        <?= $termsBox('tags', 'Tags', $post['tags'], $tags, 'Ex.: php, dicas') ?>
      <?php endif ?>

      <section class="box" data-image-field>
        <h3>Imagem de capa</h3>
        <div class="image-preview"></div>
        <input type="hidden" name="cover" value="<?= e($post['cover']) ?>">
        <div class="row">
          <button type="button" class="btn btn-sm" data-image-pick>Escolher imagem</button>
          <button type="button" class="btn btn-sm btn-ghost" data-image-remove>Remover</button>
        </div>
        <label class="field">Texto alternativo
          <input name="cover_alt" value="<?= e($post['cover_alt']) ?>" maxlength="200" placeholder="Descreva a imagem" autocomplete="off">
          <span class="hint">Para o Google Imagens e para quem usa leitor de tela.</span>
        </label>
      </section>

      <section class="box">
        <h3>Resumo</h3>
        <textarea name="excerpt" rows="3" placeholder="Aparece nas listagens. Vazio = início do texto."><?= e($post['excerpt']) ?></textarea>
      </section>

      <?php if (count($authors) > 1): ?>
        <section class="box">
          <h3>Autor</h3>
          <select name="author">
            <?php foreach ($authors as $a): ?>
              <option value="<?= e($a['username']) ?>"<?= $a['username'] === $post['author'] ? ' selected' : '' ?>><?= e($a['name']) ?></option>
            <?php endforeach ?>
          </select>
        </section>
      <?php endif ?>

      <?php if ($revisions): ?>
        <details class="box">
          <summary><h3>Revisões (<?= count($revisions) ?>)</h3></summary>
          <ul class="revisions">
            <?php foreach ($revisions as $i => $rev): ?>
              <li>
                <span><?= e(format_date($rev['date'], true)) ?></span>
                <button class="link" form="revision-<?= $i ?>" data-confirm="Voltar o texto para esta versão? A versão atual fica guardada nas revisões.">Restaurar</button>
              </li>
            <?php endforeach ?>
          </ul>
        </details>
      <?php endif ?>

      <?php if (!$isNew && $post['status'] !== 'trash'): ?>
        <button class="link danger" form="trash-form">Mover para a lixeira</button>
      <?php endif ?>
    </aside>
  </div>
</form>

<?php if (!$isNew): ?>
  <form method="post" action="<?= e(url('/admin/posts/' . $post['id'] . '/trash')) ?>" id="trash-form" hidden><?= csrf_field() ?></form>
  <?php foreach ($revisions as $i => $rev): ?>
    <form method="post" action="<?= e(url('/admin/posts/' . $post['id'] . '/revision')) ?>" id="revision-<?= $i ?>" hidden>
      <?= csrf_field() ?><input type="hidden" name="revision" value="<?= e($rev['name']) ?>">
    </form>
  <?php endforeach ?>
<?php endif ?>
