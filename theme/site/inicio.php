<?php
/** Página inicial. @var array $c campos (modelos/inicio.php) */
$ab = $c['abertura'];
// A última palavra do título ganha o degradê ("Tecnologia que <strong>performa</strong>")
$tituloHero = preg_replace('/(\S+)$/u', '<strong>$1</strong>', e($ab['titulo']));
$geral = ht_geral();
?>
<section class="hero escuro">
  <div class="container hero-h">
    <div class="hero-texto">
      <?php if ($ab['sobretitulo']): ?><p class="sobretitulo"><?= e($ab['sobretitulo']) ?></p><?php endif ?>
      <h1><?= $tituloHero ?></h1>
      <p class="hero-lead"><?= e($ab['texto']) ?></p>
      <div class="botoes">
        <?= ht_botao($ab['botao'], 'botao', 'seta') ?>
        <?= ht_botao($ab['botao_secundario'], 'botao botao-fantasma') ?>
      </div>
      <?php if ($ab['marcas']): ?>
        <p class="hero-marcas">Parceira <?= implode(' · ', array_map(fn($m) => '<span>' . e(trim($m)) . '</span>', explode('·', $ab['marcas']))) ?></p>
      <?php endif ?>
    </div>
    <div class="hero-colagem" aria-hidden="true">
      <?= ht_img($ab['foto_1'], '(max-width: 899px) 70vw, 420px', ['class' => 'colagem-a', 'loading' => 'eager', 'fetchpriority' => 'high']) ?>
      <?= ht_img($ab['foto_2'], '240px', ['class' => 'colagem-b', 'loading' => 'eager']) ?>
      <?= ht_img($ab['foto_3'], '240px', ['class' => 'colagem-c', 'loading' => 'eager']) ?>
      <?php if ($ab['selo_valor']): ?>
        <div class="colagem-selo"><strong><?= e($ab['selo_valor']) ?></strong><span><?= e($ab['selo_texto']) ?></span></div>
      <?php endif ?>
    </div>
  </div>
</section>

<?php if ($c['atalhos']): ?>
  <section class="atalhos-secao" aria-label="Acesso rápido">
    <div class="container">
      <ul class="atalhos">
        <?php foreach ($c['atalhos'] as $a): ?>
          <li>
            <a href="<?= e(ht_link($a['link'])) ?>" class="atalho"<?= ht_alvo($a['link']) ?>>
              <span class="atalho-icone"><?= ht_icone($a['icone'], 22) ?></span>
              <span class="atalho-corpo">
                <strong><?= e($a['titulo']) ?></strong>
                <span><?= e($a['texto']) ?></span>
                <span class="atalho-link"><?= e($a['rotulo']) ?> <?= ht_icone(ht_alvo($a['link']) ? 'externo' : 'seta', 16) ?></span>
              </span>
            </a>
          </li>
        <?php endforeach ?>
      </ul>
    </div>
  </section>
<?php endif ?>

<?php if ($c['numeros']): ?>
  <section class="numeros-secao" aria-label="A HT em números">
    <div class="container">
      <ul class="numeros-home">
        <?php foreach ($c['numeros'] as $n): ?><li><strong><?= e($n['valor']) ?></strong> <span><?= e($n['texto']) ?></span></li><?php endforeach ?>
      </ul>
    </div>
  </section>
<?php endif ?>

<?php $s = $c['solucoes']; ?>
<section class="secao">
  <div class="container">
    <div class="solucoes-topo">
      <?= ht_secao_titulo($s['titulo'], $s['sobretitulo']) ?>
      <div>
        <div class="texto"><?= $s['texto'] ?></div>
        <?= ht_botao($s['link'], 'link-seta', 'seta') ?>
      </div>
    </div>
    <ul class="bento">
      <?php foreach ($s['itens'] as $i => $item): ?>
        <li<?= $i === 0 ? ' class="grande"' : '' ?>>
          <a href="<?= e(ht_link($item['link'] ?: '/servicos/')) ?>">
            <?= ht_img($item['imagem'], $i === 0 ? '(max-width: 899px) 100vw, 760px' : '(max-width: 899px) 100vw, 400px') ?>
            <span class="bento-texto"><strong><?= e($item['titulo']) ?></strong><span><?= e($item['texto']) ?></span></span>
            <span class="bento-seta"><?= ht_icone('seta') ?></span>
          </a>
        </li>
      <?php endforeach ?>
    </ul>
  </div>
</section>

<?php $q = $c['quem_somos']; ?>
<section class="secao secao-cinza">
  <div class="container duas-colunas">
    <div>
      <?= ht_secao_titulo($q['titulo'], $q['sobretitulo']) ?>
      <div class="texto texto-grande"><?= $q['texto'] ?></div>
      <?php if ($q['destaques']): ?>
        <ul class="lista-check">
          <?php foreach ($q['destaques'] as $d): ?><li><?= ht_icone('ok') ?> <?= e($d['texto']) ?></li><?php endforeach ?>
        </ul>
      <?php endif ?>
      <?= ht_botao($q['botao'], 'botao botao-contorno') ?>
    </div>
    <?php if ($q['video']): ?>
      <div class="video-moldura"><video class="video" src="<?= e(ht_link($q['video'])) ?>" controls preload="metadata" playsinline></video></div>
    <?php endif ?>
  </div>
</section>

<?php $p = $c['parceiros']; ?>
<section class="secao">
  <div class="container">
    <?= ht_secao_titulo($p['titulo'], $p['sobretitulo'], 2, true) ?>
    <ul class="logos">
      <?php foreach ($geral['parceiros'] ?? [] as $parceiro): ?>
        <?php if ($parceiro['logo']): ?>
          <li title="<?= e($parceiro['texto']) ?>"><?= ht_img($parceiro['logo'], '170px', ['alt' => $parceiro['nome']]) ?></li>
        <?php endif ?>
      <?php endforeach ?>
    </ul>
    <p class="centro-link"><?= ht_botao($p['link'], 'link-seta', 'seta') ?></p>
  </div>
</section>

<section class="secao secao-cinza">
  <div class="container">
    <?= ht_secao_titulo($c['cases']['titulo'], $c['cases']['sobretitulo']) ?>
    <div class="ultimos">
      <ul class="cartoes"><?php foreach (ht_ultimos(3, 'cases') as $post): ?><?= ht_cartao($post) ?><?php endforeach ?></ul>
      <?= ht_botao($c['cases']['botao'], 'botao botao-contorno', 'seta') ?>
    </div>
  </div>
</section>

<section class="secao">
  <div class="container">
    <?= ht_secao_titulo($c['blog']['titulo'], $c['blog']['sobretitulo']) ?>
    <?= ht_blog_newsletter(ht_ultimos(3), $c['blog']['botao']) ?>
  </div>
</section>
