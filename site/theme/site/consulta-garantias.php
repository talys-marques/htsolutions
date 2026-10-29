<?php
/**
 * Consulta de garantia por número de série. A lista (public/assets/db_943hf3.bin) é decifrada no
 * navegador por public/assets/ht/paginas.js, como no site antigo. @var array $c  @var array $post
 */
$suporte = ht_geral()['suporte'] ?? [];
?>
<section class="cabecalho">
  <div class="container">
    <h1><?= e($post['title']) ?></h1>
    <p class="texto-grande"><?= e($c['texto']) ?></p>
  </div>
</section>

<section class="secao">
  <div class="container garantia" data-garantia="<?= e(url('/assets/db_943hf3.bin')) ?>">
    <form class="busca">
      <label for="serial" class="sr-only">Número de série</label>
      <input id="serial" type="text" placeholder="Digite o serial (ex.: ABC123456)" autocomplete="off" required>
      <button type="submit" class="botao"><?= ht_icone('busca', 18) ?> Pesquisar</button>
    </form>
    <p class="mensagem" role="status" hidden></p>

    <div class="resultado" hidden>
      <table><tbody></tbody></table>
      <div class="suporte">
        <strong><?= e($c['suporte_titulo']) ?></strong>
        <p><?= e($suporte['horario'] ?? '') ?></p>
        <?php if (!empty($suporte['telefone'])): ?><a href="<?= e(ht_tel($suporte['telefone'])) ?>"><?= ht_icone('telefone', 18) ?> <?= e($suporte['telefone']) ?></a><?php endif ?>
        <?php if (!empty($suporte['email'])): ?><a href="mailto:<?= e($suporte['email']) ?>" data-email><?= ht_icone('email', 18) ?> <?= e($suporte['email']) ?></a><?php endif ?>
      </div>
    </div>
  </div>
</section>
