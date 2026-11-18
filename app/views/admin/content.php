<?php
/**
 * /admin/content: páginas com modelo (Conteúdo do site) e os modelos que ainda não têm página.
 * @var array $templates  SiteContent::templates()  @var array $pages  [['item' => ..., 'template' => ...], ...]
 * @var array $missing  modelos de página única ainda sem página  @var int $installable  páginas no content/ do tema
 */
$repeatable = array_filter($templates, fn($t) => $t['repeatable']);
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <?php if ($repeatable): ?>
    <div class="head-actions">
      <?php foreach ($repeatable as $name => $t): ?>
        <form method="post" action="<?= e(url('/admin/content/new/' . $name)) ?>">
          <?= csrf_field() ?>
          <button class="btn">+ <?= e($t['label']) ?></button>
        </form>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>

<p class="muted">Textos, imagens, links e listas das páginas do site. A montagem de cada página é fixa: dá para trocar o conteúdo e acrescentar, mover ou tirar itens das listas. As mudanças entram no ar assim que você salva.</p>

<?php if ($installable): ?>
  <div class="alert alert-warning cf-install">
    <span>O tema ativo traz o conteúdo inicial de <?= $installable === 1 ? '1 página que ainda não existe' : "$installable páginas que ainda não existem" ?> aqui.</span>
    <form method="post" action="<?= e(url('/admin/content/install')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-primary">Criar <?= $installable === 1 ? 'a página' : 'as páginas' ?></button>
    </form>
  </div>
<?php endif ?>

<div class="table-wrap">
  <table class="table">
    <thead>
      <tr><th>Página</th><th class="hide-sm">Última alteração</th><th class="col-actions"><span class="sr-only">Ações</span></th></tr>
    </thead>
    <tbody>
      <?php foreach ($pages as ['item' => $item, 'template' => $t]): ?>
        <tr>
          <td class="col-title">
            <?php // Modelos repetidos (marcas, campanhas): o nome é o título de cada página. ?>
            <a href="<?= e(url('/admin/content/' . $item['id'])) ?>"><?= e($t['repeatable'] ? $t['label'] . ': ' . $item['title'] : $t['label']) ?></a>
            <?php if (!$t['shared'] && !Posts::isPublic($item)): ?> <?= Admin::badge($item) ?><?php endif ?>
            <div class="row-meta"><?= $t['shared'] ? 'Aparece em todas as páginas' : e(post_path($item)) ?></div>
          </td>
          <td class="hide-sm muted nowrap"><?= e(format_date($item['updated_at'], true)) ?></td>
          <td class="col-actions">
            <a href="<?= e(url('/admin/content/' . $item['id'])) ?>">Editar</a>
            <?php if (!$t['shared']): ?>
              <a href="<?= e(Posts::isPublic($item) ? post_url($item) : url('/admin/preview/' . $item['id'])) ?>" target="_blank" rel="noopener">Ver</a>
            <?php endif ?>
          </td>
        </tr>
      <?php endforeach ?>
      <?php foreach ($missing as $name => $t): ?>
        <tr>
          <td class="col-title">
            <span class="muted"><?= e($t['label']) ?></span>
            <div class="row-meta"><?= $t['shared'] ? 'Aparece em todas as páginas' : 'Página ainda não criada' ?></div>
          </td>
          <td class="hide-sm"></td>
          <td class="col-actions">
            <form method="post" action="<?= e(url('/admin/content/new/' . $name)) ?>"><?= csrf_field() ?><button class="link">Criar</button></form>
          </td>
        </tr>
      <?php endforeach ?>
      <?php if (!$pages && !$missing): ?>
        <tr><td colspan="3" class="muted">Nenhuma página ainda. Use o botão acima para criar a primeira.</td></tr>
      <?php endif ?>
    </tbody>
  </table>
</div>
<p class="hint">Endereço, publicação e SEO avançado (título para o Google, imagem para redes sociais) ficam também em <a href="<?= e(url('/admin/posts?type=page')) ?>">Páginas</a>. Para tirar uma página do site, mande-a para a lixeira por lá.</p>
