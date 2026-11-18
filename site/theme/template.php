<?php
/**
 * Página com modelo (Painel → Conteúdo do site): mostra templates/{modelo}.php do tema ativo
 * (ou o do tema padrão). O template recebe as mesmas variáveis de page.php e mais $fields,
 * os campos da página já completos (fields/{modelo}.php). Guia: themes/README.md.
 * @var array $post  @var array $fields  @var string $template  nome do modelo  @var bool $preview  @var bool $is_home
 */
?>
<?php if (!empty($is_home)) slot('home-top', get_defined_vars()) ?>
<?php if ($preview): ?>
  <div class="preview-bar">Pré-visualização · esta página ainda não está publicada</div>
<?php endif ?>
<?= render_file(Themes::file('templates/' . $template), get_defined_vars()) ?>
<?php if (!empty($is_home)) slot('home-bottom', get_defined_vars()) ?>
