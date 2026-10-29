<?php
/**
 * Layout simples (login, configuração inicial, mensagens de erro).
 * @var string $content  @var string $title
 */
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Painel') ?> · <?= e(setting('title')) ?></title>
<link rel="icon" href="<?= e(favicon_url()) ?>">
<link rel="stylesheet" href="<?= e(asset('admin.css')) ?>">
</head>
<body class="auth-page">
<aside class="auth-aside">
  <p class="auth-eyebrow">Painel de administração</p>
  <div>
    <p class="auth-title"><?= e(setting('title')) ?></p>
    <?php if (setting('tagline')): ?><p class="auth-tagline"><?= e(setting('tagline')) ?></p><?php endif ?>
  </div>
  <p class="auth-colophon">Acesso restrito · <?= date('Y') ?></p>
</aside>
<main class="auth-main">
  <div class="auth-box">
    <?php foreach (flash() as [$type, $message]): ?>
      <div class="alert alert-<?= e($type) ?>" role="status"><?= e($message) ?></div>
    <?php endforeach ?>
    <?= $content ?>
  </div>
</main>
<script src="<?= e(asset('admin.js')) ?>"></script>
</body>
</html>
