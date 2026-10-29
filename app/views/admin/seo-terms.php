<?php
/**
 * Painel → SEO → Categorias e tags: lista com o que cada uma já tem de SEO.
 * @var array $lists  ['category' => [termo + seo], 'tag' => [...]]  @var int $missing
 */
$table = function (string $kind, array $terms) {
    ob_start(); ?>
    <div class="table-wrap">
      <table class="table compact">
        <thead><tr><th>Nome</th><th>Posts</th><th class="hide-sm">Texto da página</th><th class="hide-sm">Título e descrição</th><th>Google</th><th class="col-actions"></th></tr></thead>
        <tbody>
          <?php foreach ($terms as $term): ?>
            <?php $edit = url("/admin/seo/terms/$kind/" . rawurlencode($term['slug'])); ?>
            <tr>
              <td class="col-title"><a href="<?= e($edit) ?>"><?= e($term['name']) ?></a><div class="row-meta">/<?= e($kind) ?>/<?= e($term['slug']) ?>/</div></td>
              <td class="muted"><?= (int) $term['count'] ?></td>
              <td class="hide-sm muted"><?= $term['seo']['description'] !== '' ? 'Sim' : '—' ?></td>
              <td class="hide-sm muted"><?= $term['seo']['seo_title'] !== '' || $term['seo']['seo_description'] !== '' ? 'Próprios' : 'Automáticos' ?></td>
              <td><?= Seo::indexable($kind, $term['slug']) ? '<span class="badge badge-published">Aparece</span>' : '<span class="badge badge-draft">Fora</span>' ?></td>
              <td class="col-actions"><a href="<?= e($edit) ?>">Editar</a><a href="<?= e(term_url($kind, $term['name'])) ?>" target="_blank" rel="noopener">Ver</a></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php return ob_get_clean();
};
?>
<div class="page-head"><h1>SEO</h1></div>
<?php if (Auth::isAdmin()): // as outras abas são só do admin ?>
  <?= render_file(__DIR__ . '/_seo-tabs.php', ['tab' => 'terms', 'missing' => $missing]) ?>
<?php endif ?>
<p class="muted">As páginas de categoria são portas de entrada do Google. Um texto de apresentação e uma descrição própria ajudam
  essas páginas a aparecer nas buscas pelo assunto. Sem eles, o título é o nome da categoria e a descrição é montada com os títulos dos posts.</p>

<div class="panel sections">
  <section class="section">
    <header class="section-head">
      <h2>Categorias</h2>
      <p><?= count($lists['category'] ?? []) ?> em uso.</p>
    </header>
    <?= $lists['category'] ?? [] ? $table('category', $lists['category']) : '<p class="muted">Nenhuma categoria em uso.</p>' ?>
  </section>

  <section class="section">
    <header class="section-head">
      <h2>Tags</h2>
      <p><?= count($lists['tag'] ?? []) ?> em uso.<?= setting('seo_index_tags') ? '' : ' Todas estão fora do Google' . (Auth::isAdmin() ? ' (aba Geral → Indexação).' : '.') ?></p>
    </header>
    <?php if ($lists['tag'] ?? []): ?>
      <details>
        <summary class="link">Mostrar as tags</summary>
        <?= $table('tag', $lists['tag']) ?>
      </details>
    <?php else: ?>
      <p class="muted">Nenhuma tag em uso.</p>
    <?php endif ?>
  </section>
</div>
