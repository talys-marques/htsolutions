<?php
/**
 * Página fixa (Sobre, Contato...).
 * Páginas do site da HT têm um modelo: o conteúdo vem dos campos (Conteúdo do site, no painel)
 * e o template é theme/site/{modelo}.php.
 * @var array $post  @var bool $preview
 */
$modelo = basename((string) ($post['modelo'] ?? ''));
if ($modelo !== '' && is_file(__DIR__ . "/site/$modelo.php") && class_exists('SiteHT')) {
    require_once __DIR__ . '/site/_partes.php';
    $c = SiteHT::completar($modelo, $post['campos'] ?? []);
    echo '<div class="pg pg-' . e($modelo) . '">';
    include __DIR__ . "/site/$modelo.php";
    echo '</div>';
    return;
}
?>
<?php if ($preview): ?>
  <div class="preview-bar">Pré-visualização · esta página ainda não está publicada</div>
<?php endif ?>

<article class="post page">
  <header class="post-header narrow">
    <h1 class="post-title"><?= e($post['title']) ?></h1>
  </header>
  <?php if ($post['cover']): ?>
    <figure class="post-cover"><?= image_tag($post['cover'], '(max-width: 1000px) 100vw, 960px', ['alt' => $post['cover_alt'], 'fetchpriority' => 'high']) ?></figure>
  <?php endif ?>
  <div class="prose narrow">
    <?= $post['content'] ?>
  </div>
</article>
