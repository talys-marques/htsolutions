<?php /** @var array $me  @var string|null $error */ ?>
<div class="page-head"><h1>Minha conta</h1></div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif ?>

<form method="post" class="panel stack narrow-form">
  <?= csrf_field() ?>
  <p class="muted">Usuário: <strong><?= e($me['username']) ?></strong> · <?= e(Auth::ROLES[$me['role']] ?? '') ?></p>
  <label class="field">Nome (aparece nos posts)
    <input name="name" value="<?= e($me['name']) ?>" required>
  </label>
  <label class="field">E-mail (opcional)
    <input type="email" name="email" value="<?= e($me['email'] ?? '') ?>">
  </label>
  <hr>
  <p class="muted">Para trocar a senha, preencha os três campos abaixo. Trocar a senha desconecta seus outros dispositivos.</p>
  <label class="field">Senha atual
    <input type="password" name="current_password" autocomplete="current-password">
  </label>
  <label class="field">Nova senha
    <input type="password" name="password" minlength="8" autocomplete="new-password">
  </label>
  <label class="field">Repita a nova senha
    <input type="password" name="password_confirm" minlength="8" autocomplete="new-password">
  </label>
  <div><button class="btn btn-primary">Salvar</button></div>
</form>
