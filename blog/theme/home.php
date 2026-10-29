<?php
/**
 * Lista de posts: a página inicial ou, quando o início é uma página fixa, a página dos posts ($blog).
 * A estrutura ($layout) é escolhida em Configurações → Página inicial;
 * Site::blog() já separa o destaque, os posts ao lado dele (Revista) e as seções (Por categoria).
 * @var string $layout  @var int $columns  @var array|null $featured  @var array $side  @var array $posts
 * @var array $sections  @var array|null $blog  @var array $pagination  @var callable $page_link  @var array $site
 */
$grid = 'post-grid cols-' . $columns;
$showTitle = in_array($site['title_place'], ['home', 'both'], true);
$showTagline = $site['home_intro'] && $site['tagline'];
?>
<div class="wrap">
  <?php if ($blog && $pagination['page'] === 1): ?>
    <section class="intro">
      <h1 class="intro-title"><?= e($blog['title']) ?></h1>
      <?php // Texto escrito na página dos posts (HTML do editor), acima da lista. ?>
      <?php if (trim(strip_tags($blog['content'], '<img><iframe><video>')) !== ''): ?><div class="intro-text intro-html"><?= $blog['content'] ?></div><?php endif ?>
    </section>
  <?php elseif ($pagination['page'] === 1): ?>
    <?php // O h1 fica mesmo quando escondido: leitores de tela e buscadores contam com ele. ?>
    <section class="intro<?= $showTitle || $showTagline ? '' : ' visually-hidden' ?>">
      <h1 class="intro-title<?= $showTitle ? '' : ' visually-hidden' ?>"><?= e($site['title']) ?></h1>
      <?php if ($showTagline): ?><p class="intro-text"><?= e($site['tagline']) ?></p><?php endif ?>
    </section>
  <?php endif ?>

  <div class="home-body">
  <?php if (!$featured && !$posts && !$sections): ?>
    <div class="empty">
      <p>Nenhum post publicado ainda.</p>
    </div>
  <?php else: ?>
    <?php if ($side): ?>
      <div class="magazine-top">
        <?php partial('card', ['post' => $featured, 'featured' => true]) ?>
        <div class="magazine-side">
          <?php foreach ($side as $post): ?>
            <?php partial('card', ['post' => $post]) ?>
          <?php endforeach ?>
        </div>
      </div>
    <?php elseif ($featured): ?>
      <?php partial('card', ['post' => $featured, 'featured' => true]) ?>
    <?php endif ?>

    <?php if ($sections): ?>
      <?php foreach ($sections as $section): ?>
        <section class="home-section">
          <header class="home-section-head">
            <h2><a href="<?= e(term_url('category', $section['name'])) ?>"><?= e($section['name']) ?></a></h2>
            <?php if ($section['count'] > count($section['posts'])): ?>
              <a class="more" href="<?= e(term_url('category', $section['name'])) ?>">Ver todos (<?= (int) $section['count'] ?>) →</a>
            <?php endif ?>
          </header>
          <div class="<?= $grid ?>">
            <?php foreach ($section['posts'] as $post): ?>
              <?php partial('card', ['post' => $post, 'level' => 3]) ?>
            <?php endforeach ?>
          </div>
        </section>
      <?php endforeach ?>
    <?php else: ?>
      <?php if ($layout === 'compact'): ?>
        <?php
        $years = [];
        foreach ($posts as $post) {
            $years[date('Y', $post['ts'])][] = $post;
        }
        ?>
        <div class="post-compact">
          <?php foreach ($years as $year => $items): ?>
            <h2 class="compact-year"><?= (int) $year ?></h2>
            <ul class="compact-list">
              <?php foreach ($items as $post): ?>
                <li>
                  <time datetime="<?= e($post['published_at']) ?>"><?= e(date('d/m', $post['ts'])) ?></time>
                  <a href="<?= e(post_url($post)) ?>"><?= e($post['title']) ?></a>
                </li>
              <?php endforeach ?>
            </ul>
          <?php endforeach ?>
        </div>
      <?php else: ?>
        <div class="<?= $layout === 'classic' ? 'post-list' : $grid ?>">
          <?php foreach ($posts as $post): ?>
            <?php partial('card', ['post' => $post]) ?>
          <?php endforeach ?>
        </div>
      <?php endif ?>
      <?php partial('pagination', ['pagination' => $pagination, 'link' => $page_link]) ?>
    <?php endif ?>
  <?php endif ?>
  </div>
</div>
