<?php /** @var array $counts  @var array $checks */ ?>
<div class="page-head"><h1>Backup e ferramentas</h1></div>

<div class="panel sections">
  <section class="section">
    <header class="section-head">
      <h2>Backup</h2>
      <p>Uma cópia completa do blog, pronta para guardar.</p>
    </header>
    <div class="stack">
      <p class="muted">Todo o blog cabe em <code>data/</code> (posts, usuários, configurações) e nas pastas de arquivos enviados:
        <code>public/uploads/</code> e, se o blog veio do WordPress, <code>public/wp-content/uploads/</code>.
        Baixe um backup aqui ou copie essas pastas por FTP (com centenas de MB em arquivos, o FTP costuma ser mais rápido).
        Para restaurar, basta descompactar por cima da pasta do blog.</p>
      <form method="post" action="<?= e(url('/admin/tools/backup')) ?>" class="row">
        <?= csrf_field() ?>
        <label class="check"><input type="checkbox" name="uploads"> Incluir os arquivos enviados, inclusive os do WordPress (pode ficar grande)</label>
        <button class="btn btn-primary">Baixar backup</button>
      </form>
    </div>
  </section>

  <section class="section">
    <header class="section-head">
      <h2>Exportar</h2>
      <p>O conteúdo num arquivo .xml no formato do WordPress.</p>
    </header>
    <div class="stack">
      <p class="muted">É o mesmo arquivo de <strong>Ferramentas → Exportar</strong> do WordPress: posts e páginas (inclusive rascunhos e agendados),
        categorias, tags, autores, capas, imagens e título e descrição SEO. Serve para levar o blog para o WordPress
        (<strong>Ferramentas → Importar → WordPress</strong>) ou para outras plataformas que aceitam esse formato.</p>
      <p class="muted">As imagens não vão dentro do arquivo: o importador as baixa de <code><?= e(site_url()) ?></code>, que precisa estar no ar
        (se esse não for o endereço público, ajuste em Configurações). No WordPress, marque <strong>Baixar e importar arquivos anexos</strong>.
        Configurações, menu e senhas ficam de fora; para uma cópia completa, use o backup.</p>
      <form method="post" action="<?= e(url('/admin/tools/export')) ?>" class="stack">
        <?= csrf_field() ?>
        <div class="row choices-inline">
          <?php foreach (Export::CONTENT as $value => $label): ?>
            <label class="check"><input type="radio" name="content" value="<?= e($value) ?>"<?= $value === 'all' ? ' checked' : '' ?>> <?= e($label) ?></label>
          <?php endforeach ?>
        </div>
        <div><button class="btn btn-primary">Baixar exportação</button></div>
      </form>
    </div>
  </section>

  <section class="section">
    <header class="section-head">
      <h2>Conteúdo</h2>
      <p>O que está guardado hoje e a manutenção do índice.</p>
    </header>
    <div class="stack">
      <ul class="stats">
        <li><strong><?= (int) $counts['post'] ?></strong> posts</li>
        <li><strong><?= (int) $counts['page'] ?></strong> páginas</li>
        <li><strong><?= (int) $counts['trash'] ?></strong> na lixeira</li>
      </ul>
      <p class="muted">Editou ou copiou arquivos de <code>data/posts/</code> na mão? Reconstrua o índice para o site enxergar as mudanças.</p>
      <form method="post" action="<?= e(url('/admin/tools/reindex')) ?>">
        <?= csrf_field() ?><button class="btn">Reconstruir índice</button>
      </form>
    </div>
  </section>

  <section class="section">
    <header class="section-head">
      <h2>Servidor</h2>
      <p>Requisitos da hospedagem e permissões de escrita.</p>
    </header>
    <div>
      <table class="table compact">
        <tbody>
          <?php foreach ($checks as $label => [$value, $ok]): ?>
            <tr><th><?= e($label) ?></th><td><?= e($value) ?></td><td><?= $ok ? '<span class="badge badge-published">ok</span>' : '<span class="badge badge-trash">verificar</span>' ?></td></tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
