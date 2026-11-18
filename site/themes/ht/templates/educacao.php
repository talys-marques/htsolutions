<?php
/** Google for Education. @var array $fields campos (fields/educacao.php) */
$videos = array_values(array_filter($fields['playlist']['videos'], fn($v) => ht_youtube($v['link']) !== ''));
$case = $fields['case']['post'] ? Posts::findBySlug($fields['case']['post']) : null;
$caseVideo = ht_youtube($fields['case']['video']);
?>
<section class="cabecalho">
  <div class="container duas-colunas">
    <div>
      <?= ht_img($fields['intro']['logo'], '200px', ['alt' => 'Google for Education', 'class' => 'intro-logo', 'loading' => 'eager']) ?>
      <h1><?= ht_titulo($fields['intro']['titulo']) ?></h1>
      <div class="texto"><?= $fields['intro']['texto'] ?></div>
      <a href="#contato" class="botao botao-claro">Fale com um especialista</a>
    </div>
    <?= ht_img($fields['intro']['imagem'], '(max-width: 899px) 90vw, 520px', ['class' => 'cabecalho-imagem', 'loading' => 'eager']) ?>
  </div>
</section>

<?php if ($fields['destaques']): ?>
  <section class="secao-marinho faixa-numeros">
    <div class="container">
      <ul class="numeros">
        <?php foreach ($fields['destaques'] as $d): ?><li><strong><?= e($d['titulo']) ?></strong> <?= e($d['texto']) ?></li><?php endforeach ?>
      </ul>
    </div>
  </section>
<?php endif ?>

<section class="secao">
  <div class="container duas-colunas">
    <div>
      <?= ht_secao_titulo($fields['definicao']['titulo']) ?>
      <div class="texto texto-grande"><?= $fields['definicao']['texto'] ?></div>
      <?= ht_botao($fields['definicao']['botao'], 'botao', 'seta') ?>
    </div>
    <ul class="ferramentas">
      <?php foreach ($fields['definicao']['blocos'] as $b): ?>
        <li><?= ht_img($b['imagem'], '300px') ?><div class="texto"><?= $b['texto'] ?></div></li>
      <?php endforeach ?>
    </ul>
  </div>
</section>

<section class="secao secao-azul">
  <div class="container">
    <?= ht_secao_titulo($fields['motivos']['titulo'], '', 2, true) ?>
    <ul class="motivos">
      <?php foreach ($fields['motivos']['blocos'] as $m): ?>
        <li><?= ht_img($m['icone'], '48px', ['width' => 48, 'height' => 48]) ?><h3><?= e($m['titulo']) ?></h3><div class="texto"><?= $m['texto'] ?></div></li>
      <?php endforeach ?>
    </ul>
  </div>
</section>

<?php if ($videos): ?>
  <section class="secao">
    <div class="container">
      <?= ht_secao_titulo($fields['playlist']['titulo'], $fields['playlist']['sobretitulo']) ?>
      <div class="playlist" data-playlist>
        <div class="video-embed">
          <iframe src="<?= e(ht_youtube($videos[0]['link'])) ?>" title="<?= e($videos[0]['titulo']) ?>" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe>
        </div>
        <ol>
          <?php foreach ($videos as $i => $v): ?>
            <li>
              <button type="button" data-video="<?= e(ht_youtube($v['link'])) ?>"<?= $i === 0 ? ' aria-current="true"' : '' ?>>
                <?= ht_icone('play', 16) ?> <span><?= e($v['titulo']) ?></span> <small><?= e($v['duracao']) ?></small>
              </button>
            </li>
          <?php endforeach ?>
        </ol>
      </div>
    </div>
  </section>
<?php endif ?>

<section class="secao secao-cinza">
  <div class="container duas-colunas">
    <div>
      <?= ht_secao_titulo($fields['case']['titulo'], $fields['case']['sobretitulo']) ?>
      <?php if ($case && Posts::isPublic($case)): ?><ul class="cartoes cartao-unico"><?= ht_cartao($case) ?></ul><?php endif ?>
      <?= ht_botao($fields['case']['botao'], 'botao botao-contorno', 'seta') ?>
    </div>
    <?php if ($caseVideo): ?>
      <div class="video-embed"><iframe src="<?= e($caseVideo) ?>" title="<?= e(strip_tags($fields['case']['titulo'])) ?>" loading="lazy" allow="encrypted-media; picture-in-picture" allowfullscreen></iframe></div>
    <?php endif ?>
  </div>
</section>
