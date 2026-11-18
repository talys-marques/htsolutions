<?php
/** Soluções e serviços. @var array $fields campos (fields/servicos.php) */
$icones = function (array $itens): string {
    $html = '<ul class="icones">';
    foreach ($itens as $i) {
        $html .= '<li>' . ht_img($i['imagem'], '44px', ['width' => 44, 'height' => 44]) . ' ' . e($i['titulo']) . '</li>';
    }
    return $html . '</ul>';
};
?>
<section class="cabecalho">
  <div class="container duas-colunas">
    <div>
      <div class="titulo"><h1><?= ht_titulo($fields['intro']['titulo']) ?></h1></div>
      <div class="texto"><?= $fields['intro']['texto'] ?></div>
    </div>
    <?= ht_img($fields['intro']['imagem'], '(max-width: 899px) 90vw, 500px', ['class' => 'cabecalho-imagem', 'loading' => 'eager']) ?>
  </div>
</section>

<section class="secao" id="google">
  <div class="container duas-colunas">
    <div>
      <?= ht_secao_titulo($fields['google']['titulo']) ?>
      <div class="texto texto-grande"><?= $fields['google']['texto'] ?></div>
      <div class="botoes">
        <?php foreach ($fields['google']['botoes'] as $b): ?><?= ht_botao($b) ?><?php endforeach ?>
      </div>
    </div>
    <ul class="logos-google">
      <?php foreach ($fields['google']['logos'] as $l): ?><li><?= ht_img($l['imagem'], '300px') ?></li><?php endforeach ?>
    </ul>
  </div>
</section>

<section class="secao secao-cinza" id="client">
  <div class="container">
    <?= ht_secao_titulo($fields['client']['titulo']) ?>
    <div class="texto texto-grande intro-texto"><?= $fields['client']['texto'] ?></div>
    <ul class="blocos">
      <?php foreach ($fields['client']['itens'] as $i): ?>
        <li class="bloco bloco-produto">
          <?= ht_img($i['imagem'], '300px') ?>
          <div class="bloco-corpo"><h3><?= e($i['titulo']) ?></h3><p><?= e($i['texto']) ?></p></div>
        </li>
      <?php endforeach ?>
    </ul>
  </div>
</section>

<section class="secao" id="enterprise">
  <div class="container duas-colunas">
    <div>
      <?= ht_secao_titulo($fields['enterprise']['titulo']) ?>
      <div class="texto texto-grande"><?= $fields['enterprise']['texto'] ?></div>
      <ul class="lista-check">
        <?php foreach ($fields['enterprise']['lista'] as $l): ?><li><?= ht_icone('ok') ?> <span><?= ht_titulo($l['texto']) ?></span></li><?php endforeach ?>
      </ul>
    </div>
    <?= ht_img($fields['enterprise']['imagem'], '(max-width: 899px) 80vw, 420px', ['class' => 'enterprise-imagem']) ?>
  </div>
</section>

<section class="secao secao-azul" id="servicos">
  <div class="container">
    <?= ht_secao_titulo($fields['servicos_enterprise']['titulo']) ?>
    <div class="texto texto-grande intro-texto"><?= $fields['servicos_enterprise']['texto'] ?></div>
    <?= $icones($fields['servicos_enterprise']['itens']) ?>

    <div class="servicos-client">
      <?= ht_secao_titulo($fields['servicos_client']['titulo']) ?>
      <div class="texto texto-grande intro-texto"><?= $fields['servicos_client']['texto'] ?></div>
      <?= $icones($fields['servicos_client']['itens']) ?>
    </div>
  </div>
</section>

<section class="secao secao-marinho chamado">
  <div class="container">
    <h2><?= e($fields['chamado']['titulo']) ?></h2>
    <?= ht_botao($fields['chamado']['botao'], 'botao botao-claro', 'externo') ?>
  </div>
</section>
