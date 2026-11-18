<?php
/**
 * Página de apresentação (campos em theme/fields/landing.php). Seção vazia não aparece.
 * Imprima texto com e(); os campos html e heading já foram limpos ao salvar e vão direto.
 * @var array $post  @var array $fields  campos completos (toda chave existe)
 */
$hero = $fields['hero'];
$cta = $fields['cta'];
$button = function (array $b, string $class = 'lp-button'): string {
    if ($b['text'] === '' || $b['link'] === '') {
        return '';
    }
    $external = preg_match('#^https?://#i', $b['link']) ? ' target="_blank" rel="noopener"' : '';
    return '<a class="' . e($class) . '" href="' . e($b['link']) . '"' . $external . '>' . e($b['text']) . '</a>';
};
$blocks = array_filter($fields['blocks'], fn($b) => $b['title'] !== '' || $b['text'] !== '' || $b['image'] !== '');
$cards = array_filter($fields['cards']['items'], fn($c) => $c['title'] !== '' || $c['text'] !== '' || $c['image'] !== '');
$faq = array_filter($fields['faq']['items'], fn($q) => $q['question'] !== '' && $q['answer'] !== '');
$buttons = $button($hero['button']) . $button($hero['button_2'], 'lp-button lp-button-ghost');
?>
<article class="lp">
  <header class="lp-hero<?= $hero['image'] !== '' ? ' has-image' : '' ?>">
    <div class="wrap lp-hero-inner">
      <div class="lp-hero-text">
        <?php if ($hero['eyebrow'] !== ''): ?><p class="lp-eyebrow"><?= e($hero['eyebrow']) ?></p><?php endif ?>
        <h1 class="lp-title"><?= e($post['title']) ?></h1>
        <?php if ($hero['text'] !== ''): ?><p class="lp-lead"><?= nl2br(e($hero['text'])) ?></p><?php endif ?>
        <?php if ($buttons !== ''): ?><div class="lp-buttons"><?= $buttons ?></div><?php endif ?>
      </div>
      <?php if ($hero['image'] !== ''): ?>
        <figure class="lp-image"><?= image_tag($hero['image'], '(max-width: 860px) 100vw, 560px', ['alt' => $hero['image_alt'], 'fetchpriority' => 'high']) ?></figure>
      <?php endif ?>
    </div>
  </header>

  <?php foreach ($blocks as $block): ?>
    <section class="lp-block<?= $block['image'] !== '' ? ' has-image' : '' ?><?= $block['side'] === 'left' ? ' image-left' : '' ?>">
      <div class="wrap lp-block-inner">
        <div class="lp-block-text">
          <?php if ($block['title'] !== ''): ?><h2><?= e($block['title']) ?></h2><?php endif ?>
          <?php if ($block['text'] !== ''): ?><div class="prose"><?= Media::optimizeHtml($block['text'], false, '(max-width: 860px) 100vw, 560px') ?></div><?php endif ?>
        </div>
        <?php if ($block['image'] !== ''): ?>
          <figure class="lp-image"><?= image_tag($block['image'], '(max-width: 860px) 100vw, 560px', ['alt' => $block['image_alt'], 'loading' => 'lazy', 'decoding' => 'async']) ?></figure>
        <?php endif ?>
      </div>
    </section>
  <?php endforeach ?>

  <?php if ($cards): ?>
    <section class="lp-section">
      <div class="wrap">
        <?php if ($fields['cards']['title'] !== ''): ?><h2 class="lp-section-title"><?= e($fields['cards']['title']) ?></h2><?php endif ?>
        <ul class="lp-cards">
          <?php foreach ($cards as $card): ?>
            <li class="lp-card">
              <?php if ($card['image'] !== ''): ?><?= image_tag($card['image'], '(max-width: 640px) 100vw, 400px', ['alt' => '', 'loading' => 'lazy', 'decoding' => 'async']) ?><?php endif ?>
              <?php if ($card['title'] !== ''): ?>
                <?php // Com link, o título vira o link e o CSS estica o clique para o cartão inteiro. ?>
                <h3><?php if ($card['link'] !== ''): ?><a href="<?= e($card['link']) ?>"><?= e($card['title']) ?></a><?php else: ?><?= e($card['title']) ?><?php endif ?></h3>
              <?php endif ?>
              <?php if ($card['text'] !== ''): ?><p><?= nl2br(e($card['text'])) ?></p><?php endif ?>
            </li>
          <?php endforeach ?>
        </ul>
      </div>
    </section>
  <?php endif ?>

  <?php if ($faq): ?>
    <section class="lp-section">
      <div class="narrow">
        <?php if ($fields['faq']['title'] !== ''): ?><h2 class="lp-section-title"><?= e($fields['faq']['title']) ?></h2><?php endif ?>
        <?php foreach ($faq as $q): ?>
          <details class="lp-question">
            <summary><?= e($q['question']) ?></summary>
            <div class="prose"><?= $q['answer'] ?></div>
          </details>
        <?php endforeach ?>
      </div>
    </section>
  <?php endif ?>

  <?php if ($cta['title'] !== '' || $button($cta['button']) !== ''): ?>
    <section class="lp-cta">
      <div class="narrow">
        <?php if ($cta['title'] !== ''): ?><h2><?= $cta['title'] ?></h2><?php endif ?>
        <?php if ($cta['text'] !== ''): ?><p><?= nl2br(e($cta['text'])) ?></p><?php endif ?>
        <?= $button($cta['button'], 'lp-button lp-button-light') ?>
      </div>
    </section>
  <?php endif ?>
</article>
