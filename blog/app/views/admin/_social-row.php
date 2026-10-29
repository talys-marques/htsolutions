<?php
/** Uma rede social em Configurações. @var string $key  @var array $network  @var string $user */
$prefix = Social::prefix($key);
?>
<div class="social-row" data-social-row="<?= e($key) ?>">
  <label class="social-field">
    <span class="social-name"><?= Social::icon($key) ?><span class="social-label"><?= e($network['label']) ?></span></span>
    <?php if ($prefix): ?><span class="social-prefix"><?= e($prefix) ?></span><?php endif ?>
    <input name="social_user[]" value="<?= e($user) ?>" placeholder="<?= e($network['placeholder'] ?? 'seuusuario') ?>" autocomplete="off" autocapitalize="off" spellcheck="false">
  </label>
  <input type="hidden" name="social_network[]" value="<?= e($key) ?>">
  <button type="button" class="btn btn-sm btn-ghost" data-social-remove title="Remover" aria-label="Remover <?= e($network['label']) ?>">✕</button>
</div>
