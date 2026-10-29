<?php /** @var string $text  @var string|null $error  @var array $misses  endereços com 404: { count, last, from, suggest } */ ?>
<div class="page-head"><h1>Redirecionamentos</h1></div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif ?>

<?php if ($misses): ?>
  <section class="panel stack">
    <h2>Endereços não encontrados (404)</h2>
    <p class="muted">Endereços que visitantes ou o Google tentaram abrir e não existem, dos mais acessados para os menos.
      Dê um destino a cada um para não perder visitas nem posição no Google. Sugestões vêm de posts com o mesmo final de endereço.</p>
    <div class="table-wrap">
      <table class="table compact misses">
        <thead><tr><th>Endereço</th><th>Redirecionar para</th><th class="col-actions"></th></tr></thead>
        <tbody>
          <?php foreach ($misses as $path => $miss): ?>
            <tr>
              <td class="col-title">
                <a href="<?= e(url($path)) ?>" target="_blank" rel="noopener"><?= e($path) ?></a>
                <div class="row-meta">
                  <?= (int) $miss['count'] ?> <?= $miss['count'] === 1 ? 'acesso' : 'acessos' ?> · último em <?= e(format_date($miss['last'], true)) ?>
                  <?php if ($miss['from'] !== ''): ?> · link em <?= e($miss['from']) ?><?php endif ?>
                </div>
              </td>
              <td>
                <form method="post" class="row miss-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="miss">
                  <input type="hidden" name="from" value="<?= e($path) ?>">
                  <input name="to" value="<?= e($miss['suggest']) ?>" placeholder="/endereco-novo/" aria-label="Destino de <?= e($path) ?>" required>
                  <button class="btn btn-sm">Redirecionar</button>
                </form>
              </td>
              <td class="col-actions">
                <form method="post">
                  <?= csrf_field() ?><input type="hidden" name="action" value="forget"><input type="hidden" name="from" value="<?= e($path) ?>">
                  <button class="link" title="Tirar da lista sem redirecionar">Ignorar</button>
                </form>
              </td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <form method="post" data-confirm="Limpar a lista de endereços não encontrados?">
      <?= csrf_field() ?><input type="hidden" name="action" value="forget">
      <button class="link">Limpar a lista</button>
    </form>
  </section>
<?php endif ?>

<form method="post" class="panel stack">
  <?= csrf_field() ?>
  <h2>Todos os redirecionamentos</h2>
  <p class="muted">
    Endereços antigos que devem levar a um endereço novo (redirecionamento 301, que preserva o Google).
    Um por linha: <code>/endereco-antigo /endereco-novo/</code>. Linhas começando com <code>#</code> são ignoradas.
  </p>
  <p class="muted">São criados automaticamente quando você muda o endereço de um post publicado e na importação do WordPress.
    Só valem quando não existe uma página no endereço antigo.</p>
  <textarea name="redirects" rows="18" class="mono" spellcheck="false" placeholder="/2019/05/meu-post-antigo /meu-post-antigo/&#10;/contato.html /contato/"><?= e($text) ?></textarea>
  <div><button class="btn btn-primary">Salvar redirecionamentos</button></div>
</form>
