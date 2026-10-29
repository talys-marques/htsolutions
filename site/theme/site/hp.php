<?php
/** HP Inc: vitrine de produtos da própria HP (script dela). @var array $c campos (modelos/hp.php) */
?>
<section class="cabecalho">
  <div class="container">
    <h1><?= ht_titulo($c['titulo']) ?></h1>
    <p class="texto-grande"><?= e($c['texto']) ?></p>
    <a href="#contato" class="botao botao-claro"><?= e($c['botao'] ?: 'Fale com um especialista') ?></a>
  </div>
</section>

<section class="vitrine-hp">
  <script src="https://syndication.inc.hp.com/inpage/contentid.min.js#Yz1zaG93Y2FzZS91cy9lbiZtPUxBJmNjPWJyJnA9My1IV0tLLTQ5"></script>
</section>
