<?php /** @var string|null $error  @var array|null $report  @var array $files  @var int $maxUpload  @var array|null $progress  @var array $missing  @var string $oldUrl */ ?>
<div class="page-head"><h1>Importar do WordPress</h1></div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif ?>

<?php if ($report): ?>
  <section class="panel stack report">
    <h2>Importação concluída</h2>
    <ul class="stats">
      <li><strong><?= (int) $report['posts'] ?></strong> posts</li>
      <li><strong><?= (int) $report['pages'] ?></strong> páginas</li>
      <li><strong><?= (int) $report['with_cover'] ?></strong> com imagem de capa</li>
      <li><strong><?= (int) $report['redirects'] ?></strong> redirecionamentos</li>
      <?php if ($report['updated']): ?><li><strong><?= (int) $report['updated'] ?></strong> já existiam e foram atualizados</li><?php endif ?>
      <?php if ($report['skipped']): ?><li><strong><?= (int) $report['skipped'] ?></strong> ignorados (lixeira, rascunhos automáticos…)</li><?php endif ?>
    </ul>
    <?php if ($report['old_url']): ?>
      <p class="muted">Links de <code><?= e($report['old_url']) ?></code> foram convertidos para endereços internos.</p>
    <?php endif ?>
    <?php if ($report['authors']): ?>
      <div class="alert alert-warning">Autores do WordPress sem usuário aqui (os posts ficaram com você): <strong><?= e(implode(', ', array_keys($report['authors']))) ?></strong>.
        Para manter a autoria, crie usuários com o mesmo login em Usuários e importe de novo.</div>
    <?php endif ?>
    <?php if ($report['shortcodes']): ?>
      <div class="alert alert-warning">
        Shortcodes de plugins que continuam no texto (aparecem como texto até você ajustar):
        <?php foreach (array_slice($report['shortcodes'], 0, 15, true) as $name => $count): ?>
          <code>[<?= e($name) ?>]</code> ×<?= (int) $count ?>
        <?php endforeach ?>
      </div>
    <?php endif ?>
    <?php if ($missing): ?><p><strong>Falta só um passo:</strong> trazer as imagens (veja abaixo).</p><?php endif ?>
    <p><a class="btn btn-primary" href="<?= e(url('/admin/posts')) ?>">Ver os posts importados</a></p>
  </section>
<?php endif ?>

<?php if ($progress && !$progress['finished']): ?>
  <?php $processed = $progress['ok'] + $progress['failed'] ?>
  <form method="post" class="panel stack" data-autosubmit>
    <?= csrf_field() ?>
    <input type="hidden" name="step" value="images">
    <input type="hidden" name="old_url" value="<?= e($progress['old_url']) ?>">
    <input type="hidden" name="after" value="<?= e($progress['after']) ?>">
    <input type="hidden" name="total" value="<?= (int) $progress['total'] ?>">
    <input type="hidden" name="ok" value="<?= (int) $progress['ok'] ?>">
    <input type="hidden" name="failed" value="<?= (int) $progress['failed'] ?>">
    <h2>Baixando imagens de <?= e($progress['old_url']) ?>…</h2>
    <progress max="<?= (int) $progress['total'] ?>" value="<?= (int) $processed ?>"></progress>
    <p><?= (int) $processed ?> de <?= (int) $progress['total'] ?><?php if ($progress['failed']): ?> · <?= (int) $progress['failed'] ?> não encontradas<?php endif ?>.
      Deixe esta página aberta: ela continua sozinha. Se parar, o que já foi baixado fica.</p>
    <div class="row">
      <button class="btn">Continuar</button>
      <a class="btn btn-ghost" href="<?= e(url('/admin/import')) ?>">Parar</a>
    </div>
  </form>
<?php else: ?>
  <?php if ($progress): ?>
    <div class="alert alert-success"><?= (int) $progress['ok'] ?> imagens baixadas de <?= e($progress['old_url']) ?>.</div>
  <?php endif ?>
  <?php if ($missing): ?>
    <form method="post" class="panel stack">
      <?= csrf_field() ?>
      <input type="hidden" name="step" value="images">
      <h2>Imagens</h2>
      <?php if ($progress): ?>
        <p><strong><?= count($missing) ?></strong> imagens não foram encontradas no site antigo, por exemplo:</p>
        <ul>
          <?php foreach (array_slice($missing, 0, 5) as $path): ?><li><code><?= e($path) ?></code></li><?php endforeach ?>
        </ul>
        <p class="muted">Pode ser que já estivessem quebradas no WordPress. Se tiver a pasta <code>wp-content/uploads</code>, copie esses arquivos para <code>public/wp-content/uploads</code>.</p>
      <?php else: ?>
        <p><strong><?= count($missing) ?></strong> imagens usadas nos posts ainda não estão aqui.
          Baixe do site antigo enquanto ele estiver no ar (antes de apontar o domínio para cá),
          ou copie a pasta <code>wp-content/uploads</code> do WordPress para <code>public/wp-content/uploads</code>.</p>
      <?php endif ?>
      <label class="field">Endereço do site antigo
        <input name="old_url" value="<?= e($oldUrl) ?>" placeholder="https://meublog.com.br" required>
      </label>
      <div><button class="btn btn-primary" data-busy="Começando…"><?= $progress ? 'Tentar de novo' : 'Baixar imagens do site antigo' ?></button></div>
    </form>
  <?php endif ?>
<?php endif ?>

<section class="panel stack">
  <h2>Como funciona</h2>
  <ol class="steps">
    <li>No WordPress, vá em <strong>Ferramentas → Exportar → Todo o conteúdo</strong> e baixe o arquivo <code>.xml</code>.</li>
    <li>Envie o arquivo abaixo<?php if ($maxUpload): ?> (ou, se for maior que <?= e(format_bytes($maxUpload)) ?>, coloque-o por FTP na pasta <code>data/import/</code>)<?php endif ?>.</li>
    <li>Traga as imagens: baixe do site antigo com o botão que aparece depois de importar, ou copie a pasta <code>wp-content/uploads</code> do WordPress para <code>public/wp-content/uploads</code>.</li>
  </ol>
  <p class="muted">Endereços, datas, categorias, tags, imagens de capa e descrições do Yoast/Rank Math são mantidos.
    Se o endereço de algum post mudar, um redirecionamento 301 é criado. Pode importar de novo quantas vezes quiser: os mesmos posts são atualizados, sem duplicar.</p>
</section>

<form method="post" enctype="multipart/form-data" class="panel stack">
  <?= csrf_field() ?>
  <h2>Importar</h2>
  <label class="field">Arquivo de exportação (.xml)
    <input type="file" name="xml" accept=".xml,text/xml,application/xml">
  </label>
  <?php if ($files): ?>
    <label class="field">…ou um arquivo que já está em <code>data/import/</code>
      <select name="existing">
        <option value="">—</option>
        <?php foreach ($files as $f): ?><option><?= e($f) ?></option><?php endforeach ?>
      </select>
    </label>
  <?php endif ?>
  <label class="field">Endereço do site antigo (opcional)
    <input name="old_url" placeholder="https://meublog.com.br">
    <span class="hint">Vazio = usa o endereço que está no arquivo.</span>
  </label>
  <label class="check"><input type="checkbox" name="drafts" checked> Importar rascunhos também</label>
  <div><button class="btn btn-primary" data-busy="Importando… pode levar alguns segundos">Importar</button></div>
</form>

<p class="hint">Também dá para importar pelo terminal: <code>php extensions/wordpress-import/cli.php exportacao.xml</code>
  (e baixar as imagens com <code>php extensions/wordpress-import/cli.php --imagens</code>).
  Depois de migrar, você pode apagar a pasta <code>extensions/wordpress-import/</code>: o conteúdo importado continua funcionando.</p>
