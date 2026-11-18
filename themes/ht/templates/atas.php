<?php
/** Atas de Registro de Preços. @var array $fields campos (fields/atas.php)  @var array $post */
?>
<section class="cabecalho">
  <div class="container">
    <div class="titulo"><h1><?= e($post['title']) ?></h1></div>
    <div class="texto"><?= $fields['resumo'] ?></div>
  </div>
</section>

<section class="secao">
  <div class="container atas-chamada">
    <div class="texto texto-grande"><?= $fields['texto'] ?></div>
    <div>
      <h2><?= e($fields['titulo_atas']) ?></h2>
      <?= ht_botao($fields['botao_atas'], 'botao', 'externo') ?>
    </div>
  </div>
</section>

<section class="secao secao-cinza">
  <div class="container duas-colunas">
    <?= ht_secao_titulo($fields['duvida']['titulo']) ?>
    <div>
      <div class="texto"><?= $fields['duvida']['texto'] ?></div>
      <div class="botoes">
        <?= ht_botao($fields['duvida']['botao']) ?>
        <a href="#contato" class="botao botao-contorno">Fale com um especialista</a>
      </div>
    </div>
  </div>
</section>

<section class="secao">
  <div class="container duas-colunas">
    <?= ht_img($fields['saiba_mais']['imagem'], '(max-width: 899px) 90vw, 420px', ['class' => 'imagem-arredondada saiba-imagem']) ?>
    <div>
      <?= ht_secao_titulo($fields['saiba_mais']['titulo'], $fields['saiba_mais']['sobretitulo']) ?>
      <div class="texto"><?= $fields['saiba_mais']['texto'] ?></div>
    </div>
  </div>
</section>

<section class="secao escuro transparencia">
  <div class="container">
    <?php if ($fields['transparencia']['sobretitulo']): ?><p class="sobretitulo"><?= e($fields['transparencia']['sobretitulo']) ?></p><?php endif ?>
    <h2><?= ht_titulo($fields['transparencia']['titulo']) ?></h2>
    <p><?= e($fields['transparencia']['texto']) ?></p>
    <?= ht_botao($fields['transparencia']['botao'], 'botao botao-claro', 'seta') ?>
  </div>
</section>
