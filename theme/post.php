<?php
/**
 * Post completo.
 * @var array $post  @var array|null $author  @var array|null $prev  @var array|null $next
 * @var array $related  posts do "Leia também" (itens do índice)  @var bool $preview  @var int $reading_time  @var array $site
 */
$related = $related ?? [];
$shareUrl = rawurlencode(absolute_url('/' . $post['slug'] . '/'));
$shareText = rawurlencode($post['title']);
?>
<?php if ($preview): ?>
  <div class="preview-bar">Pré-visualização · este conteúdo ainda não está publicado</div>
<?php endif ?>

<article class="post">
  <header class="post-header narrow">
    <?php if ($post['categories']): ?>
      <p class="kickers">
        <?php foreach ($post['categories'] as $category): ?>
          <a class="kicker" href="<?= e(term_url('category', $category)) ?>"><?= e($category) ?></a>
        <?php endforeach ?>
      </p>
    <?php endif ?>
    <h1 class="post-title"><?= e($post['title']) ?></h1>
    <p class="meta">
      <?php if ($site['show_author']): ?>
        <a href="<?= e(author_url($post['author'])) ?>"><?= e($author['name'] ?? $post['author']) ?></a>
      <?php endif ?>
      <time datetime="<?= e($post['published_at']) ?>"><?= e(format_date($post['published_at'] ?: $post['updated_at'])) ?></time>
      <span><?= (int) $reading_time ?> min de leitura</span>
    </p>
  </header>

  <?php if ($post['cover']): ?>
    <figure class="post-cover"><?= image_tag($post['cover'], '(max-width: 1000px) 100vw, 960px', ['alt' => $post['cover_alt'], 'fetchpriority' => 'high']) ?></figure>
  <?php endif ?>

  <div class="prose narrow">
    <?= $post['content'] ?>
  </div>

  <footer class="post-footer narrow">
    <?php if ($post['tags']): ?>
      <p class="tags">
        <?php foreach ($post['tags'] as $tag): ?>
          <a href="<?= e(term_url('tag', $tag)) ?>">#<?= e($tag) ?></a>
        <?php endforeach ?>
      </p>
    <?php endif ?>
    <p class="share">
      <span>Compartilhar:</span>
      <a href="https://wa.me/?text=<?= $shareText ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener">WhatsApp</a>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $shareUrl ?>" target="_blank" rel="noopener">LinkedIn</a>
      <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&amp;text=<?= $shareText ?>" target="_blank" rel="noopener">X</a>
      <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener">Facebook</a>
    </p>
  </footer>
</article>

<?php if ($related): ?>
  <section class="related" aria-labelledby="leia-tambem">
    <h2 id="leia-tambem">Leia também</h2>
    <div class="post-grid cols-3">
      <?php foreach ($related as $item): ?>
        <?php partial('card', ['post' => $item, 'level' => 3]) ?>
      <?php endforeach ?>
    </div>
  </section>
<?php endif ?>

<?php if ($prev || $next): ?>
  <nav class="post-nav narrow" aria-label="Outros posts">
    <?php if ($prev): ?>
      <a class="post-nav-link" href="<?= e(post_url($prev)) ?>"><span>← Anterior</span><strong><?= e($prev['title']) ?></strong></a>
    <?php else: ?><span></span><?php endif ?>
    <?php if ($next): ?>
      <a class="post-nav-link next" href="<?= e(post_url($next)) ?>"><span>Próximo →</span><strong><?= e($next['title']) ?></strong></a>
    <?php endif ?>
  </nav>
<?php endif ?>
