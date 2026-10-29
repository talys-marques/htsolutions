<?php
/** Canais de transparência (Certigov). @var array $c campos (modelos/certigov.php) */
$f = $c['fabricantes'];
?>
<section class="cabecalho">
  <div class="container duas-colunas">
    <div>
      <h1><?= ht_titulo($c['intro']['titulo']) ?></h1>
      <p class="texto-grande"><?= e($c['intro']['texto']) ?></p>
    </div>
    <?= ht_img($c['intro']['imagem'], '(max-width: 899px) 80vw, 480px', ['class' => 'cabecalho-imagem', 'loading' => 'eager']) ?>
  </div>
</section>

<?php if ($f['imagem']): ?>
  <div class="faixa-fabricantes">
    <div class="container">
      <?php // Logos brancos: precisam da faixa azul-escura atrás. A versão quadrada é para celular. ?>
      <?= ht_img($f['imagem'], '(max-width: 1247px) 100vw, 1200px', ['alt' => 'Fabricantes parceiros', 'class' => 'largo']) ?>
      <?= ht_img($f['imagem_mobile'] ?: $f['imagem'], '300px', ['alt' => 'Fabricantes parceiros', 'class' => 'mobile']) ?>
    </div>
  </div>
<?php endif ?>

<section class="secao">
  <div class="container">
    <p class="texto-grande centro"><?= e($f['texto']) ?></p>
    <?= ht_img($c['certificado'], '(max-width: 899px) 100vw, 700px', ['alt' => 'Certificado Certigov da HT Solutions', 'class' => 'certificado']) ?>
  </div>
</section>

<section class="secao secao-cinza">
  <div class="container duas-colunas">
    <div>
      <h2><?= e($c['comite']['titulo']) ?></h2>
      <p class="texto-grande"><?= e($c['comite']['texto']) ?></p>
    </div>
    <?= ht_img($c['comite']['imagem'], '(max-width: 899px) 80vw, 426px', ['class' => 'comite']) ?>
  </div>
</section>

<section class="secao secao-marinho">
  <div class="container contato-etica">
    <div>
      <h2><?= e($c['diretor']['titulo']) ?></h2>
      <p><?= e($c['diretor']['texto']) ?></p>
      <?php if ($c['diretor']['email']): ?>
        <a href="mailto:<?= e($c['diretor']['email']) ?>" class="botao botao-claro"><?= ht_icone('email', 18) ?> <?= e($c['diretor']['botao'] ?: 'Falar com o diretor') ?></a>
      <?php endif ?>
    </div>
    <div>
      <h2><?= e($c['etica']['titulo']) ?></h2>
      <div class="texto"><?= $c['etica']['texto'] ?></div>
    </div>
  </div>
</section>
