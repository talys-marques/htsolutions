<?php
/**
 * Prévia do resultado no Google. admin.js (seção "SEO") preenche e atualiza
 * enquanto se digita, a partir do formulário com data-seo.
 */
?>
<div class="snippet" data-snippet>
  <p class="snippet-label">Prévia no Google</p>
  <div class="snippet-site">
    <span class="snippet-icon" aria-hidden="true"><img src="<?= e(favicon_url()) ?>" alt=""></span>
    <span><span class="snippet-name"><?= e(setting('title')) ?></span><span class="snippet-url" data-snippet-url></span></span>
  </div>
  <p class="snippet-title" data-snippet-title></p>
  <p class="snippet-desc" data-snippet-desc></p>
</div>
