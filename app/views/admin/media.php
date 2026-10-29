<?php /** @var array $files  @var string $q  @var array $pagination */ ?>
<div class="page-head"><h1>Mídia</h1></div>

<form class="dropzone" method="post" action="<?= e(url('/admin/media/upload')) ?>" enctype="multipart/form-data" data-dropzone>
  <?= csrf_field() ?>
  <input type="file" name="files[]" id="media-files" multiple hidden>
  <label for="media-files">
    <strong>Clique para enviar</strong> ou arraste arquivos para cá
    <span class="hint">Imagens, PDF, áudio, vídeo e documentos · até <?= e(format_bytes(Media::maxBytes())) ?> por arquivo · imagens largas são reduzidas automaticamente</span>
  </label>
</form>

<div class="toolbar">
  <span class="muted"><?= (int) $pagination['total'] ?> arquivo(s)</span>
  <form class="search" method="get" action="<?= e(url('/admin/media')) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar pelo nome…" aria-label="Buscar">
  </form>
</div>

<?php if (!$files): ?>
  <div class="empty"><p><?= $q !== '' ? 'Nenhum arquivo com esse nome.' : 'Nenhum arquivo enviado ainda.' ?></p></div>
<?php else: ?>
  <div class="media-grid">
    <?php foreach ($files as $f): ?>
      <figure class="media-item">
        <a class="media-thumb" href="<?= e($f['url']) ?>" target="_blank" rel="noopener">
          <?php if ($f['is_image']): ?>
            <img src="<?= e($f['url']) ?>" alt="" loading="lazy">
          <?php else: ?>
            <span class="file-icon"><?= e($f['ext']) ?></span>
          <?php endif ?>
        </a>
        <figcaption>
          <span class="media-name" title="<?= e($f['path']) ?>"><?= e($f['name']) ?></span>
          <span class="hint"><?= e(format_bytes($f['size'])) ?> · <?= e(date('d/m/Y', $f['mtime'])) ?></span>
          <span class="media-actions">
            <button type="button" class="link" data-copy="<?= e(full_url($f['url'])) ?>">Copiar URL</button>
            <form method="post" action="<?= e(url('/admin/media/delete')) ?>" data-confirm="Excluir “<?= e($f['name']) ?>”? Posts que usam este arquivo ficarão com ele quebrado.">
              <?= csrf_field() ?><input type="hidden" name="path" value="<?= e($f['path']) ?>">
              <button class="link danger">Excluir</button>
            </form>
          </span>
        </figcaption>
      </figure>
    <?php endforeach ?>
  </div>
  <?= render_file(__DIR__ . '/_pagination.php', [
      'pagination' => $pagination,
      'link' => fn($n) => url('/admin/media') . '?' . http_build_query(array_filter(['page' => $n > 1 ? $n : '', 'q' => $q])),
  ]) ?>
<?php endif ?>
