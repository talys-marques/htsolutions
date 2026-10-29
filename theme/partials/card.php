<?php
/**
 * Card de post nas listagens.
 * @var array $post  item do índice (sem o conteúdo)  @var bool $featured  @var int $level  nível do título (h2, h3)
 */
$featured = $featured ?? false;
$level = $level ?? 2;
$category = $post['categories'][0] ?? null;
?>
<article class="card<?= $featured ? ' card-featured' : '' ?><?= $post['cover'] ? ' has-cover' : '' ?>">
  <?php if ($post['cover']): ?>
    <a class="card-cover" href="<?= e(post_url($post)) ?>" tabindex="-1" aria-hidden="true">
      <?php // sizes: largura do card na tela (destaque ocupa mais); o navegador escolhe a versão do srcset. ?>
      <?= image_tag($post['cover'], $featured ? '(max-width: 800px) 100vw, 760px' : '(max-width: 640px) 100vw, 360px',
          ['alt' => '', 'loading' => $featured ? 'eager' : 'lazy', 'decoding' => 'async']) ?>
    </a>
  <?php endif ?>
  <div class="card-body">
    <?php if ($category): ?><a class="kicker" href="<?= e(term_url('category', $category)) ?>"><?= e($category) ?></a><?php endif ?>
    <h<?= (int) $level ?> class="card-title"><a href="<?= e(post_url($post)) ?>"><?= e($post['title']) ?></a></h<?= (int) $level ?>>
    <?php if ($post['excerpt']): ?><p class="card-excerpt"><?= e($post['excerpt']) ?></p><?php endif ?>
    <?php if ($post['type'] === 'post'): ?>
      <p class="meta">
        <time datetime="<?= e($post['published_at']) ?>"><?= e(format_date($post['published_at'])) ?></time>
        <span><?= (int) $post['reading_time'] ?> min de leitura</span>
      </p>
    <?php endif ?>
  </div>
</article>
