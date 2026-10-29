<?php /** @var string|null $error  @var string $username */ ?>
<h1>Entrar no painel</h1>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif ?>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label class="field">Usuário
    <input name="username" value="<?= e($username) ?>" autocomplete="username" autocapitalize="none" required autofocus>
  </label>
  <label class="field">Senha
    <input type="password" name="password" autocomplete="current-password" required>
  </label>
  <button class="btn btn-primary btn-block">Entrar</button>
</form>
<p class="hint">Esqueceu a senha? No servidor, rode <code>php tools/reset-password.php seu-usuario</code>.</p>
