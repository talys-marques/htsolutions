<?php
/**
 * Painel → Temas: escolher, pré-visualizar, personalizar, baixar e enviar temas.
 * @var array $themes  Themes::all()  @var string $activeSlug  @var string|null $previewing
 */
// Os forms de ação postam para esta mesma tela, com o tema e a ação.
$action = fn(string $do, string $slug) => csrf_field()
    . '<input type="hidden" name="action" value="' . e($do) . '"><input type="hidden" name="theme" value="' . e($slug) . '">';
?>
<div class="page-head"><h1>Temas</h1></div>
<p class="muted">O tema muda o visual do site e pode trazer blocos novos, como banners, vitrines e chamadas para a loja.
  Posts, páginas, menu e configurações continuam os mesmos em qualquer tema: trocar de tema não apaga nada.</p>

<?php if ($previewing): ?>
  <div class="alert alert-warning">Você está pré-visualizando o tema “<?= e($themes[$previewing]['name']) ?>” no site. Só você vê; os visitantes continuam vendo o tema ativo.
    <a class="btn btn-sm" href="<?= e(url('/admin/themes/preview')) ?>">Sair da prévia</a></div>
<?php endif ?>

<div class="theme-grid">
  <?php foreach ($themes as $slug => $theme): ?>
    <?php
    $isActive = $slug === $activeSlug;
    $usable = !$theme['error'];
    $shot = $usable ? Themes::screenshot($theme) : '';
    ?>
    <article class="theme-card<?= $isActive ? ' is-active' : '' ?>">
      <div class="theme-shot">
        <?php if ($shot): ?>
          <img src="<?= e($shot) ?>" alt="" loading="lazy">
        <?php else: ?>
          <span class="theme-shot-empty" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($theme['name'], 0, 1))) ?></span>
        <?php endif ?>
      </div>
      <div class="theme-body">
        <header class="theme-head">
          <h2><?= e($theme['name']) ?></h2>
          <?php if ($isActive): ?><span class="badge badge-published">Ativo</span><?php endif ?>
          <?php if ($slug === $previewing): ?><span class="badge badge-scheduled">Em prévia</span><?php endif ?>
        </header>
        <p class="theme-meta"><?= e(implode(' · ', array_filter([
            $theme['version'] ? 'Versão ' . $theme['version'] : '',
            $theme['author'] ? 'por ' . $theme['author'] : '',
            $theme['builtin'] ? 'pasta theme/' : "pasta themes/$slug/",
        ]))) ?></p>
        <?php if ($theme['description']): ?><p class="theme-desc"><?= e($theme['description']) ?></p><?php endif ?>
        <?php if ($theme['error']): ?><p class="alert alert-error"><?= e($theme['error']) ?></p><?php endif ?>

        <div class="theme-actions">
          <?php if ($usable && !$isActive): ?>
            <form method="post"><?= $action('activate', $slug) ?><button class="btn btn-primary btn-sm">Ativar</button></form>
            <a class="btn btn-sm" href="<?= e(url('/admin/themes/preview/' . $slug)) ?>" target="_blank" rel="noopener">Pré-visualizar</a>
          <?php endif ?>
          <?php if ($usable && $theme['options']): ?>
            <a class="btn btn-sm" href="<?= e(url('/admin/themes/' . $slug)) ?>">Personalizar</a>
          <?php endif ?>
          <?php if (!$theme['builtin']): ?>
            <span class="theme-actions-end">
              <?php if ($usable): ?>
                <form method="post"><?= $action('download', $slug) ?><button class="link" title="Para instalar em outro site, com as opções preenchidas aqui">Baixar .zip</button></form>
              <?php endif ?>
              <?php if (!$isActive): ?>
                <form method="post" data-confirm="Excluir o tema “<?= e($theme['name']) ?>”? A pasta themes/<?= e($slug) ?>/ e as opções salvas dele serão apagadas. Posts e páginas não mudam.">
                  <?= $action('delete', $slug) ?><button class="link danger">Excluir</button>
                </form>
              <?php endif ?>
            </span>
          <?php endif ?>
        </div>
      </div>
    </article>
  <?php endforeach ?>
</div>

<div class="panel sections">
  <section class="section">
    <header class="section-head">
      <h2>Enviar tema</h2>
      <p>Instale um tema novo ou atualize um que já existe.</p>
    </header>
    <div class="stack">
      <form class="dropzone" method="post" action="<?= e(url('/admin/themes')) ?>" enctype="multipart/form-data" data-dropzone>
        <?= csrf_field() ?><input type="hidden" name="action" value="upload">
        <input type="file" name="zip" id="theme-zip" accept=".zip,application/zip" hidden>
        <label for="theme-zip">
          <strong>Clique para enviar</strong> ou arraste o .zip do tema para cá
          <span class="hint">Um arquivo .zip com a pasta do tema (com o theme.json dentro) · até <?= e(format_bytes(Media::maxBytes())) ?></span>
        </label>
      </form>
      <p class="muted"><b>Levar um tema para outro site:</b> clique em <b>Baixar .zip</b> no tema e envie o arquivo aqui, no painel do outro site.
        Vão juntas as opções preenchidas em Personalizar e as imagens usadas nelas.</p>
      <p class="muted"><b>Atualizar:</b> enviar um tema que já está instalado troca os arquivos dele pela versão nova e mantém as opções deste site.</p>
      <p class="muted"><b>Criar um tema:</b> o passo a passo está em <code>themes/README.md</code>. O jeito mais rápido é baixar um destes, mudar o nome da pasta e o theme.json e editar.</p>
    </div>
  </section>
</div>
