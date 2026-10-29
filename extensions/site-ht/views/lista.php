<?php
/**
 * /admin/site: páginas do site com formulário de conteúdo.
 * @var array $paginas  [['post' => ..., 'modelo' => ...], ...]
 */
?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
</div>

<p class="muted sh-intro">Textos, imagens, links e menus das páginas do site. A estrutura de cada página é fixa: dá para trocar o conteúdo, acrescentar ou tirar itens das listas, mas não mudar a montagem. As mudanças entram no ar assim que você salva.</p>

<?php if (!$paginas): ?>
  <div class="empty"><p>Nenhuma página do site encontrada. Rode <code>php extensions/site-ht/instalar.php</code>.</p></div>
<?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr><th>Página</th><th class="hide-sm">Última alteração</th><th class="col-actions"><span class="sr-only">Ações</span></th></tr>
      </thead>
      <tbody>
        <?php foreach ($paginas as ['post' => $post, 'modelo' => $modelo]): ?>
          <tr>
            <td class="col-title">
              <a href="<?= e(url('/admin/site/' . $post['id'])) ?>"><?= e($modelo['titulo']) ?></a>
              <div class="row-meta"><?= !empty($modelo['geral']) ? 'Aparece em todas as páginas' : e(post_path($post)) ?></div>
            </td>
            <td class="hide-sm muted nowrap"><?= e(format_date($post['updated_at'], true)) ?></td>
            <td class="col-actions">
              <a href="<?= e(url('/admin/site/' . $post['id'])) ?>">Editar</a>
              <?php if (empty($modelo['geral'])): ?><a href="<?= e(post_url($post)) ?>" target="_blank" rel="noopener">Ver</a><?php endif ?>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
<?php endif ?>

<style>
  .sh-intro { max-width: 46rem; margin: -.25rem 0 1.25rem; }
</style>
