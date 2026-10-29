<?php
/**
 * Lista de posts ou páginas.
 * @var string $type  @var string $status  @var string $q  @var array $counts  @var array $pagination
 */
$isPage = $type === 'page';
$tabs = ['' => 'Todos', 'published' => 'Publicados', 'scheduled' => 'Agendados', 'draft' => 'Rascunhos', 'trash' => 'Lixeira'];
$link = function (array $params) use ($isPage, $status, $q) {
    $params += ['type' => $isPage ? 'page' : '', 'status' => $status, 'q' => $q];
    $query = http_build_query(array_filter($params, fn($v) => $v !== '' && $v !== 1));
    return url('/admin/posts') . ($query !== '' ? '?' . $query : '');
};
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <a class="btn btn-primary" href="<?= e(url('/admin/posts/new' . ($isPage ? '?type=page' : ''))) ?>"><?= $isPage ? 'Nova página' : 'Novo post' ?></a>
</div>

<div class="toolbar">
  <nav class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
      <?php if ($key === '' || $counts[$key] > 0 || $status === $key): ?>
        <a href="<?= e($link(['status' => $key, 'q' => ''])) ?>"<?= $status === $key ? ' class="active"' : '' ?>><?= $label ?> <span class="count"><?= $counts[$key] ?></span></a>
      <?php endif ?>
    <?php endforeach ?>
  </nav>
  <form class="search" method="get" action="<?= e(url('/admin/posts')) ?>">
    <?php if ($isPage): ?><input type="hidden" name="type" value="page"><?php endif ?>
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar…" aria-label="Buscar">
  </form>
</div>

<?php if (!$pagination['items']): ?>
  <div class="empty">
    <?php if ($q !== ''): ?>
      <p>Nada encontrado para “<?= e($q) ?>”.</p>
    <?php elseif ($status === 'trash'): ?>
      <p>A lixeira está vazia.</p>
    <?php else: ?>
      <p><?= $isPage ? 'Nenhuma página ainda. Páginas servem para conteúdos fixos, como “Sobre” e “Contato”.' : 'Nenhum post ainda.' ?></p>
      <a class="btn btn-primary" href="<?= e(url('/admin/posts/new' . ($isPage ? '?type=page' : ''))) ?>"><?= $isPage ? 'Criar a primeira página' : 'Escrever o primeiro post' ?></a>
    <?php endif ?>
  </div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Título</th>
          <th>Status</th>
          <?php if (!$isPage): ?><th class="hide-sm">Categorias</th><?php endif ?>
          <th class="hide-sm">Autor</th>
          <th>Data</th>
          <th class="col-actions"><span class="sr-only">Ações</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pagination['items'] as $item): ?>
          <tr>
            <td class="col-title">
              <a href="<?= e(url('/admin/posts/' . $item['id'])) ?>"><?= e($item['title']) ?></a>
              <div class="row-meta">/<?= e($item['slug']) ?>/<?php if ($role = Admin::pageRole($item)): ?> · <strong><?= e($role) ?></strong><?php endif ?></div>
            </td>
            <td><?= Admin::badge($item) ?></td>
            <?php if (!$isPage): ?><td class="hide-sm muted"><?= e(implode(', ', $item['categories'])) ?></td><?php endif ?>
            <td class="hide-sm muted"><?= e(Auth::find($item['author'])['name'] ?? $item['author']) ?></td>
            <td class="muted nowrap"><?= e(date('d/m/Y', $item['ts'])) ?></td>
            <td class="col-actions">
              <?php if ($item['status'] === 'trash'): ?>
                <form method="post" action="<?= e(url('/admin/posts/' . $item['id'] . '/restore')) ?>"><?= csrf_field() ?><button class="link">Restaurar</button></form>
                <form method="post" action="<?= e(url('/admin/posts/' . $item['id'] . '/delete')) ?>" data-confirm="Excluir “<?= e($item['title']) ?>” para sempre? Não dá para desfazer."><?= csrf_field() ?><button class="link danger">Excluir</button></form>
              <?php else: ?>
                <a href="<?= e(Posts::isPublic($item) ? post_url($item) : url('/admin/preview/' . $item['id'])) ?>" target="_blank" rel="noopener">Ver</a>
                <form method="post" action="<?= e(url('/admin/posts/' . $item['id'] . '/trash')) ?>"><?= csrf_field() ?><button class="link danger">Lixeira</button></form>
              <?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?= render_file(__DIR__ . '/_pagination.php', ['pagination' => $pagination, 'link' => fn($n) => $link(['page' => $n])]) ?>
<?php endif ?>
