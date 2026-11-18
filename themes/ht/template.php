<?php
/**
 * Página do site com modelo (Conteúdo do site), igual à de theme/template.php, mas dentro de
 * <div class="pg pg-{modelo}">: o paginas.css de cada página depende dessa classe.
 * @var array $post  @var array $fields  @var string $template  nome do modelo  @var bool $preview  @var bool $is_home
 */
?>
<?php if (!empty($is_home)) slot('home-top', get_defined_vars()) ?>
<?php if ($preview): ?>
  <div class="preview-bar">Pré-visualização · esta página ainda não está publicada</div>
<?php endif ?>
<div class="pg pg-<?= e($template) ?>">
<?= render_file(Themes::file('templates/' . $template), get_defined_vars()) ?>
</div>
<?php if (!empty($is_home)) slot('home-bottom', get_defined_vars()) ?>
