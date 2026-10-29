<?php
/** Quem somos. @var array $c campos (modelos/quem-somos.php)  @var array $post */
$parceiros = ht_geral()['parceiros'] ?? [];
?>
<section class="cabecalho">
  <div class="container">
    <div class="titulo"><h1><?= e($post['title']) ?></h1></div>
    <div class="texto"><?= $c['resumo'] ?></div>
  </div>
</section>

<section class="secao">
  <div class="container duas-colunas">
    <div class="texto texto-grande"><?= $c['texto'] ?></div>
    <?php if ($c['video']['url']): ?>
      <div>
        <h2 class="video-titulo"><?= e($c['video']['titulo']) ?></h2>
        <video class="video" src="<?= e(ht_link($c['video']['url'])) ?>" controls preload="metadata" playsinline></video>
      </div>
    <?php endif ?>
  </div>
</section>

<section class="secao secao-cinza">
  <div class="container">
    <?= ht_secao_titulo($c['premiacoes']['titulo']) ?>
    <div class="premios">
      <?php foreach ($c['premiacoes']['itens'] as $i => $p): ?>
        <div class="duas-colunas<?= $i % 2 ? ' inverter' : '' ?>">
          <?= ht_img($p['foto'], '(max-width: 899px) 100vw, 560px', ['class' => 'imagem-arredondada']) ?>
          <div class="texto"><?= $p['texto'] ?></div>
        </div>
      <?php endforeach ?>
    </div>
  </div>
</section>

<section class="secao secao-azul" id="parceiros">
  <div class="container">
    <?= ht_secao_titulo($c['titulo_parceiros']) ?>
    <ul class="parceiros">
      <?php foreach ($parceiros as $p): ?>
        <li class="parceiro">
          <?= ht_img($p['logo'], '170px', ['alt' => $p['nome']]) ?>
          <p><?= e($p['texto']) ?></p>
          <?php if ($p['link']): ?><a href="<?= e(ht_link($p['link'])) ?>"<?= ht_alvo($p['link']) ?>>Saiba mais</a><?php endif ?>
        </li>
      <?php endforeach ?>
    </ul>
  </div>
</section>
