<?php /** Página não encontrada. */ ?>
<div class="wrap">
  <section class="not-found">
    <p class="kicker">Erro 404</p>
    <h1>Página não encontrada</h1>
    <p class="muted">O endereço pode ter mudado ou o conteúdo foi removido. Que tal buscar?</p>
    <form class="search-big" action="<?= e(url('/')) ?>" method="get" role="search" data-suggest="<?= e(url('/search.json')) ?>">
      <input type="search" name="s" placeholder="O que você procura?" aria-label="Buscar">
      <button>Buscar</button>
    </form>
    <p><a href="<?= e(url('/')) ?>">← Voltar para o início</a></p>
  </section>
</div>
