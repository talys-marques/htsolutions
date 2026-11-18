# Guia para quem (ou qual IA) for mexer no código

Blog em PHP 8.1+ **sem banco de dados e sem dependências**. Novas funcionalidades
são feitas editando este código diretamente. A regra de ouro é manter tudo
simples: menos peças = menos coisas que quebram.

## Regras do projeto

- **Sem Composer, npm, frameworks ou build.** Nada de bibliotecas externas, nem
  por CDN no painel. Se precisar de algo, escreva o mínimo necessário.
- **Sem banco de dados.** Todo dado persistente é JSON em `data/`, lido e
  gravado **só** via `Storage::read()` / `Storage::write()`. Operações "ler,
  alterar, gravar" ficam dentro de `Storage::lock(fn() => ...)`.
- **PHP 8.1 compatível** (hospedagens compartilhadas). Não use recursos do 8.2+
  (readonly classes, constantes tipadas, `json_validate`...).
- **Textos da interface em português do Brasil**; código (nomes de classes,
  funções, variáveis) em inglês. Comentários em português, curtos.
- **Segurança:**
  - Imprima dados sempre com `e()`: `<?= e($valor) ?>`. A exceção é o HTML do
    conteúdo dos posts e os campos de código das configurações (`head_code` etc.).
  - Todo formulário POST do painel leva `<?= csrf_field() ?>` e todo handler
    POST chama `verify_csrf()` logo no início.
  - Todo handler do painel começa com `Auth::requireLogin()` ou
    `Auth::requireAdmin()`. Para posts, use `Auth::requireCanEdit($post)`.
  - HTML enviado por autores (não-admin) passa por `Html::clean()`, que é uma
    lista branca: para liberar uma tag ou atributo, inclua em `Html::TAGS`.
  - O painel envia CSP com `script-src 'self'`: nada de `<script>` inline,
    `onclick=` ou `href="javascript:..."` nas views. JS vai em `public/assets/admin.js`.
- **Não quebre URLs públicas** (`/slug/`, `/category/x/`, `/tag/x/`,
  `/sitemap.xml`). Elas seguem o padrão do WordPress por causa do SEO. Se
  precisar mudar um endereço, crie um redirecionamento com `Redirects::add()`.
- **O blog funciona em qualquer pasta** (raiz do domínio ou `/blog/`). Em `data/`,
  imagens e links internos ficam gravados sem a pasta (`/uploads/foto.jpg`).
  `Posts`, `Settings` e `Terms` colocam a pasta atual ao ler e tiram ao gravar
  (`add_base()` / `strip_base()` em `app/helpers.php`), então o resto do código
  usa os valores direto. Campo novo que guarda endereço ou HTML: inclua no
  `paths()` da classe. Caminhos fixos nos templates usam `url()`.
- **SEO fica no núcleo** (não há plugin): toda página pública monta `$meta`
  (campos no comentário de `app/Seo.php`) e o `<head>` sai de `Seo::head()`.
  Imagens de uploads nos templates usam `image_tag()` (srcset com versões menores).
- Visitantes não podem receber cookie. Sessão só no painel (`Auth::startSession()`
  já é chamado para rotas `/admin`).

## Onde fica cada coisa

| Quero... | Arquivo |
|---|---|
| Nova página pública | rota em `app/routes/site.php` → método em `app/Site.php` → template em `theme/` |
| Página que o time de negócio edita por campos fixos (Conteúdo do site) | um modelo no tema: `fields/{nome}.php` (campos) + `templates/{nome}.php` (HTML) + CSS em `assets/style.css` + conteúdo inicial opcional em `content/{nome}.json`. Passo a passo em `themes/README.md` ("Páginas com campos"). Exemplo pronto: `theme/fields/landing.php` + `theme/templates/landing.php` |
| Novo tipo de campo para os modelos | `Field` (construtor + `clean()`, `fill()`, `paths()`, `plainText()`) → formulário em `app/views/admin/_field.php` → leitura em `public/assets/admin.js` (seção "Conteúdo do site") |
| Nova tela no painel | rota em `app/routes/admin.php` → método em `app/Admin.php` → template em `app/views/admin/` → link no menu em `app/views/admin/layout.php` |
| Novo campo no post | `Posts::blank()` (valor padrão) → formulário em `app/views/admin/edit.php` → leitura em `Admin::savePost()` → exibição em `theme/post.php`. Se precisar aparecer em listagens, inclua também em `Posts::indexItem()` |
| Nova configuração | `Settings::defaults()` → `app/views/admin/settings.php` → `Admin::settingsInput()` → use com `setting('chave')` |
| Página fixa no início e página dos posts (como no WordPress) | escolha em Configurações → Página inicial (`home_page`, `blog_page`), lida por `Posts::homePage()` / `Posts::blogPage()`. Início em `Site::home()`, lista de posts em `Site::blog()`. Links de posts usam `post_url()` / `post_path()`, que já devolvem `/` para a página inicial. No menu, item `blog` de `Menu` |
| Nova estrutura da página inicial | `Settings::homeLayouts()` → `theme/home.php` + `public/assets/site.css` → miniatura em `$shapes` de `app/views/admin/settings.php`. A prévia em Configurações (`Admin::settingsPreview()`) já mostra a nova sem mudanças |
| Nova rede social no rodapé | `Social::networks()` (endereço e ícone SVG) |
| Novo tipo de item no menu do topo | `Menu::TYPES` → `normalize()`, `resolve()`, `describe()` e `search()` em `app/Menu.php` (formato no comentário da classe) → aba do seletor em `app/views/admin/settings.php` |
| Mudar o visual do tema Padrão (o que vale para todo blog) | `theme/*.php` e `public/assets/site.css` (modo claro/escuro: `public/assets/site.js`) |
| Visual para uma loja, tela inicial diferente, blocos e páginas extras | um tema em `themes/{nome}/` (veja Temas abaixo e o guia `themes/README.md`) |
| Sistema de temas (instalar .zip, ativar, prévia, opções, baixar .zip) | `app/Themes.php` (+ `app/Zip.php`) → tela `app/views/admin/themes.php` e `theme-options.php` → handlers `Admin::themes()`, `themeOptions()`, `themeFrame()`, `themePreview()` |
| Novo lugar onde temas podem pôr blocos | `slot('nome', get_defined_vars())` no template de `theme/` + tabela de slots em `themes/README.md` |
| Sugestões da busca enquanto digita | dados em `Site::suggest()` (`/search.json`), lista em `public/assets/site.js`. Vale para todo formulário com `data-suggest` |
| Novo ajuste de estilo (token: cantos, espaços...) | `Settings::tokens()` (opções e variáveis CSS) → use a variável em `public/assets/site.css` → miniatura em `public/assets/admin.css` (`.swatch[data-swatch=chave]`) |
| Mudar o editor | `public/assets/admin.js` (seção "Editor visual") |
| Título, meta tags, Open Graph, dados estruturados, robots.txt | `app/Seo.php`. Opções em Painel → SEO: chaves `seo_*` de `Settings::defaults()` → `app/views/admin/seo.php` → `Admin::seo()` |
| Análise de SEO no editor (prévia do Google, palavra-chave) | `public/assets/admin.js` (seção "SEO"), para todo formulário com `data-seo` |
| Textos de SEO de categorias e tags | `app/Terms.php` (`data/terms.json`) → telas `seo-terms.php` e `seo-term.php` |
| Versões menores das imagens (srcset) | `Media::srcset()` e `Media::thumb()` (rota `/thumbs/...`), gravadas em `public/thumbs/`. É cache: pode apagar a pasta, ela se refaz |
| "Leia também" (posts relacionados) | escolha em `Posts::related()`, exibição em `theme/post.php`; quantidade em Configurações → Leitura (`related_posts`) |
| Endereços que deram 404 | `Redirects::logMiss()` (`data/not-found.json`), listados em Redirecionamentos para virar redirecionamento |
| Funções utilitárias | `app/helpers.php` |
| Script de terminal | `tools/` (comece com `require dirname(__DIR__) . '/app/bootstrap.php';`) |
| Recurso opcional, que o dono pode querer ou não levar junto | uma extensão em `extensions/{nome}/` (veja abaixo) |

Classes em `app/NomeDaClasse.php` e `extensions/{nome}/NomeDaClasse.php` são
carregadas automaticamente.

## Conteúdo do site (páginas com campos)

Para sites institucionais e landing pages: quem desenvolve cria a página (campos + template),
o time de negócio só troca o conteúdo num formulário em Painel → Conteúdo do site.

- A página é uma página do blog (`type: page`) com `template` (nome do modelo) e `fields`
  (os valores). Ganha endereço, SEO, sitemap, busca, lixeira e versões do blog.
- O modelo mora no tema: `fields/{nome}.php` devolve `label`, `fields` (com `Field::*`) e,
  se quiser, `description`, `order`, `repeatable` (várias páginas, ex.: uma por marca) e
  `shared` (conteúdo de todas as páginas, como topo e rodapé, lido com `SiteContent::shared()`).
  `templates/{nome}.php` recebe `$fields` completos (toda chave existe). O tema padrão vale
  como reserva, igual aos outros templates.
- Os campos vêm do JSON montado pelo `admin.js` e passam por `Field::clean()` ao salvar
  (só ficam as chaves do modelo; `html` passa por `Html::clean()`). No template, imprima
  texto com `e()`; `html` e `heading` vão direto.
- `Posts` aplica a pasta do blog nos campos de imagem, arquivo, link e HTML (`Field::paths()`),
  põe o texto dos campos na busca e no resumo, e restaura `template` e `fields` nas versões.
- Campo novo num modelo não quebra páginas antigas: `Field::fill()` completa com vazio.
  Renomear uma chave perde o valor antigo (não há migração automática).
- O código está em `app/SiteContent.php` (modelos, páginas, conteúdo inicial),
  `app/Field.php` (tipos de campo) e nos handlers `Admin::content*()`.

## Temas

O tema Padrão é `theme/`. Cada tema instalado é uma pasta `themes/{nome}/` que só traz
o que muda: `theme()` e `partial()` usam o arquivo do tema ativo e, se ele não tiver, o
de `theme/` (`Themes::file()`). Guia completo para quem cria temas: `themes/README.md`.

- `theme.json` (obrigatório): nome, descrição e `options`, que viram o formulário de
  Painel → Temas → Personalizar. Valores com `theme_option('chave')`, gravados em
  `data/theme-options.json`. Tipos em `Themes::OPTION_TYPES`.
- `slots/{lugar}.php`: blocos nos lugares marcados com `slot()` nos templates de `theme/`
  (inclusive `header-actions`, dentro do cabeçalho).
  **Ao mexer em `theme/`, mantenha as chamadas `slot()`**: os temas dependem delas.
- `functions.php` (opcional): funções do tema, `'body_class'` (classes no `<body>` para o CSS
  seguir as opções) e `'routes'` para páginas novas (antes de `/{slug}`, nunca no `/admin`).
- `assets/`: `style.css` e `script.js` entram sozinhos; o resto com `theme_asset()`. Os
  temas ficam fora de `public/`, então os arquivos passam pela rota `/themes/{nome}/assets/...`.
- O tema escolhido fica em `setting('theme')` (`default` = `theme/`). O admin pode
  pré-visualizar outro tema só para ele (sessão `theme_preview`).
- Copiar entre sites: Baixar .zip (`Themes::export()`, leva `options.json` e as imagens das
  opções) → Enviar tema (`Themes::installZip()`). O .zip é lido em PHP puro (`Zip`), sem a extensão zip.
- O núcleo nunca depende de um tema específico. Tema = aparência e blocos de um site;
  extensão = recurso opcional do painel. Dado que precisa sobreviver à troca de tema
  não é do tema.

## Extensões

Recursos opcionais ficam isolados numa pasta `extensions/{nome}/`. Apagar a pasta
remove o recurso sem quebrar nada. Exemplo pronto: `extensions/wordpress-import/`.

- **O núcleo (`app/`, `theme/`) nunca referencia uma extensão.** Só a extensão
  conhece o núcleo, nunca o contrário.
- `extensions/{nome}/extension.php` devolve um array com `name`, e opcionalmente
  `routes` (função que recebe o `$router`) e `menu` (itens do painel). Formato
  completo no comentário de `app/Extensions.php`.
- Telas do painel: `admin_view(__DIR__ . '/views/tela.php', [...])`.
- Script de terminal: `require dirname(__DIR__, 2) . '/app/bootstrap.php';`
- Dados que a extensão criar devem continuar válidos sem ela (ex.: posts
  importados são posts normais em `data/posts/`).
- Cada extensão tem um `README.md` curto dizendo o que faz e o que acontece ao apagá-la.

Antes de criar uma extensão, pergunte se o recurso é mesmo opcional. Se todo
blog precisa dele, ele vai no núcleo.

## Como os dados ficam guardados

```
data/settings.json        configurações do painel
data/users.json           usuários { "login": { name, email, role, password (hash) } }
data/posts/{id}.json      um arquivo por post/página (fonte da verdade)
data/index.json           metadados de todos os posts (derivado; refeito por Posts::rebuildIndex())
data/search.json          palavras de cada post para a busca (derivado)
data/redirects.json       { "/antigo": "/novo/" }
data/terms.json           SEO de categorias e tags { "category": { "slug": { description, seo_title, seo_description, noindex } } }
data/not-found.json       últimos endereços com 404 { "/caminho": { count, last, from } }
data/theme-options.json   opções de cada tema { "tema": { "chave": valor } } (imagens do próprio tema como "assets/...")
data/revisions/{id}/      versões anteriores de cada post
data/sessions/, cache/, logs/, import/
```

Campos de um post: `id, type (post|page), status (draft|published|trash),
title, slug, content (HTML), excerpt, cover, cover_alt, categories[] (a primeira
é a principal), tags[], author, published_at, created_at, updated_at, seo_title,
seo_description, seo_keyword, seo_noindex, template` (modelo do Conteúdo do site;
`''` = página comum), `fields` (valores do modelo) e, quando importado, `wp_id`.
"Agendado" = `published` com `published_at` no futuro.

## Padrões de código

```php
// Rota (app/routes/site.php). Devolver false = 404.
$router->get('/arquivo/{ano:\d{4}}', fn($p) => Site::year((int) $p['ano']));

// Handler público (app/Site.php)
public static function year(int $year)
{
    $posts = array_filter(Posts::query(['type' => 'post', 'public' => true]),
        fn($p) => (int) date('Y', $p['ts']) === $year);
    if (!$posts) {
        return false;
    }
    theme('archive', ['label' => 'Ano', 'name' => (string) $year, 'posts' => array_values($posts), ...]);
    return true;
}

// Handler do painel (app/Admin.php)
public static function algo(): void
{
    Auth::requireAdmin();
    if (is_post()) {
        verify_csrf();
        // ... gravar ...
        flash('success', 'Salvo.');
        redirect('/admin/algo');
    }
    admin_view('algo', ['title' => 'Algo', 'active' => 'algo']);
}
```

Helpers mais usados: `e()`, `url()`, `absolute_url()`, `post_url()`, `post_path()`,
`term_url()`, `setting()`, `slugify()`, `format_date()`, `excerpt_from()`,
`input()`, `get_param()`, `redirect()`, `json_response()`, `flash()`,
`theme()`, `partial()`, `admin_view()`, `paginate()`.

## Testar

```bash
php -S localhost:8080 -t public public/index.php
```

- Com o servidor embutido, os erros aparecem na tela (`debug` liga sozinho).
- Para testar sem mexer nos dados reais: `BLOG_DATA_DIR=/tmp/blog-teste php -S ...`
- Verifique a sintaxe: `php -l arquivo.php`.
- Depois de mexer em `Posts::indexItem()` ou na busca, rode `php tools/reindex.php`.
- Conteúdo inicial de um tema (`content/`): `php tools/site-content.php` cria as páginas que
  faltam (`--update` sobrescreve, `--export` grava o que está no site de volta em `content/`).

## Origem desta cópia

O código do núcleo (`app/`, `theme/`, `tools/`, `public/index.php`, `public/assets/` fora de `ht/`,
`themes/README.md`) é o de `github.com/fabianokunst/blog-system` (`main` @ `55e11bb`, 10/11/2026),
sem mudanças. Não faça commit nem push para o repositório do Fabiano a partir daqui. Para
atualizar, copie de novo esses arquivos do `main`: o que é da HT fica fora deles.

Exceção: `themes/.htaccess`. O original nega tudo ("Require all denied"); com o domínio apontando
para esta pasta (Hostinger), isso bloqueava também a rota `/themes/{tema}/assets/...` (403 no CSS,
fontes e logos), porque o servidor confere o acesso da pasta antes do `.htaccess` da raiz. O nosso
manda todo pedido de `/themes/` para `public/index.php`. **Ao atualizar o núcleo, não sobrescreva
este arquivo** (a não ser que o blog-system já traga a correção).

## Site da HT Solutions

Este blog-system é o site inteiro de htsolutions.com.br: páginas institucionais na raiz, posts
em `/{slug}/` e a lista de posts em `/blog/`. Tudo o que é da HT está no tema `themes/ht/`
(ativo em `setting('theme')`), usando o Conteúdo do site do núcleo.

| O quê | Onde |
|---|---|
| Campos de cada página do site (11 modelos) | `themes/ht/fields/{modelo}.php`. Chaves em português; botões com `ht_campo_botao()` (`texto` + `link`) |
| HTML de cada página | `themes/ht/templates/{modelo}.php` (recebe `$fields`); `themes/ht/template.php` põe a página em `<div class="pg pg-{modelo}">` |
| Funções `ht_*` dos templates e dos modelos | `themes/ht/functions.php` |
| Menu do topo, contatos, rodapé, parceiros | modelo `geral` (`shared`): página em rascunho `site-geral`, lida por `ht_geral()` |
| Formulários da Emarsys e qual página usa qual | `themes/ht/ht.php` |
| Topo, barra do blog (só fora das páginas do site) e rodapé | `themes/ht/layout.php` |
| Fonte, cores, topo e rodapé, sem modo escuro | `themes/ht/assets/ht.css`, carregado depois do `site.css` |
| Seções e componentes das páginas do site | `themes/ht/assets/paginas.css` (só nas páginas com modelo) |
| Menu do celular, formulário de contato | `themes/ht/assets/ht.js` |
| Vídeos, newsletter, consulta de garantias | `themes/ht/assets/paginas.js` |
| Favicon, imagem para redes sociais, logo do Google | `public/assets/ht/` (usados nas Configurações, ficam fora do tema) |
| Imagens das páginas | `public/uploads/site/` |
| Conteúdo inicial das páginas (versionado) | `themes/ht/content/*.json` |
| Instalação (páginas, `/blog/`, início, redirecionamentos do site antigo) | `php themes/ht/instalar.php` (`--atualizar`, `--exportar`) |
| Configurações da HT na primeira instalação | `themes/ht/settings.json` (depois valem as do painel, em `data/settings.json`, fora do Git) |
| Publicação na Hostinger | branch `producao` = pasta `site/` na raiz, atualizada por `.github/workflows/producao.yml` da raiz do repositório. `data/` e uploads novos ficam fora do Git (`.gitignore` desta pasta) |

- **Consulta de garantias**: a base é `public/assets/db_943hf3.bin` (fora do Git, tem nomes de
  clientes), decifrada no navegador por `paginas.js` como no site antigo. A senha está no
  JavaScript: qualquer um consegue ler a base.
- O modelo **Página de apresentação** (`theme/fields/landing.php`, do núcleo) também aparece em
  Conteúdo do site, para landing pages. Já sai com o topo, o rodapé, a fonte e o azul da HT; as
  seções têm o desenho simples do tema padrão (CSS próprio no tema deixaria igual às outras páginas).
- Os temas de exemplo do blog-system (`themes/holofote/` etc.) ficaram de fora de propósito:
  ativar outro tema tira o visual da HT (o conteúdo não se perde).
