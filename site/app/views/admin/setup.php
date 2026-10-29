<?php /** @var string|null $error  @var array $old */ ?>
<h1>Bem-vindo</h1>
<p class="muted">Crie a conta do administrador. Ela é única e controla tudo: posts, autores, configurações e backup.</p>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif ?>
<form method="post" class="stack">
  <?= csrf_field() ?>
  <label class="field">Nome do blog
    <input name="site_title" value="<?= e($old['site_title']) ?>" placeholder="Meu Blog" autofocus>
  </label>
  <label class="field">Seu nome (aparece nos posts)
    <input name="name" value="<?= e($old['name']) ?>" required autocomplete="name">
  </label>
  <label class="field">Usuário para entrar
    <input name="username" value="<?= e($old['username']) ?>" required pattern="[a-z0-9][a-z0-9._\-]{2,31}" autocapitalize="none" autocomplete="username">
    <span class="hint">Letras minúsculas, números, ponto ou hífen.</span>
  </label>
  <label class="field">E-mail (opcional)
    <input type="email" name="email" value="<?= e($old['email']) ?>" autocomplete="email">
  </label>
  <label class="field">Senha
    <input type="password" name="password" required minlength="8" autocomplete="new-password">
    <span class="hint">Mínimo de 8 caracteres.</span>
  </label>
  <label class="field">Repita a senha
    <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
  </label>
  <button class="btn btn-primary btn-block">Criar conta e começar</button>
</form>
