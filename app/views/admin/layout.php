<?php
/**
 * Layout do painel.
 * @var string $content  @var string $title  @var array|null $user  @var string|null $active
 */
$active = $active ?? '';
$isAdmin = ($user['role'] ?? '') === 'admin';
$nav = [
    'Conteúdo' => [
        ['posts', '/admin/posts', 'Posts', true],
        ['pages', '/admin/posts?type=page', 'Páginas', true],
        ['media', '/admin/media', 'Mídia', true],
        // O autor só tem a parte de conteúdo do SEO (o resto é técnico).
        ['seo', '/admin/seo/terms', 'Categorias e tags', !$isAdmin],
    ],
    'Administração' => [
        ['users', '/admin/users', 'Usuários', $isAdmin],
        ['settings', '/admin/settings', 'Configurações', $isAdmin],
        ['seo', '/admin/seo', 'SEO', $isAdmin],
        ['redirects', '/admin/redirects', 'Redirecionamentos', $isAdmin],
    ],
    'Ferramentas' => [],
];
foreach (Extensions::menu() as $item) {
    // 'group' escolhe a seção do menu (padrão: Ferramentas)
    $nav[$item['group'] ?? 'Ferramentas'][] = [$item['key'], $item['url'], $item['label'], $item['role'] === 'author' || $isAdmin];
}
$nav['Ferramentas'][] = ['tools', '/admin/tools', 'Backup e ferramentas', $isAdmin];
$name = (string) ($user['name'] ?? '');
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="base-path" content="<?= e(url('/')) ?>">
<title><?= e($title ?? 'Painel') ?> · <?= e(setting('title')) ?></title>
<link rel="icon" href="<?= e(favicon_url()) ?>">
<link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <a class="brand" href="<?= e(url('/admin')) ?>"><span class="brand-sub">Painel</span><span class="brand-name"><?= e(setting('title')) ?></span></a>
    <nav class="nav" aria-label="Painel">
      <?php foreach ($nav as $group => $items): ?>
        <?php $items = array_filter($items, fn($i) => $i[3]); ?>
        <?php if ($items): ?>
          <div class="nav-group">
            <span class="nav-label"><?= e($group) ?></span>
            <?php foreach ($items as [$key, $href, $label]): ?>
              <a href="<?= e(url($href)) ?>"<?= $active === $key ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach ?>
          </div>
        <?php endif ?>
      <?php endforeach ?>
    </nav>
    <div class="sidebar-foot">
      <a class="account<?= $active === 'account' ? ' active' : '' ?>" href="<?= e(url('/admin/account')) ?>" title="Minha conta">
        <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($name, 0, 1))) ?></span>
        <span class="account-text"><span class="account-name"><?= e($name) ?></span><span class="role"><?= e(Auth::ROLES[$user['role'] ?? 'author'] ?? '') ?></span></span>
      </a>
      <div class="foot-links">
        <a href="<?= e(url('/')) ?>" target="_blank" rel="noopener">Ver o site</a>
        <form method="post" action="<?= e(url('/admin/logout')) ?>"><?= csrf_field() ?><button class="nav-button">Sair</button></form>
      </div>
    </div>
  </aside>
  <main class="main">
    <?php foreach (flash() as [$type, $message]): ?>
      <div class="alert alert-<?= e($type) ?>" role="status"><?= e($message) ?></div>
    <?php endforeach ?>
    <?= $content ?>
  </main>
</div>
<script src="<?= e(asset('admin.js')) ?>"></script>
</body>
</html>
