<?php /** @var array $values  @var bool $editing  @var string|null $error */ ?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
  <a class="btn btn-ghost" href="<?= e(url('/admin/users')) ?>">← Voltar</a>
</div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif ?>

<form method="post" class="panel stack narrow-form">
  <?= csrf_field() ?>
  <label class="field">Nome (aparece nos posts)
    <input name="name" value="<?= e($values['name']) ?>" required>
  </label>
  <label class="field">Usuário para entrar
    <input name="username" value="<?= e($values['username']) ?>" required pattern="[a-z0-9][a-z0-9._\-]{2,31}" autocapitalize="none" autocomplete="off">
    <span class="hint">Letras minúsculas, números, ponto ou hífen.<?= $editing ? ' Ao trocar, a pessoa passa a entrar com o login novo, os posts vão junto e o endereço antigo do autor redireciona para o novo.' : '' ?></span>
  </label>
  <label class="field">E-mail (opcional)
    <input type="email" name="email" value="<?= e($values['email']) ?>">
  </label>
  <p class="field">Papel: <strong><?= e(Auth::ROLES[$values['role']] ?? 'Autor') ?></strong></p>
  <label class="field"><?= $editing ? 'Nova senha (deixe vazio para manter)' : 'Senha' ?>
    <input type="password" name="password" minlength="8" autocomplete="new-password"<?= $editing ? '' : ' required' ?>>
  </label>
  <label class="field">Repita a senha
    <input type="password" name="password_confirm" minlength="8" autocomplete="new-password"<?= $editing ? '' : ' required' ?>>
  </label>
  <div><button class="btn btn-primary"><?= $editing ? 'Salvar' : 'Criar autor' ?></button></div>
</form>
