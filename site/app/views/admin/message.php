<?php /** @var string $title  @var string $message */ ?>
<div class="message-box">
  <h1><?= e($title) ?></h1>
  <p><?= e($message) ?></p>
  <p><a class="btn" href="<?= e(url('/admin')) ?>" data-back>← Voltar</a> <a class="btn btn-ghost" href="<?= e(url('/admin')) ?>">Ir para o painel</a></p>
</div>
