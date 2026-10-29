<?php
/**
 * Painel → SEO: situação, títulos, indexação, dados estruturados, verificação e robots.txt.
 * @var array $s  configurações  @var int $missing  @var int $hidden  @var int $total
 */
$url = (string) $s['url'];
$host = preg_replace('#^https?://#', '', site_url());
$link = fn(string $path, string $label) => '<a href="' . e(url($path)) . '">' . e($label) . '</a>';
// Situação: [estado (good|ok|bad), HTML]. Textos dinâmicos passam por e().
$status = [
    $url !== ''
        ? ['good', 'Endereço oficial: <strong>' . e($url) . '</strong>. Visitas por http://, www ou outro domínio são redirecionadas para ele.']
        : ['bad', 'Preencha o Endereço do site em ' . $link('/admin/settings#endereco', 'Configurações → Endereço e região') . '. Sem ele, o Google pode achar o blog em mais de um endereço (com e sem www, http e https).'],
    $url === '' || str_starts_with($url, 'https://')
        ? null
        : ['ok', 'O endereço oficial não usa https. O Google prefere sites seguros: ative o certificado na hospedagem e troque para https://.'],
    $s['seo_hide_site']
        ? ['bad', 'O site inteiro está <strong>escondido dos buscadores</strong> (veja Indexação, abaixo).']
        : ['good', 'O site está liberado para os buscadores.'],
    $s['seo_google']
        ? ['good', 'Verificação do Google Search Console configurada.']
        : ['ok', 'Verifique o site no Google Search Console (código em Verificação, abaixo) e envie o sitemap por lá.'],
    $missing
        ? ['ok', $missing . ' endereço(s) deram “página não encontrada” recentemente. ' . $link('/admin/redirects', 'Ver e redirecionar')]
        : ['good', 'Nenhum endereço quebrado registrado.'],
    Media::canThumb()
        ? ['good', 'Imagens com versões menores para celular e listas (páginas mais leves).']
        : ['ok', 'O servidor não consegue gerar versões menores das imagens: confira a extensão GD do PHP e a permissão de escrita em <code>public/</code>.'],
    $hidden ? ['ok', $hidden . ' de ' . $total . ' posts e páginas estão marcados para ficar fora do Google.'] : null,
];
?>
<div class="page-head"><h1>SEO</h1></div>
<?= render_file(__DIR__ . '/_seo-tabs.php', ['tab' => 'general', 'missing' => $missing]) ?>

<form method="post" class="settings" data-seo="home" data-site="<?= e($s['title']) ?>" data-tagline="<?= e($s['tagline']) ?>" data-host="<?= e($host) ?>">
  <?= csrf_field() ?>

  <div class="panel sections">
    <section class="section">
      <header class="section-head">
        <h2>Situação</h2>
        <p>O básico para o Google encontrar e entender o blog.</p>
      </header>
      <ul class="seo-checks">
        <?php foreach (array_filter($status) as [$state, $html]): ?><li data-state="<?= e($state) ?>"><?= $html ?></li><?php endforeach ?>
      </ul>
    </section>

    <section class="section">
      <header class="section-head">
        <h2>Página inicial e títulos</h2>
        <p>Como o blog aparece no Google e como os títulos das páginas são montados.</p>
      </header>
      <div class="stack">
        <?= render_file(__DIR__ . '/_snippet.php') ?>
        <label class="field">Título da página inicial
          <input name="seo_home_title" value="<?= e($s['seo_home_title']) ?>" maxlength="120" placeholder="Vazio = nome do blog e frase de apresentação">
          <span class="meter" data-meter="title" data-field="seo_home_title"></span>
        </label>
        <label class="field">Descrição do blog para o Google
          <textarea name="description" rows="3" maxlength="300" placeholder="Vazio = frase de apresentação"><?= e($s['description']) ?></textarea>
          <span class="meter" data-meter="desc" data-field="description"></span>
        </label>
        <fieldset class="field">
          <legend>Separador dos títulos</legend>
          <div class="row choices-inline">
            <?php foreach (Seo::SEPARATORS as $sep): ?>
              <label class="check sep-choice"><input type="radio" name="seo_separator" value="<?= e($sep) ?>"<?= $sep === Seo::separator() ? ' checked' : '' ?>> <span><?= e($sep) ?></span></label>
            <?php endforeach ?>
          </div>
          <span class="hint">Entre o título da página e o nome do blog: “Como fazer um deck <?= e(Seo::separator()) ?> <?= e($s['title']) ?>”.</span>
        </fieldset>
      </div>
    </section>

    <section class="section">
      <header class="section-head">
        <h2>Indexação</h2>
        <p>O que pode aparecer no Google. Páginas de fora também saem do sitemap.</p>
      </header>
      <div class="stack">
        <label class="check"><input type="checkbox" name="seo_index_tags"<?= $s['seo_index_tags'] ? ' checked' : '' ?>> Páginas de tag no Google</label>
        <p class="hint">Desmarque se as tags têm poucos posts cada: páginas quase vazias podem pesar contra o blog.</p>
        <label class="check"><input type="checkbox" name="seo_index_authors"<?= $s['seo_index_authors'] ? ' checked' : '' ?>> Páginas de autor no Google</label>
        <p class="hint">Num blog com um só autor, a página do autor repete a página inicial: melhor desmarcar.<?= $s['show_author'] ? '' : ' (Hoje o nome do autor não aparece nos posts, então elas já ficam de fora.)' ?></p>
        <p class="muted">Para tirar do Google um post ou uma página, marque <strong>Esconder dos buscadores</strong> na edição dele; para uma categoria ou tag, use a aba <?= $link('/admin/seo/terms', 'Categorias e tags') ?>.</p>
        <p class="muted">Sitemap: <a href="<?= e(url('/sitemap.xml')) ?>" target="_blank" rel="noopener"><?= e(absolute_url('/sitemap.xml')) ?></a>, com posts, páginas, categorias<?= $s['seo_index_tags'] ? ', tags' : '' ?><?= $s['seo_index_authors'] && $s['show_author'] ? ', autores' : '' ?> e as imagens de capa. Envie esse endereço no Google Search Console e no Bing.</p>
        <hr>
        <label class="check danger-check"><input type="checkbox" name="seo_hide_site"<?= $s['seo_hide_site'] ? ' checked' : '' ?>> Esconder o site inteiro dos buscadores</label>
        <p class="hint">Só enquanto o blog está em testes. Esquecer marcado tira o blog do Google.</p>
      </div>
    </section>

    <section class="section">
      <header class="section-head">
        <h2>Quem publica</h2>
        <p>Dados estruturados que o Google usa no painel de conhecimento e nos resultados.</p>
      </header>
      <div class="stack">
        <div class="row choices-inline">
          <label class="check"><input type="radio" name="seo_entity" value="organization"<?= $s['seo_entity'] !== 'person' ? ' checked' : '' ?>> Empresa ou organização</label>
          <label class="check"><input type="radio" name="seo_entity" value="person"<?= $s['seo_entity'] === 'person' ? ' checked' : '' ?>> Pessoa</label>
        </div>
        <label class="field">Nome
          <input name="seo_entity_name" value="<?= e($s['seo_entity_name']) ?>" placeholder="Vazio = <?= e($s['title']) ?>">
        </label>
        <div class="field" data-image-field>
          <span>Logo ou foto</span>
          <div class="image-preview small"></div>
          <input type="hidden" name="seo_entity_logo" value="<?= e($s['seo_entity_logo']) ?>">
          <div class="row"><button type="button" class="btn btn-sm" data-image-pick>Escolher</button><button type="button" class="btn btn-sm btn-ghost" data-image-remove>Remover</button></div>
          <span class="hint">Vazio = logo do blog. As redes sociais de Configurações entram como perfis oficiais.</span>
        </div>
      </div>
    </section>

    <section class="section">
      <header class="section-head">
        <h2>Verificação</h2>
        <p>Prova para o Google e o Bing de que o site é seu.</p>
      </header>
      <div class="stack">
        <label class="field">Google Search Console
          <input name="seo_google" value="<?= e($s['seo_google']) ?>" placeholder="Código ou a tag &lt;meta name=&quot;google-site-verification&quot; ...&gt;" autocomplete="off">
          <span class="hint">No Search Console: adicionar propriedade → Prefixo do URL → Tag HTML. Cole a tag inteira ou só o código.</span>
        </label>
        <label class="field">Bing Webmaster Tools
          <input name="seo_bing" value="<?= e($s['seo_bing']) ?>" placeholder="Código ou a tag &lt;meta name=&quot;msvalidate.01&quot; ...&gt;" autocomplete="off">
        </label>
      </div>
    </section>

    <section class="section">
      <header class="section-head">
        <h2>robots.txt</h2>
        <p>Regras para os robôs dos buscadores.</p>
      </header>
      <div class="stack">
        <p class="muted">Hoje o <a href="<?= e(url('/robots.txt')) ?>" target="_blank" rel="noopener">robots.txt</a> está assim:</p>
        <pre class="code-box"><?= e(Seo::robotsTxt()) ?></pre>
        <label class="field">Linhas extras
          <textarea name="seo_robots" rows="3" class="mono" spellcheck="false" placeholder="Disallow: /pasta-privada/"><?= e($s['seo_robots']) ?></textarea>
          <span class="hint">Entram depois do bloqueio do painel. Cuidado: <code>Disallow: /</code> tira o blog inteiro do Google.</span>
        </label>
      </div>
    </section>
  </div>

  <div class="sticky-actions"><button class="btn btn-primary">Salvar SEO</button></div>
</form>
