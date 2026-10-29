<?php
/**
 * Prévia ao vivo do site num iframe reduzido (Configurações). O admin.js envia
 * o formulário para $src a cada mudança e mostra o resultado.
 * @var string $src  @var string $address  @var string $title
 */
?>
<div class="site-preview" data-site-preview="<?= e($src) ?>" data-device="desktop">
  <div class="site-preview-bar">
    <span class="site-preview-dots" aria-hidden="true"><i></i><i></i><i></i></span>
    <span class="site-preview-url"><?= e($address) ?></span>
    <span class="site-preview-status" data-preview-status role="status"></span>
    <span class="segmented" role="group" aria-label="Tamanho da prévia">
      <button type="button" data-preview-device="desktop" aria-pressed="true">Computador</button>
      <button type="button" data-preview-device="mobile" aria-pressed="false">Celular</button>
    </span>
  </div>
  <div class="site-preview-stage">
    <iframe title="<?= e($title) ?>" sandbox="allow-same-origin" tabindex="-1"></iframe>
  </div>
</div>
