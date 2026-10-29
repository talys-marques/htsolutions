<?php
/**
 * @var array $s  @var array $menuRows  @var array $fonts  @var array $networks  @var array $layouts  @var array $pages  @var array $titlePlaces
 * @var array $tokens  @var array $tokenChoices  @var array|null $previewItem  @var string|null $error
 */
$host = parse_url(site_url(), PHP_URL_HOST) ?: 'meublog.com.br';
$timezones = ['America/Sao_Paulo', 'America/Manaus', 'America/Cuiaba', 'America/Belem', 'America/Fortaleza', 'America/Recife', 'America/Bahia', 'America/Rio_Branco', 'America/Noronha', 'Europe/Lisbon', 'UTC'];
if (!in_array($s['timezone'], $timezones, true)) {
    $timezones[] = $s['timezone'];
}

// Miniaturas das estruturas da página inicial: [classe, x, y, largura, altura] numa tela de 96×64.
// Classes: i = imagem, t = título, s = texto.
$shapes = [
    'classic' => [['i', 8, 8, 44, 22], ['t', 56, 10, 32, 4], ['s', 56, 17, 28, 2.5], ['s', 56, 22, 22, 2.5],
        ['t', 8, 36, 52, 3.5], ['s', 8, 42, 40, 2.5], ['i', 70, 35, 18, 11],
        ['t', 8, 51, 48, 3.5], ['s', 8, 57, 36, 2.5], ['i', 70, 50, 18, 11]],
    'grid' => [['i', 8, 8, 44, 20], ['t', 56, 10, 32, 4], ['s', 56, 17, 28, 2.5], ['s', 56, 22, 22, 2.5],
        ['i', 8, 34, 24, 14], ['t', 8, 51, 22, 3], ['s', 8, 56, 16, 2.5],
        ['i', 36, 34, 24, 14], ['t', 36, 51, 20, 3], ['s', 36, 56, 16, 2.5],
        ['i', 64, 34, 24, 14], ['t', 64, 51, 22, 3], ['s', 64, 56, 14, 2.5]],
    'magazine' => [['i', 8, 8, 52, 29], ['t', 8, 41, 46, 4], ['s', 8, 48, 36, 2.5],
        ['i', 64, 8, 24, 12], ['t', 64, 22, 22, 3], ['i', 64, 29, 24, 12], ['t', 64, 43, 20, 3],
        ['i', 8, 54, 24, 7], ['i', 36, 54, 24, 7], ['i', 64, 54, 24, 7]],
    'compact' => [['t', 8, 7, 12, 3.5],
        ['s', 8, 16, 9, 2.5], ['t', 22, 15.5, 60, 3.5], ['s', 8, 24, 9, 2.5], ['t', 22, 23.5, 48, 3.5], ['s', 8, 32, 9, 2.5], ['t', 22, 31.5, 54, 3.5],
        ['t', 8, 42, 12, 3.5],
        ['s', 8, 51, 9, 2.5], ['t', 22, 50.5, 44, 3.5], ['s', 8, 59, 9, 2.5], ['t', 22, 58.5, 58, 3.5]],
    'categories' => [['t', 8, 7, 22, 4], ['s', 74, 8, 14, 2.5],
        ['i', 8, 15, 24, 12], ['i', 36, 15, 24, 12], ['i', 64, 15, 24, 12], ['s', 8, 30, 20, 2.5], ['s', 36, 30, 18, 2.5], ['s', 64, 30, 20, 2.5],
        ['t', 8, 37, 18, 4], ['s', 74, 38, 14, 2.5],
        ['i', 8, 44, 24, 12], ['i', 36, 44, 24, 12], ['i', 64, 44, 24, 12], ['s', 8, 59, 20, 2.5], ['s', 36, 59, 18, 2.5], ['s', 64, 59, 20, 2.5]],
];
$thumbs = array_map(fn($rects) => '<svg viewBox="0 0 96 64">'
    . implode('', array_map(fn($r) => vsprintf('<rect class="%s" x="%s" y="%s" width="%s" height="%s" rx="1.2"/>', $r), $rects))
    . '</svg>', $shapes);

// Atalhos do topo: id da <section> => nome. Seção nova entra aqui também.
$sections = ['identidade' => 'Identidade', 'aparencia' => 'Aparência', 'estilo' => 'Estilo', 'pagina-inicial' => 'Página inicial',
    'menu' => 'Menu', 'redes-sociais' => 'Redes sociais', 'leitura' => 'Leitura', 'endereco' => 'Endereço e região', 'rodape' => 'Rodapé e códigos'];
?>
<div class="page-head"><h1>Configurações</h1></div>
<nav class="tabs jump" aria-label="Seções das configurações" data-jump>
  <?php foreach ($sections as $id => $label): ?><a href="#<?= e($id) ?>"><?= e($label) ?></a><?php endforeach ?>
</nav>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif ?>

<form method="post" class="settings">
  <?= csrf_field() ?>

  <div class="panel sections">
    <section class="section" id="identidade" tabindex="-1">
      <header class="section-head">
        <h2>Identidade</h2>
        <p>Nome, apresentação e imagens que representam o blog.</p>
      </header>
      <div class="stack">
        <div class="grid-2">
          <label class="field">Nome do blog
            <input name="title" value="<?= e($s['title']) ?>" required>
          </label>
          <label class="field">Onde o nome aparece
            <select name="title_place">
              <?php foreach ($titlePlaces as $key => $label): ?><option value="<?= e($key) ?>"<?= $key === $s['title_place'] ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
            </select>
            <span class="hint">Sem logo, o cabeçalho sempre mostra o nome.</span>
          </label>
        </div>
        <label class="field">Frase de apresentação
          <input name="tagline" value="<?= e($s['tagline']) ?>" placeholder="Aparece no topo da página inicial">
          <span class="hint">Título e descrição do blog no Google ficam em <a href="<?= e(url('/admin/seo')) ?>">SEO</a>.</span>
        </label>
        <div class="grid-2">
          <div class="field" data-image-field>
            <span>Logo (opcional)</span>
            <div class="image-preview small"></div>
            <input type="hidden" name="logo" value="<?= e($s['logo']) ?>">
            <div class="row"><button type="button" class="btn btn-sm" data-image-pick>Escolher</button><button type="button" class="btn btn-sm btn-ghost" data-image-remove>Remover</button></div>
          </div>
          <div class="field" data-image-field>
            <span>Ícone da aba (favicon)</span>
            <div class="image-preview small"></div>
            <input type="hidden" name="favicon" value="<?= e($s['favicon']) ?>">
            <div class="row"><button type="button" class="btn btn-sm" data-image-pick>Escolher</button><button type="button" class="btn btn-sm btn-ghost" data-image-remove>Remover</button></div>
            <span class="hint">Aparece na aba do navegador, nos favoritos e na tela inicial do celular. Use uma imagem quadrada em PNG, de 512×512 pixels.</span>
          </div>
          <div class="field" data-image-field>
            <span>Imagem padrão para redes sociais</span>
            <div class="image-preview small"></div>
            <input type="hidden" name="default_image" value="<?= e($s['default_image']) ?>">
            <div class="row"><button type="button" class="btn btn-sm" data-image-pick>Escolher</button><button type="button" class="btn btn-sm btn-ghost" data-image-remove>Remover</button></div>
            <span class="hint">Usada quando o post não tem capa.</span>
          </div>
        </div>
      </div>
    </section>

    <section class="section" id="aparencia" tabindex="-1">
      <header class="section-head">
        <h2>Aparência</h2>
        <p>Cor e fontes do site. As fontes são as que já vêm nos aparelhos, então nada é baixado e o site continua leve.</p>
      </header>
      <div class="stack">
        <div>
          <?php // O texto (hex) é o que vale; o seletor ao lado só ajuda a escolher (admin.js sincroniza os dois). ?>
          <div class="field field-inline">
            <label for="accent_color">Cor de destaque</label>
            <span class="color-input" data-color-input>
              <input type="text" id="accent_color" name="accent_color" value="<?= e($s['accent_color']) ?>" placeholder="#2f5bea" spellcheck="false" autocomplete="off">
              <input type="color" value="<?= e($s['accent_color']) ?>" aria-label="Escolher a cor">
            </span>
          </div>
          <?php // Os links do site usam esta cor sobre fundo branco; admin.js atualiza o aviso ao trocar a cor. ?>
          <span class="hint danger" data-accent-warning<?= contrast_ratio($s['accent_color'], '#ffffff') >= 4.5 ? ' hidden' : '' ?>>Esta cor tem pouco contraste com o fundo branco: os links do site ficam difíceis de ler. Prefira um tom mais escuro.</span>
        </div>
        <label class="check"><input type="checkbox" name="browser_accent"<?= $s['browser_accent'] ? ' checked' : '' ?>> Pintar a barra do navegador do celular com a cor de destaque</label>
        <div class="grid-2">
          <label class="field">Fonte do texto
            <select name="font_text" data-font-select="text">
              <?php foreach ($fonts as $key => $font): ?><option value="<?= e($key) ?>" data-stack="<?= e($font['stack']) ?>"<?= $key === $s['font_text'] ? ' selected' : '' ?>><?= e($font['label']) ?></option><?php endforeach ?>
            </select>
          </label>
          <label class="field">Fonte dos títulos
            <select name="font_heading" data-font-select="heading">
              <option value="">Igual ao texto</option>
              <?php foreach ($fonts as $key => $font): ?><option value="<?= e($key) ?>" data-stack="<?= e($font['stack']) ?>"<?= $key === $s['font_heading'] ? ' selected' : '' ?>><?= e($font['label']) ?></option><?php endforeach ?>
            </select>
          </label>
        </div>
        <div>
          <div class="font-preview" data-font-preview>
            <p class="font-preview-title">Como escolher a fonte do seu blog</p>
            <p class="font-preview-text">Uma boa fonte deixa o texto agradável de ler do começo ao fim, no computador e no celular.</p>
          </div>
          <span class="hint">A prévia usa as fontes deste computador. Em outros aparelhos o desenho pode mudar um pouco, mas o estilo se mantém.</span>
        </div>
      </div>
    </section>

    <section class="section" id="estilo" tabindex="-1">
      <header class="section-head">
        <h2>Estilo</h2>
        <p>Cantos, espaços e proporções que valem para o site inteiro. A prévia mostra <?= $previewItem ? 'o seu post mais recente' : 'a página inicial' ?> com estas escolhas, a cor e as fontes, antes de salvar.</p>
      </header>
      <div class="stack">
        <div class="tokens">
          <?php foreach ($tokens as $key => $token): ?>
            <fieldset class="token" data-token-default="<?= e($token['default']) ?>">
              <legend><?= e($token['label']) ?></legend>
              <div class="token-options">
                <?php foreach ($token['options'] as $value => $option): ?>
                  <label class="choice">
                    <input type="radio" name="tokens[<?= e($key) ?>]" value="<?= e($value) ?>"<?= $value === $tokenChoices[$key] ? ' checked' : '' ?>>
                    <?php // A miniatura recebe as variáveis CSS da própria opção; o desenho fica em admin.css. ?>
                    <span class="choice-thumb" aria-hidden="true"><span class="swatch" data-swatch="<?= e($key) ?>" style="<?= e(Settings::css($option['css'])) ?>"><i></i><i></i><i></i></span></span>
                    <span class="choice-name"><?= e($option['label']) ?></span>
                  </label>
                <?php endforeach ?>
              </div>
              <span class="hint"><?= e($token['hint']) ?></span>
            </fieldset>
          <?php endforeach ?>
        </div>
        <div><button type="button" class="btn btn-sm" data-tokens-reset>Voltar ao padrão</button></div>
        <?= render_file(__DIR__ . '/_site-preview.php', [
            'src' => url('/admin/settings/preview?page=post'),
            'address' => $host . ($previewItem ? '/' . $previewItem['slug'] . '/' : ''),
            'title' => 'Prévia de um post',
        ]) ?>
      </div>
    </section>

    <section class="section" id="pagina-inicial" tabindex="-1">
      <header class="section-head">
        <h2>Página inicial</h2>
        <p>O que aparece no início do blog e como os posts são organizados. A prévia mostra a lista de posts antes de salvar.</p>
      </header>
      <div class="stack">
        <?php
        // Opções das duas listas: páginas que não estão na lixeira. As que não estão no ar avisam no nome.
        $pageOptions = function (int $selected) use ($pages) {
            foreach ($pages as $page) {
                $state = Posts::isPublic($page) ? '' : (Posts::isScheduled($page) ? ' (agendada)' : ' (rascunho)');
                echo '<option value="', (int) $page['id'], '"', $page['id'] === $selected ? ' selected' : '', '>',
                    e(($page['title'] !== '' ? $page['title'] : '(sem título)') . $state), '</option>';
            }
        };
        ?>
        <div class="grid-2">
          <label class="field">A página inicial mostra
            <select name="home_page" data-home-page>
              <option value="0">Os posts mais recentes</option>
              <?php if ($pages): ?><optgroup label="Uma página fixa"><?php $pageOptions((int) $s['home_page']) ?></optgroup><?php endif ?>
            </select>
            <span class="hint">Monte a página em <a href="<?= e(url('/admin/posts?type=page')) ?>">Páginas</a>, como qualquer outra, e escolha aqui. Ela abre no endereço principal do blog. Enquanto não estiver publicada, o início continua mostrando os posts.</span>
          </label>
          <?php // Só vale com página fixa no início: admin.js esconde quando o início mostra os posts. ?>
          <label class="field" data-blog-page>Página dos posts
            <select name="blog_page">
              <option value="0">Nenhuma</option>
              <?php $pageOptions((int) $s['blog_page']) ?>
            </select>
            <span class="hint">A lista de posts passa a ficar no endereço desta página (crie uma página “Blog”, por exemplo; o texto dela aparece acima da lista). Para os visitantes chegarem lá, coloque <b>Página dos posts</b> no Menu.</span>
          </label>
        </div>

        <hr>
        <h3>Estrutura da lista de posts</h3>
        <div class="layout-picker" role="radiogroup" aria-label="Estrutura da página inicial" data-layout-picker>
          <?php foreach ($layouts as $key => $layout): ?>
            <label class="choice">
              <input type="radio" name="home_layout" value="<?= e($key) ?>" data-options="<?= e(implode(' ', $layout['options'])) ?>" data-description="<?= e($layout['description']) ?>"<?= $key === $s['home_layout'] ? ' checked' : '' ?>>
              <span class="choice-thumb layout-thumb" aria-hidden="true"><?= $thumbs[$key] ?? '' ?></span>
              <span class="choice-name"><?= e($layout['label']) ?></span>
            </label>
          <?php endforeach ?>
        </div>
        <p class="hint layout-description" data-layout-description><?= e($layouts[$s['home_layout']]['description'] ?? '') ?></p>
        <div class="home-options">
          <label class="check" data-home-option="featured"><input type="checkbox" name="home_featured"<?= $s['home_featured'] ? ' checked' : '' ?>> Destacar o post mais recente</label>
          <label class="field field-inline" data-home-option="columns">Colunas
            <select name="home_columns">
              <?php foreach ([2, 3, 4] as $n): ?><option value="<?= $n ?>"<?= $n === (int) $s['home_columns'] ? ' selected' : '' ?>><?= $n ?></option><?php endforeach ?>
            </select>
          </label>
          <?php // Com página fixa no início, a lista mostra o título e o texto da página dos posts no lugar da frase. ?>
          <label class="check" data-home-intro><input type="checkbox" name="home_intro"<?= $s['home_intro'] ? ' checked' : '' ?>> Mostrar a frase de apresentação no topo</label>
        </div>

        <?php $blogPage = Posts::blogPage(); ?>
        <?= render_file(__DIR__ . '/_site-preview.php', ['src' => url('/admin/settings/preview'), 'address' => $host . ($blogPage ? '/' . $blogPage['slug'] . '/' : ''), 'title' => 'Prévia da lista de posts']) ?>
        <span class="hint">A prévia usa as configurações desta tela, mesmo as que ainda não foram salvas. Os links dela não abrem; use a rolagem para ver o resto da página.</span>
      </div>
    </section>

    <section class="section" id="menu" tabindex="-1">
      <header class="section-head">
        <h2>Menu</h2>
        <p>Os links do topo de todas as páginas. Escolha páginas, posts, categorias ou tags do blog, ou crie links para outros sites.</p>
        <p>Um item com subitens vira uma lista que abre ao passar o mouse (no celular, ao tocar).</p>
      </header>
      <div class="menu-builder" data-menu="<?= e(url('/admin/settings/menu-items')) ?>">
        <ol class="menu-list" data-menu-list>
          <?php foreach ($menuRows as $i => $row): ?><?= render_file(__DIR__ . '/_menu-row.php', ['i' => $i, 'row' => $row]) ?><?php endforeach ?>
        </ol>
        <p class="menu-empty">O menu está vazio. Adicione o primeiro item abaixo.</p>
        <?php foreach (['item' => 'page', 'link' => 'link'] as $key => $type): ?>
          <template data-menu-template="<?= e($key) ?>"><?= render_file(__DIR__ . '/_menu-row.php', ['i' => '__i__', 'row' => Menu::describe(['type' => $type, 'id' => 0, 'url' => '', 'label' => '']) + ['sub' => false]]) ?></template>
        <?php endforeach ?>

        <div class="menu-add">
          <div class="menu-add-head">
            <strong>Adicionar ao menu</strong>
            <div class="segmented" role="group" aria-label="Tipo de item">
              <?php foreach (['page' => 'Páginas', 'post' => 'Posts', 'category' => 'Categorias', 'tag' => 'Tags', 'link' => 'Link'] as $type => $label): ?>
                <button type="button" data-menu-tab="<?= e($type) ?>" aria-pressed="<?= $type === 'page' ? 'true' : 'false' ?>"><?= e($label) ?></button>
              <?php endforeach ?>
            </div>
          </div>
          <div data-menu-pane="search">
            <input type="search" data-menu-search placeholder="Buscar pelo título…" aria-label="Buscar itens para o menu" autocomplete="off">
            <ul class="menu-results" data-menu-results aria-live="polite"></ul>
            <span class="hint" data-menu-more hidden>Mostrando só os primeiros. Busque pelo título para achar os outros.</span>
          </div>
          <div class="menu-link" data-menu-pane="link" hidden>
            <label class="field">Nome no menu
              <input data-menu-link-label placeholder="Loja" autocomplete="off">
            </label>
            <label class="field">Endereço
              <input data-menu-link-url placeholder="https://..." autocomplete="off" autocapitalize="off" spellcheck="false">
            </label>
            <button type="button" class="btn" data-menu-link-add>Adicionar</button>
            <label class="check"><input type="checkbox" data-menu-link-newtab> Abrir em nova aba</label>
            <span class="hint">Sem endereço, o item vira só um título que abre os subitens: crie “Produtos” e coloque as páginas embaixo dele.</span>
          </div>
        </div>
        <span class="hint">Use as setas (ou arraste pela alça) para mudar a ordem, e <b>→</b> para colocar um item dentro do que está acima. Com o nome vazio, o menu usa o título da página ou o nome da categoria e acompanha quando ele muda. Marque <b>Nova aba</b> nos links que devem abrir em outra aba, como os de outros sites.</span>
      </div>
    </section>

    <section class="section" id="redes-sociais" tabindex="-1">
      <header class="section-head">
        <h2>Redes sociais</h2>
        <p>Ícones com link para os seus perfis, no rodapé de todas as páginas.</p>
      </header>
      <div class="social" data-social>
        <div class="social-list" data-social-list>
          <?php foreach ($s['social'] as $item): ?>
            <?php if (isset($networks[$item['network']])): ?>
              <?= render_file(__DIR__ . '/_social-row.php', ['key' => $item['network'], 'network' => $networks[$item['network']], 'user' => $item['user']]) ?>
            <?php endif ?>
          <?php endforeach ?>
        </div>
        <select class="social-add" data-social-add aria-label="Adicionar rede social">
          <option value="">+ Adicionar rede social</option>
          <?php foreach ($networks as $key => $network): ?><option value="<?= e($key) ?>"><?= e($network['label']) ?></option><?php endforeach ?>
        </select>
        <span class="hint">Escreva só o nome de usuário. Se colar o link do perfil, ele é ajustado ao salvar. Os ícones aparecem nesta ordem.</span>
        <?php foreach ($networks as $key => $network): ?>
          <template data-social-template="<?= e($key) ?>"><?= render_file(__DIR__ . '/_social-row.php', ['key' => $key, 'network' => $network, 'user' => '']) ?></template>
        <?php endforeach ?>
      </div>
    </section>

    <section class="section" id="leitura" tabindex="-1">
      <header class="section-head">
        <h2>Leitura</h2>
        <p>Quantos posts aparecem por vez e o que cada um exibe.</p>
      </header>
      <div class="stack">
        <div class="grid-2">
          <label class="field">Posts por página
            <input type="number" name="posts_per_page" min="1" max="100" value="<?= (int) $s['posts_per_page'] ?>">
          </label>
        </div>
        <label class="check"><input type="checkbox" name="show_author"<?= $s['show_author'] ? ' checked' : '' ?>> Mostrar o nome do autor nos posts</label>
        <label class="field">“Leia também” no fim dos posts
          <select name="related_posts">
            <?php foreach ([0 => 'Não mostrar', 3 => '3 posts', 6 => '6 posts'] as $count => $label): ?>
              <option value="<?= $count ?>"<?= $count === (int) $s['related_posts'] ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach ?>
          </select>
          <span class="hint">Posts da mesma categoria e com tags em comum. Ajuda o leitor a continuar no blog e o Google a achar os posts antigos.</span>
        </label>
      </div>
    </section>

    <section class="section" id="endereco" tabindex="-1">
      <header class="section-head">
        <h2>Endereço e região</h2>
        <p>Endereço oficial, fuso horário e idioma do site.</p>
      </header>
      <div class="stack">
        <label class="field">Endereço do site
          <input name="url" value="<?= e($s['url']) ?>" placeholder="https://meublog.com.br">
          <span class="hint">Usado no sitemap e nos links de compartilhamento. Vazio = detectar automaticamente. Preencha em produção: visitas por outros endereços (http://, com ou sem www) passam a ser redirecionadas para este, como o Google prefere.</span>
        </label>
        <div class="grid-2">
          <label class="field">Fuso horário
            <select name="timezone">
              <?php foreach ($timezones as $tz): ?><option<?= $tz === $s['timezone'] ? ' selected' : '' ?>><?= e($tz) ?></option><?php endforeach ?>
            </select>
          </label>
          <label class="field">Idioma
            <select name="language">
              <?php foreach (['pt-BR' => 'Português (Brasil)', 'pt-PT' => 'Português (Portugal)', 'en' => 'Inglês', 'es' => 'Espanhol'] as $code => $label): ?>
                <option value="<?= e($code) ?>"<?= $code === $s['language'] ? ' selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach ?>
            </select>
          </label>
        </div>
      </div>
    </section>

    <section class="section" id="rodape" tabindex="-1">
      <header class="section-head">
        <h2>Rodapé e códigos</h2>
        <p>Texto do rodapé e códigos de terceiros, como analytics.</p>
      </header>
      <div class="stack">
        <label class="field">Texto do rodapé (aceita HTML)
          <textarea name="footer_text" rows="3" class="mono"><?= e($s['footer_text']) ?></textarea>
        </label>
        <label class="field">Código no &lt;head&gt;
          <textarea name="head_code" rows="4" class="mono" placeholder="Google Analytics, Search Console, pixel..."><?= e($s['head_code']) ?></textarea>
          <span class="hint">Substitui os plugins de analytics do WordPress. Vai em todas as páginas públicas. A verificação do Search Console tem campo próprio em <a href="<?= e(url('/admin/seo')) ?>">SEO</a>.</span>
        </label>
        <label class="field">Código antes do &lt;/body&gt;
          <textarea name="body_code" rows="3" class="mono" placeholder="Chat, widgets..."><?= e($s['body_code']) ?></textarea>
        </label>
      </div>
    </section>
  </div>

  <div class="sticky-actions"><button class="btn btn-primary">Salvar configurações</button></div>
</form>
