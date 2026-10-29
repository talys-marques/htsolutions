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
| Nova tela no painel | rota em `app/routes/admin.php` → método em `app/Admin.php` → template em `app/views/admin/` → link no menu em `app/views/admin/layout.php` |
| Novo campo no post | `Posts::blank()` (valor padrão) → formulário em `app/views/admin/edit.php` → leitura em `Admin::savePost()` → exibição em `theme/post.php`. Se precisar aparecer em listagens, inclua também em `Posts::indexItem()` |
| Nova configuração | `Settings::defaults()` → `app/views/admin/settings.php` → `Admin::settingsInput()` → use com `setting('chave')` |
| Página fixa no início e página dos posts (como no WordPress) | escolha em Configurações → Página inicial (`home_page`, `blog_page`), lida por `Posts::homePage()` / `Posts::blogPage()`. Início em `Site::home()`, lista de posts em `Site::blog()`. Links de posts usam `post_url()` / `post_path()`, que já devolvem `/` para a página inicial. No menu, item `blog` de `Menu` |
| Nova estrutura da página inicial | `Settings::homeLayouts()` → `theme/home.php` + `public/assets/site.css` → miniatura em `$shapes` de `app/views/admin/settings.php`. A prévia em Configurações (`Admin::settingsPreview()`) já mostra a nova sem mudanças |
| Nova rede social no rodapé | `Social::networks()` (endereço e ícone SVG) |
| Novo tipo de item no menu do topo | `Menu::TYPES` → `normalize()`, `resolve()`, `describe()` e `search()` em `app/Menu.php` (formato no comentário da classe) → aba do seletor em `app/views/admin/settings.php` |
| Mudar o visual do site | `theme/*.php` e `public/assets/site.css` (modo claro/escuro: `public/assets/site.js`) |
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
data/revisions/{id}/      versões anteriores de cada post
data/sessions/, cache/, logs/, import/
```

Campos de um post: `id, type (post|page), status (draft|published|trash),
title, slug, content (HTML), excerpt, cover, cover_alt, categories[] (a primeira
é a principal), tags[], author, published_at, created_at, updated_at, seo_title,
seo_description, seo_keyword, seo_noindex` e, quando importado, `wp_id`.
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

## Tema da HT Solutions

Este blog roda em `/blog/` de htsolutions.com.br, ao lado do site institucional (`../site/`, Astro).
O tema copia o topo e o rodapé do site:

| O quê | Onde |
|---|---|
| Menu do topo, contatos, links do rodapé e formulário da Emarsys | `theme/ht.php` (mantenha igual a `../site/src/config.mjs`) |
| Topo, barra do blog (menu do painel e busca) e rodapé | `theme/layout.php` |
| Fonte Plus Jakarta Sans, cores, estilos do topo e rodapé, sem modo escuro | `public/assets/ht/ht.css`, carregado depois do `site.css` |
| Menu do celular e confirmação do formulário | `public/assets/ht/ht.js` |
| Logos, favicon, imagem de compartilhamento e fontes | `public/assets/ht/` |
| Configurações iniciais (nome, cor, grade, menu do blog, GTM) | `data/settings.json` |

Os links do site são caminhos a partir da raiz (`/servicos/`), sem `url()`, porque o site
não fica dentro da pasta do blog. A cor e a fonte escolhidas no painel são ignoradas: o
`ht.css` fixa as da HT.
