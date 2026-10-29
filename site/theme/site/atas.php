<?php
/** Atas de Registro de Preços. @var array $c campos (modelos/atas.php)  @var array $post */
?>
<section class="cabecalho">
  <div class="container">
    <div class="titulo"><h1><?= e($post['title']) ?></h1></div>
    <div class="texto"><?= $c['resumo'] ?></div>
  </div>
</section>

<section class="secao">
  <div class="container atas-chamada">
    <div class="texto texto-grande"><?= $c['texto'] ?></div>
    <div>
      <h2><?= e($c['titulo_atas']) ?></h2>
      <?= ht_botao($c['botao_atas'], 'botao', 'externo') ?>
    </div>
  </div>
</section>

<section class="secao secao-cinza">
  <div class="container duas-colunas">
    <?= ht_secao_titulo($c['duvida']['titulo']) ?>
    <div>
      <div class="texto"><?= $c['duvida']['texto'] ?></div>
      <div class="botoes">
        <?= ht_botao($c['duvida']['botao']) ?>
        <a href="#contato" class="botao botao-contorno">Fale com um especialista</a>
      </div>
    </div>
  </div>
</section>

<section class="secao">
  <div class="container duas-colunas">
    <?= ht_img($c['saiba_mais']['imagem'], '(max-width: 899px) 90vw, 420px', ['class' => 'imagem-arredondada saiba-imagem']) ?>
    <div>
      <?= ht_secao_titulo($c['saiba_mais']['titulo'], $c['saiba_mais']['sobretitulo']) ?>
      <div class="texto"><?= $c['saiba_mais']['texto'] ?></div>
    </div>
  </div>
</section>

<section class="secao escuro transparencia">
  <div class="container">
    <?php if ($c['transparencia']['sobretitulo']): ?><p class="sobretitulo"><?= e($c['transparencia']['sobretitulo']) ?></p><?php endif ?>
    <h2><?= ht_titulo($c['transparencia']['titulo']) ?></h2>
    <p><?= e($c['transparencia']['texto']) ?></p>
    <?= ht_botao($c['transparencia']['botao'], 'botao botao-claro', 'seta') ?>
  </div>
</section>
