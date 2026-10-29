<?php
/** Google para Empresas (Chrome Enterprise). @var array $c campos (modelos/googleparaempresas.php) */
$cases = array_filter(array_map(fn($i) => $i['post'] ? Posts::findBySlug($i['post']) : null, $c['cases']['posts']));
$cases = array_filter($cases, fn($p) => Posts::isPublic($p));
?>
<section class="cabecalho">
  <div class="container duas-colunas">
    <div>
      <?= ht_img($c['intro']['logo'], '200px', ['alt' => 'Google Chrome Enterprise', 'class' => 'intro-logo', 'loading' => 'eager']) ?>
      <h1><?= ht_titulo($c['intro']['titulo']) ?></h1>
      <p class="texto-grande"><?= ht_titulo($c['intro']['texto']) ?></p>
      <a href="#contato" class="botao botao-claro">Fale com um especialista</a>
    </div>
    <?= ht_img($c['intro']['imagem'], '(max-width: 899px) 90vw, 560px', ['alt' => 'Chromebook', 'class' => 'cabecalho-imagem', 'loading' => 'eager']) ?>
  </div>
</section>

<section class="secao">
  <div class="container">
    <div class="texto texto-grande definicao"><?= $c['definicao']['texto'] ?></div>
    <ul class="publicos">
      <?php foreach ($c['definicao']['blocos'] as $b): ?>
        <li><?= ht_img($b['imagem'], '300px') ?><h3><?= e($b['titulo']) ?></h3></li>
      <?php endforeach ?>
    </ul>
  </div>
</section>

<section class="secao secao-cinza">
  <div class="container conteudos">
    <?php foreach ($c['conteudo'] as $i => $item): ?>
      <div class="duas-colunas<?= $i % 2 ? ' inverter' : '' ?>">
        <?= ht_img($item['imagem'], '(max-width: 899px) 90vw, 485px', ['class' => 'imagem-arredondada']) ?>
        <div><h2><?= e($item['titulo']) ?></h2><div class="texto"><?= $item['texto'] ?></div></div>
      </div>
    <?php endforeach ?>
  </div>
</section>

<?php if ($cases): ?>
  <section class="secao">
    <div class="container">
      <?= ht_secao_titulo($c['cases']['titulo'], $c['cases']['sobretitulo']) ?>
      <ul class="cartoes"><?php foreach ($cases as $p): ?><?= ht_cartao($p) ?><?php endforeach ?></ul>
      <a href="<?= e(url('/category/cases/')) ?>" class="botao botao-contorno">Veja mais cases <?= ht_icone('seta', 18) ?></a>
    </div>
  </section>
<?php endif ?>

<section class="secao secao-marinho">
  <div class="container duas-colunas">
    <div>
      <?= ht_secao_titulo($c['ebook']['titulo'], $c['ebook']['sobretitulo']) ?>
      <div class="texto texto-grande"><?= $c['ebook']['texto'] ?></div>
      <?php if ($c['ebook']['arquivo']): ?>
        <a href="<?= e(ht_link($c['ebook']['arquivo'])) ?>" class="botao botao-claro" download><?= e($c['ebook']['botao'] ?: 'Baixe agora') ?> <?= ht_icone('download', 18) ?></a>
      <?php endif ?>
    </div>
    <?= ht_img($c['ebook']['imagem'], '320px', ['alt' => 'Capa do e-book', 'class' => 'ebook-capa']) ?>
  </div>
</section>

<section class="secao">
  <div class="container">
    <?= ht_secao_titulo($c['blog']['titulo'], $c['blog']['sobretitulo']) ?>
    <?= ht_blog_newsletter(ht_ultimos(3), ['texto' => 'Ver todas as notícias', 'link' => '/blog/']) ?>
  </div>
</section>
