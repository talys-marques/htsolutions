<?php
/**
 * Estrutura de todas as páginas públicas (cabeçalho, SEO, rodapé).
 *
 * Variáveis: $content (HTML da página), $meta (SEO, campos no comentário de
 * app/Seo.php), $site (Configurações). Os templates de cada página também
 * recebem $site e as variáveis que Site.php passa para eles.
 *
 * Tema da HT Solutions: topo e rodapé do site, com menu, contatos e links de Conteúdo do site →
 * "Topo, rodapé e contatos" (ht_geral()). Páginas do site (com modelo) usam paginas.css; as do
 * blog ganham a barra do blog (menu de Configurações → Menu e busca). Sem modo escuro.
 */
require_once __DIR__ . '/site/_partes.php';
$user = Auth::user();
$ht = require __DIR__ . '/ht.php';
$geral = ht_geral();
$modelo = isset($post['modelo']) && $post['type'] === 'page' ? (string) $post['modelo'] : '';
$ehSite = $modelo !== '';
$contato = $geral['contato'] ?? [];
$redes = array_filter($geral['redes'] ?? []);
$rodape = $geral['rodape'] ?? [];
$formulario = $ht['emarsys']['formularios'][$ht['formulario_por_modelo'][$modelo] ?? 'empresa'];

// Item do menu marcado: a página atual (ou uma dentro dele); "Blog" fica marcado em todo o blog
$atual = rtrim(App::$path, '/') . '/';
$marcado = function (string $link) use ($atual, $ehSite): bool {
    $caminho = strtok($link, '#');
    if (!str_starts_with($link, '/') || str_contains($link, '#') || $caminho === '/') {
        return false;
    }
    return str_starts_with($atual, $caminho) || ($caminho === '/blog/' && !$ehSite);
};
$icone = fn(string $paths, int $size = 20) => '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths . '</svg>';
$icones = [
    'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'fechar' => '<path d="M18 6 6 18M6 6l12 12"/>',
    'baixo' => '<path d="m6 9 6 6 6-6"/>',
    'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
    'linkedin' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
    'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><path d="M17.5 6.5h.01"/>',
];
$nomesRedes = ['facebook' => 'Facebook', 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram'];
?><!doctype html>
<html lang="<?= e($site['language']) ?>" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php // Título, descrição, robots, canonical, Open Graph e dados estruturados (já escapados em Seo::head). ?>
<?= Seo::head($meta) ?>
<link rel="icon" href="<?= e(favicon_url()) ?>">
<?php if ($site['favicon']): ?><link rel="apple-touch-icon" href="<?= e($site['favicon']) ?>">
<?php endif ?>
<meta name="theme-color" content="#06152b">
<link rel="stylesheet" href="<?= e(asset('site.css')) ?>">
<?php // Fontes e tokens vêm das listas fixas de Settings, por isso vão sem e(), que estragaria as aspas do CSS. ?>
<style>:root { --accent: <?= e($site['accent_color']) ?>; --on-accent: <?= e(text_color_on($site['accent_color'])) ?>; <?= Settings::css(Settings::themeVars()) ?> }</style>
<?php // Depois do <style> acima: a fonte e as cores da HT valem mesmo com outra escolha no painel. ?>
<link rel="preload" href="<?= e(url('/assets/ht/fonts/plus-jakarta-sans-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('ht/ht.css')) ?>">
<?php if ($ehSite): ?><link rel="stylesheet" href="<?= e(asset('ht/paginas.css')) ?>">
<?php endif ?>
<script src="<?= e(asset('site.js')) ?>"></script>
<script src="<?= e(asset('ht/ht.js')) ?>" defer></script>
<?= $site['head_code'] ?>

</head>
<body class="<?= e($body_class) ?>">
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>

<header class="ht-topo">
  <div class="ht-wrap ht-topo-h">
    <a class="ht-logo" href="<?= e(url('/')) ?>" aria-label="HT Solutions – página inicial">
      <img src="<?= e(url('/assets/ht/logo-branco.png')) ?>" alt="HT Solutions" width="150" height="36">
    </a>
    <button type="button" class="ht-menu-botao" aria-expanded="false" aria-controls="ht-menu">
      <span class="ht-abrir"><?= $icone($icones['menu'], 24) ?></span>
      <span class="ht-fechar"><?= $icone($icones['fechar'], 24) ?></span>
      <span class="visually-hidden">Menu</span>
    </button>
    <nav id="ht-menu" class="ht-menu" aria-label="Principal">
      <ul>
        <?php foreach ($geral['menu'] ?? [] as $item): ?>
          <?php $sub = array_filter($item['submenu'] ?? [], fn($s) => $s['titulo'] !== '' && $s['link'] !== ''); ?>
          <li<?= $sub ? ' class="ht-tem-sub"' : '' ?>>
            <a href="<?= e(ht_link($item['link'])) ?>"<?= $marcado($item['link']) ? ' class="ht-atual" aria-current="page"' : '' ?><?= ht_alvo($item['link']) ?>><?= e($item['titulo']) ?></a>
            <?php if ($sub): ?>
              <button type="button" class="ht-sub-botao" aria-expanded="false" aria-label="Abrir <?= e($item['titulo']) ?>"><?= $icone($icones['baixo'], 16) ?></button>
              <ul class="ht-submenu">
                <?php foreach ($sub as $s): ?>
                  <li><a href="<?= e(ht_link($s['link'])) ?>"<?= ht_alvo($s['link']) ?>><?= e($s['titulo']) ?></a></li>
                <?php endforeach ?>
              </ul>
            <?php endif ?>
          </li>
        <?php endforeach ?>
      </ul>
      <a href="#contato" class="ht-botao ht-botao-claro">Fale conosco</a>
    </nav>
  </div>
</header>

<?php if (!$ehSite): ?>
<div class="blog-barra">
  <div class="wrap header-inner">
    <a class="brand" href="<?= e(post_url(Posts::blogPage() ?? ['id' => 0, 'slug' => 'blog'])) ?>">Blog <span>HT Solutions</span></a>
    <nav class="site-nav" aria-label="Menu do blog">
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
      <input type="search" name="s" placeholder="Buscar no blog…" aria-label="Buscar no blog" value="<?= e(get_param('s')) ?>">
    </form>
  </div>
</div>
<?php endif ?>

<main id="conteudo" class="site-main">
<?= $content ?>
</main>

<footer class="ht-rodape">
  <section class="ht-contato" id="contato" aria-labelledby="contato-titulo">
    <div class="ht-wrap ht-contato-h">
      <div>
        <p class="ht-sobretitulo">Contato</p>
        <h2 id="contato-titulo"><?= e($contato['titulo'] ?? 'Fale conosco') ?></h2>
        <?php if (!empty($contato['texto'])): ?><p class="ht-contato-intro"><?= e($contato['texto']) ?></p><?php endif ?>
        <ul class="ht-contato-lista">
          <?php if (!empty($contato['telefone'])): ?><li><?= ht_icone('telefone') ?> <a href="<?= e(ht_tel($contato['telefone'])) ?>"><?= e($contato['telefone']) ?></a></li><?php endif ?>
          <?php if (!empty($contato['email'])): ?><li><?= ht_icone('email') ?> <a href="mailto:<?= e($contato['email']) ?>"><?= e($contato['email']) ?></a></li><?php endif ?>
          <?php if (!empty($contato['horario'])): ?><li><?= ht_icone('relogio') ?> <span><?= e($contato['horario']) ?></span></li><?php endif ?>
          <?php if (!empty($contato['endereco'])): ?><li><?= ht_icone('local') ?> <span><?= e($contato['endereco']) ?></span></li><?php endif ?>
        </ul>
      </div>
      <div class="ht-formulario">
        <h3>Entre em contato</h3>
        <?php // Vai para a Emarsys num iframe escondido: o visitante continua na página (ht.js mostra a confirmação). ?>
        <form action="<?= e($ht['emarsys']['acao']) ?>" method="get" target="emarsys-contato" data-ht-formulario>
          <input type="hidden" name="CID" value="<?= e($ht['emarsys']['cid']) ?>">
          <input type="hidden" name="f" value="<?= e($formulario['f']) ?>">
          <input type="hidden" name="p" value="2">
          <input type="hidden" name="a" value="r">
          <div class="ht-campos">
            <div class="ht-campo"><label for="ct-nome">Nome</label><input id="ct-nome" name="inp_1" maxlength="60" autocomplete="given-name" required></div>
            <div class="ht-campo"><label for="ct-sobrenome">Sobrenome</label><input id="ct-sobrenome" name="inp_2" maxlength="60" autocomplete="family-name" required></div>
            <div class="ht-campo"><label for="ct-email">E-mail</label><input id="ct-email" name="inp_3" type="email" maxlength="255" autocomplete="email" required></div>
            <div class="ht-campo"><label for="ct-whatsapp">WhatsApp (com DDD)</label><input id="ct-whatsapp" name="inp_37" type="tel" maxlength="60" autocomplete="tel" required></div>
            <div class="ht-campo ht-largo"><label for="ct-extra"><?= e($formulario['campo'][1]) ?></label><input id="ct-extra" name="<?= e($formulario['campo'][0]) ?>" maxlength="255" autocomplete="organization" required></div>
            <div class="ht-campo ht-largo"><label for="ct-mensagem">Mensagem</label><textarea id="ct-mensagem" name="<?= e($formulario['mensagem']) ?>" maxlength="255" rows="4" required></textarea></div>
          </div>
          <label class="ht-opcao"><input type="checkbox" name="optin" value="y"> Desejo receber ofertas especiais de produtos ou serviços.</label>
          <button type="submit" class="ht-botao">Enviar <?= ht_icone('seta', 18) ?></button>
          <p class="ht-ok" role="status" hidden><?= ht_icone('ok') ?> Mensagem enviada. Em breve entraremos em contato.</p>
          <iframe name="emarsys-contato" title="Envio do formulário" hidden></iframe>
        </form>
      </div>
    </div>
  </section>

  <div class="ht-wrap ht-links">
    <div class="ht-marca">
      <a href="<?= e(url('/')) ?>" aria-label="HT Solutions – página inicial">
        <img src="<?= e(url('/assets/ht/logo-branco.png')) ?>" alt="HT Solutions" width="140" height="34" loading="lazy">
      </a>
      <?php if (!empty($rodape['texto'])): ?><p><?= e($rodape['texto']) ?></p><?php endif ?>
      <div class="ht-herval">
        <img src="<?= e(url('/assets/ht/logo-herval.png')) ?>" alt="Grupo Herval" width="92" height="44" loading="lazy">
        <span>Uma empresa do Grupo Herval</span>
      </div>
    </div>
    <?php foreach (['institucional' => 'Institucional', 'solucoes' => 'Soluções'] as $chave => $titulo): ?>
      <nav aria-label="<?= e($titulo) ?>">
        <h2 class="ht-rodape-titulo"><?= e($titulo) ?></h2>
        <ul>
          <?php foreach ($rodape[$chave] ?? [] as $l): ?><li><a href="<?= e(ht_link($l['link'])) ?>"<?= ht_alvo($l['link']) ?>><?= e($l['titulo']) ?></a></li><?php endforeach ?>
        </ul>
      </nav>
    <?php endforeach ?>
    <div>
      <h2 class="ht-rodape-titulo">Siga a HT</h2>
      <ul class="ht-redes">
        <?php foreach ($redes as $rede => $href): ?>
          <li><a href="<?= e($href) ?>" target="_blank" rel="noopener" aria-label="HT Solutions no <?= e($nomesRedes[$rede] ?? $rede) ?>"><?= $icone($icones[$rede] ?? '', 18) ?></a></li>
        <?php endforeach ?>
      </ul>
      <ul>
        <?php foreach ($rodape['links'] ?? [] as $l): ?><li><a href="<?= e(ht_link($l['link'])) ?>"<?= ht_alvo($l['link']) ?>><?= e($l['titulo']) ?></a></li><?php endforeach ?>
      </ul>
    </div>
  </div>

  <div class="ht-base">
    <div class="ht-wrap">
      <span>© <?= date('Y') ?> HT Solutions. Todos os direitos reservados.</span>
      <span>Dois Irmãos – RS · Brasil</span>
    </div>
  </div>
</footer>

<?php if ($ehSite): ?><script src="<?= e(asset('ht/paginas.js')) ?>" defer></script>
<?php endif ?>
<?php if ($user): ?>
  <div class="admin-bar">
    <a href="<?= e(url('/admin')) ?>">Painel</a>
    <?php if ($ehSite && isset($post['id'])): ?><a href="<?= e(url('/admin/site/' . $post['id'])) ?>">Editar conteúdo</a>
    <?php elseif (isset($post['id']) && Auth::canEdit($post)): ?><a href="<?= e(url('/admin/posts/' . $post['id'])) ?>">Editar</a><?php endif ?>
    <?php if (!empty($edit_url)): ?><a href="<?= e($edit_url) ?>">Editar SEO</a><?php endif ?>
  </div>
<?php endif ?>
<?= $site['body_code'] ?>

</body>
</html>
