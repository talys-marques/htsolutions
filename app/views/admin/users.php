<?php /** @var array $users  @var array $counts */ ?>
<div class="page-head">
  <h1>Usuários</h1>
  <a class="btn btn-primary" href="<?= e(url('/admin/users/new')) ?>">Novo autor</a>
</div>
<p class="muted">O administrador é único e controla tudo. Autores cuidam de todo o conteúdo (posts, páginas, mídia e SEO de categorias e tags), mas não acessam usuários, configurações, SEO geral, redirecionamentos nem backup.</p>

<div class="table-wrap">
  <table class="table">
    <thead><tr><th>Nome</th><th>Usuário</th><th>Papel</th><th class="hide-sm">Posts</th><th class="hide-sm">Último acesso</th><th class="col-actions"></th></tr></thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td class="col-title"><a href="<?= e(url('/admin/users/' . $u['username'])) ?>"><?= e($u['name']) ?></a><?php if ($u['email']): ?><div class="row-meta"><?= e($u['email']) ?></div><?php endif ?></td>
          <td class="muted"><?= e($u['username']) ?></td>
          <td><span class="badge badge-<?= $u['role'] === 'admin' ? 'published' : 'draft' ?>"><?= e(Auth::ROLES[$u['role']] ?? $u['role']) ?></span></td>
          <td class="hide-sm muted"><?= (int) ($counts[$u['username']] ?? 0) ?></td>
          <td class="hide-sm muted"><?= !empty($u['last_login']) ? e(format_date($u['last_login'], true)) : '—' ?></td>
          <td class="col-actions">
            <a href="<?= e(url('/admin/users/' . $u['username'])) ?>">Editar</a>
            <?php if ($u['role'] !== 'admin'): ?>
              <form method="post" action="<?= e(url('/admin/users/' . $u['username'] . '/delete')) ?>" data-confirm="Excluir <?= e($u['name']) ?>? Os posts dessa pessoa passam para você.">
                <?= csrf_field() ?><button class="link danger">Excluir</button>
              </form>
            <?php endif ?>
          </td>
        </tr>
      <?php endforeach ?>
    </tbody>
  </table>
</div>
