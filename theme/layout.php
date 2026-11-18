<?php
/**
 * Estrutura de todas as páginas públicas (cabeçalho, SEO, rodapé).
 *
 * Variáveis: $content (HTML da página), $meta (SEO, campos no comentário de
 * app/Seo.php), $site (Configurações). Os templates de cada página também
 * recebem $site e as variáveis que Site.php passa para eles.
 *
 * Os slot() marcam onde um tema instalado pode acrescentar blocos (themes/README.md).
 */
$user = Auth::user();
$themeCss = theme_asset('style.css');
$themeJs = theme_asset('script.js');
?><!doctype html>
<html lang="<?= e($site['language']) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php // Título, descrição, robots, canonical, Open Graph e dados estruturados (já escapados em Seo::head). ?>
<?= Seo::head($meta) ?>
<link rel="icon" href="<?= e(favicon_url()) ?>">
<?php if ($site['favicon']): ?><link rel="apple-touch-icon" href="<?= e($site['favicon']) ?>">
<?php endif ?>
<?php if ($site['browser_accent']): ?><meta name="theme-color" content="<?= e($site['accent_color']) ?>">
<?php endif ?>
<link rel="stylesheet" href="<?= e(asset('site.css')) ?>">
<?php // CSS do tema ativo: antes do <style> abaixo, para a cor, as fontes e o estilo de Configurações continuarem valendo. ?>
<?php if ($themeCss): ?><link rel="stylesheet" href="<?= e($themeCss) ?>">
<?php endif ?>
<?php // Fontes e tokens vêm das listas fixas de Settings, por isso vão sem e(), que estragaria as aspas do CSS. ?>
<style>:root { --accent: <?= e($site['accent_color']) ?>; --on-accent: <?= e(text_color_on($site['accent_color'])) ?>; <?= Settings::css(Settings::themeVars()) ?> }</style>
<script src="<?= e(asset('site.js')) ?>"></script>
<?php if ($themeJs): ?><script src="<?= e($themeJs) ?>" defer></script>
<?php endif ?>
<?php slot('head', get_defined_vars()) ?>
<?= $site['head_code'] ?>

</head>
<?php // Classes extras do tema ativo (functions.php → 'body_class'), para o CSS dele depender das opções. ?>
<body class="<?= e(trim($body_class . ' ' . Themes::bodyClass(get_defined_vars()))) ?>">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<?php slot('header-before', get_defined_vars()) ?>

<header class="site-header">
  <div class="wrap header-inner">
    <a class="brand" href="<?= e(url('/')) ?>">
      <?php if ($site['logo'] && !in_array($site['title_place'], ['header', 'both'], true)): ?>
        <img src="<?= e($site['logo']) ?>" alt="<?= e($site['title']) ?>" height="36">
      <?php else: ?>
        <?php if ($site['logo']): ?><img src="<?= e($site['logo']) ?>" alt="" height="36"><?php endif ?>
        <span><?= e($site['title']) ?></span>
      <?php endif ?>
    </a>
    <nav class="site-nav" aria-label="Menu principal">
      <?php
      // Item marcado "Nova aba" no painel: abre em outra aba e avisa quem usa leitor de tela.
      $tab = fn(array $link) => $link['new_tab'] ? ' target="_blank" rel="noopener"' : '';
      $text = fn(array $link) => e($link['label']) . ($link['new_tab'] ? '<span class="visually-hidden"> (abre em nova aba)</span>' : '');
      ?>
      <?php foreach (Menu::links() as $item): ?>
        <?php $current = $item['current'] ? ' aria-current="page"' : ''; ?>
        <?php if (!$item['children']): ?>
          <a href="<?= e($item['url']) ?>"<?= $current . $tab($item) ?>><?= $text($item) ?></a>
        <?php else: ?>
          <?php // Subitens: abrem ao passar o mouse; no toque e no teclado, site.js abre e fecha. ?>
          <div class="nav-sub">
            <?php if ($item['url']): ?>
              <a class="nav-sub-toggle<?= $item['active'] ? ' is-current' : '' ?>" href="<?= e($item['url']) ?>" aria-expanded="false"<?= $current . $tab($item) ?>><?= $text($item) ?></a>
            <?php else: ?>
              <button type="button" class="nav-sub-toggle<?= $item['active'] ? ' is-current' : '' ?>" aria-expanded="false"><?= e($item['label']) ?></button>
            <?php endif ?>
            <?php $count = count($item['children']); ?>
            <div class="nav-sub-list<?= $count > 8 ? ' cols-' . min(3, (int) ceil($count / 8)) : '' ?>">
              <?php // No toque, o item de cima só abre a lista: o link dele vai como primeiro da lista. ?>
              <?php if ($item['url']): ?><a class="nav-sub-self" href="<?= e($item['url']) ?>"<?= $current . $tab($item) ?>><?= $text($item) ?></a><?php endif ?>
              <?php foreach ($item['children'] as $child): ?>
                <a href="<?= e($child['url']) ?>"<?= ($child['current'] ? ' aria-current="page"' : '') . $tab($child) ?>><?= $text($child) ?></a>
              <?php endforeach ?>
            </div>
          </div>
        <?php endif ?>
      <?php endforeach ?>
    </nav>
    <form class="search-form" action="<?= e(url('/')) ?>" method="get" role="search" data-suggest="<?= e(url('/search.json')) ?>">
      <input type="search" name="s" placeholder="Buscar…" aria-label="Buscar no blog" value="<?= e(get_param('s')) ?>">
    </form>
    <button type="button" class="theme-toggle" title="Alternar modo claro e escuro" aria-label="Alternar modo claro e escuro">
      <svg class="icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
      <svg class="icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
    </button>
    <?php slot('header-actions', get_defined_vars()) ?>
  </div>
</header>
<?php slot('header-after', get_defined_vars()) ?>

<main id="conteudo" class="site-main">
<?= $content ?>
</main>

<?php slot('footer-before', get_defined_vars()) ?>
<footer class="site-footer">
  <div class="wrap footer-inner">
    <?php if ($social = Social::links()): ?>
      <nav class="social-links" aria-label="Redes sociais">
        <?php foreach ($social as $link): ?>
          <?php // O ícone é SVG da lista fixa de Social::networks(), por isso vai sem e(). ?>
          <a href="<?= e($link['url']) ?>" title="<?= e($link['label']) ?>" aria-label="<?= e($link['label']) ?>" rel="me noopener"<?= $link['external'] ? ' target="_blank"' : '' ?>><?= $link['icon'] ?></a>
        <?php endforeach ?>
      </nav>
    <?php endif ?>
    <?php if ($site['footer_text']): ?><div class="footer-text"><?= $site['footer_text'] ?></div><?php endif ?>
    <p class="footer-meta">© <?= date('Y') ?> <?= e($site['title']) ?></p>
  </div>
</footer>

<?php if ($user): ?>
  <div class="admin-bar">
    <?php // Painel → Temas → Pré-visualizar: só quem está pré-visualizando vê este tema. ?>
    <?php if ($previewing = Themes::previewing()): ?>
      <span class="admin-bar-note">Prévia: <?= e($previewing['name']) ?></span>
      <a href="<?= e(url('/admin/themes/preview')) ?>">Sair da prévia</a>
    <?php endif ?>
    <a href="<?= e(url('/admin')) ?>">Painel</a>
    <?php if (isset($post['id']) && Auth::canEdit($post)): ?>
      <?php // Página com modelo: o conteúdo é editado no formulário de Conteúdo do site. ?>
      <a href="<?= e(url((isset($template) ? '/admin/content/' : '/admin/posts/') . $post['id'])) ?>">Editar</a>
    <?php endif ?>
    <?php if (!empty($edit_url)): ?><a href="<?= e($edit_url) ?>">Editar SEO</a><?php endif ?>
  </div>
<?php endif ?>
<?php slot('body-end', get_defined_vars()) ?>
<?= $site['body_code'] ?>

</body>
</html>
