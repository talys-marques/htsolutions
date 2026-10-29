<?php
/**
 * /admin/site/{id}: formulário de conteúdo de uma página do site, montado a partir do modelo.
 * public/assets/ht/painel.js junta os campos num JSON (campo "dados") ao salvar.
 * @var array $post  @var array $modelo  @var array $campos  (já limpos)  @var array $versoes  (Posts::revisions)
 */
$geral = !empty($modelo['geral']);
?>
<div class="page-head">
  <h1><?= e($modelo['titulo']) ?></h1>
  <div class="row">
    <a class="btn btn-ghost" href="<?= e(url('/admin/site')) ?>">← Páginas do site</a>
    <?php if (!$geral): ?><a class="btn" href="<?= e(post_url($post)) ?>" target="_blank" rel="noopener">Ver no site</a><?php endif ?>
  </div>
</div>

<?php if (!empty($modelo['descricao'])): ?><p class="muted sh-intro"><?= e($modelo['descricao']) ?></p><?php endif ?>

<form method="post" class="sh-form" data-sh-form>
  <?= csrf_field() ?>
  <input type="hidden" name="dados" value="">

  <?php if (!$geral): ?>
    <fieldset class="sh-grupo sh-pagina">
      <legend>Página</legend>
      <label class="field">Título da página
        <input name="title" value="<?= e($post['title']) ?>" maxlength="150" required>
        <span class="hint">Aparece na aba do navegador e no Google.</span>
      </label>
      <label class="field">Descrição para o Google
        <textarea name="seo_description" rows="2" maxlength="300"><?= e($post['seo_description']) ?></textarea>
        <span class="hint">Uma ou duas frases sobre a página (até uns 160 caracteres).</span>
      </label>
    </fieldset>
  <?php endif ?>

  <div class="sh-campos">
    <?php foreach ($modelo['campos'] as $chave => $def): ?>
      <?= SiteHT::campo($chave, $def, $campos[$chave] ?? null) ?>
    <?php endforeach ?>
  </div>

  <div class="sh-salvar">
    <button class="btn btn-primary" data-busy="Salvando…">Salvar e publicar</button>
    <span class="muted">Salvo pela última vez em <?= e(format_date($post['updated_at'], true)) ?></span>
  </div>
</form>

<?php if ($versoes): ?>
  <details class="sh-grupo sh-versoes">
    <summary><strong>Versões anteriores (<?= count($versoes) ?>)</strong> <span class="muted">· cada vez que alguém salva, a versão que estava no ar fica guardada aqui</span></summary>
    <ul>
      <?php foreach ($versoes as $i => $v): ?>
        <li>
          <span><?= e(format_date($v['date'], true)) ?></span>
          <form method="post" action="<?= e(url('/admin/site/' . $post['id'] . '/versao')) ?>" data-confirm="Voltar a página para esta versão? A versão que está no ar continua na lista.">
            <?= csrf_field() ?><input type="hidden" name="versao" value="<?= e($v['name']) ?>">
            <button class="link">Restaurar</button>
          </form>
        </li>
      <?php endforeach ?>
    </ul>
  </details>
<?php endif ?>

<script src="<?= e(asset('ht/painel.js')) ?>" defer></script>
<style>
  .sh-intro { max-width: 46rem; margin: -.25rem 0 1.25rem; }
  .sh-form { display: grid; gap: 1.25rem; max-width: 56rem; }
  .sh-campos { display: grid; gap: 1.25rem; }
  .sh-grupo { display: grid; gap: .9rem; margin: 0; padding: 1.1rem 1.25rem 1.25rem; border: 1px solid var(--line, #e3e6ea); border-radius: 10px; background: var(--surface, #fff); }
  .sh-grupo > legend { padding: 0 .4rem; font-weight: 700; }
  /* No celular: sem isso, os fieldsets e a barra do editor alargam a página */
  .sh-form :is(div, details, summary, fieldset, label) { min-width: 0; min-inline-size: 0; }
  .sh-form .editor-toolbar { flex-wrap: wrap; }
  .sh-grupo .sh-grupo { background: transparent; }
  .sh-rotulo { display: block; font-weight: 600; }
  .sh-campo .hint, .sh-lista-topo .hint, .sh-grupo > .hint { display: block; margin: .1rem 0 .35rem; font-weight: 400; }
  .sh-campo input, .sh-campo textarea, .sh-campo select { width: 100%; }
  .sh-imagem .image-preview img { max-height: 140px; width: auto; border-radius: 6px; }
  .sh-lista { display: grid; gap: .6rem; }
  .sh-itens { display: grid; gap: .5rem; }
  .sh-item { border: 1px solid var(--line, #e3e6ea); border-radius: 8px; background: var(--bg, #fff); }
  .sh-item > summary { display: flex; align-items: center; gap: .75rem; padding: .55rem .75rem; cursor: pointer; list-style: none; }
  .sh-item > summary::-webkit-details-marker { display: none; }
  .sh-item > summary::before { content: '▸'; color: var(--muted, #667); transition: transform .15s; }
  .sh-item[open] > summary::before { transform: rotate(90deg); }
  .sh-item-nome { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 600; }
  .sh-item-acoes { display: flex; gap: .25rem; }
  .sh-item-campos { display: grid; gap: .9rem; padding: .25rem .9rem 1rem; }
  .sh-lista > [data-sh-adicionar] { justify-self: start; }
  .sh-versoes { max-width: 56rem; margin-top: 1.5rem; }
  .sh-versoes summary { cursor: pointer; }
  .sh-versoes ul { margin: .75rem 0 0; padding: 0; list-style: none; display: grid; gap: .4rem; }
  .sh-versoes li { display: flex; gap: 1rem; align-items: center; }
  .sh-salvar { position: sticky; bottom: 0; z-index: 5; display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; padding: .85rem 0; background: var(--bg, #fff); border-top: 1px solid var(--line, #e3e6ea); }
</style>
