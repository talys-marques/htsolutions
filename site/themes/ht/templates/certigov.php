<?php
/** Canais de transparência (Certigov). @var array $fields campos (fields/certigov.php) */
$f = $fields['fabricantes'];
?>
<section class="cabecalho">
  <div class="container duas-colunas">
    <div>
      <h1><?= ht_titulo($fields['intro']['titulo']) ?></h1>
      <p class="texto-grande"><?= e($fields['intro']['texto']) ?></p>
    </div>
    <?= ht_img($fields['intro']['imagem'], '(max-width: 899px) 80vw, 480px', ['class' => 'cabecalho-imagem', 'loading' => 'eager']) ?>
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
    <?= ht_img($fields['certificado'], '(max-width: 899px) 100vw, 700px', ['alt' => 'Certificado Certigov da HT Solutions', 'class' => 'certificado']) ?>
  </div>
</section>

<section class="secao secao-cinza">
  <div class="container duas-colunas">
    <div>
      <h2><?= e($fields['comite']['titulo']) ?></h2>
      <p class="texto-grande"><?= e($fields['comite']['texto']) ?></p>
    </div>
    <?= ht_img($fields['comite']['imagem'], '(max-width: 899px) 80vw, 426px', ['class' => 'comite']) ?>
  </div>
</section>

<section class="secao secao-marinho">
  <div class="container contato-etica">
    <div>
      <h2><?= e($fields['diretor']['titulo']) ?></h2>
      <p><?= e($fields['diretor']['texto']) ?></p>
      <?php if ($fields['diretor']['email']): ?>
        <a href="mailto:<?= e($fields['diretor']['email']) ?>" class="botao botao-claro"><?= ht_icone('email', 18) ?> <?= e($fields['diretor']['botao'] ?: 'Falar com o diretor') ?></a>
      <?php endif ?>
    </div>
    <div>
      <h2><?= e($fields['etica']['titulo']) ?></h2>
      <div class="texto"><?= $fields['etica']['texto'] ?></div>
    </div>
  </div>
</section>
