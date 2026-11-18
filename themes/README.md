# Temas

Um tema muda o visual do site e pode trazer coisas novas: blocos na página
inicial, banners, chamadas no fim dos posts, páginas novas, opções próprias no
painel. Ele **não mexe no conteúdo**: posts, páginas, menu, SEO e Configurações
são os mesmos em qualquer tema, e trocar de tema não apaga nada.

- O tema **Padrão** é a pasta `theme/` (o visual "cru" do blog). Ele está sempre lá.
- Cada tema instalado é uma pasta aqui, em `themes/{nome}/`, e **só traz o que
  muda**: o arquivo que o tema não tiver vem do Padrão.
- O dono escolhe, pré-visualiza, personaliza, baixa e envia temas em
  **Painel → Temas**. O código está em `app/Themes.php`.

## Os temas que vêm com o blog

| Tema | Cabeçalho | Tela inicial | O que mais traz |
|---|---|---|---|
| **Holofote** (campanhas e promoções) | Fixo no topo ao rolar, menu em maiúsculas, lupa que abre a busca na largura toda, botão "Loja online", menu atrás de um botão no celular | Banner em 3 estilos (foto de fundo, lado a lado, caixa sobre a foto) com 2 botões, faixa de vantagens com ícones, atalhos das categorias em círculos (como stories) ou cartões, primeiro post em destaque, faixa de chamada | Aviso no topo com contagem regressiva, chamada para a loja no fim dos posts |
| **Passarela** (revista de varejo) | De jornal: data do dia, logo no centro, menu entre fios; claro, escuro ou na cor de destaque; menu atrás de um botão no celular | Carrossel com a lista numerada "Não perca" ao lado, até 3 banners de campanha, prateleiras por categoria que rolam para o lado | Faixa "Últimas" com os títulos passando, mapa do site no rodapé escuro, posts com capa larga e letra capitular |
| **Bússola** (central de dicas) | Logo com selo, busca no centro, menu em abas (que rolam no celular) | Busca grande com assuntos populares e atalho `/`, guias numerados, assuntos com ícone colorido, perguntas frequentes | Perguntas no Google (FAQPage), sumário no começo e fixo ao lado do texto marcando a parte lida, barra de progresso, "Atualizado em", voltar ao topo |
| **Vizinhança** (loja física) | Barra com "Aberto agora · fecha às 19h", endereço, telefone e WhatsApp; menu em pílulas; botão "Como chegar" | Foto da loja com o status, endereço/horário/contato, serviços, depoimentos, mapa e lojas | Página `/nossas-lojas/`, mapa que só carrega quando o visitante pede (sem cookies antes disso), WhatsApp flutuante e, no celular, barra fixa com Ligar, WhatsApp e Como chegar |

Eles também servem de exemplo: cada um usa um recurso diferente do sistema de
temas (veja a última coluna da tabela de [recursos](#o-que-um-tema-pode-ter)).

## Levar um tema para outro site

1. No site de origem, **Painel → Temas → Baixar .zip** no tema.
   O .zip leva a pasta do tema, as opções preenchidas em Personalizar
   (`options.json`) e as imagens usadas nessas opções (em `assets/media/`).
2. No outro site, **Painel → Temas → Enviar tema** com esse .zip.

Se o tema ainda não existe no outro site, ele chega com as mesmas opções. Se já
existe, os arquivos são atualizados e **as opções daquele site continuam**.

Também dá para copiar a pasta `themes/{nome}/` por FTP. As opções ficam em
`data/theme-options.json` e o tema ativo em `data/settings.json` (`theme`);
o backup do painel leva os dois e a pasta `themes/`.

## Criar um tema

O caminho mais curto: copie a pasta de um tema parecido, troque o nome da pasta
(vira o identificador: letras minúsculas, números e `-`) e o `name` no
`theme.json`, e edite. Para testar sem mexer nos dados reais:

```bash
BLOG_DATA_DIR=/tmp/blog-teste php -S localhost:8080 -t public public/index.php
```

Para instalar em outro site, compacte **a pasta** (ex.: botão direito →
Enviar para → Pasta compactada) e envie em Painel → Temas.

### O que um tema pode ter

```
themes/meu-tema/
  theme.json            nome, descrição e opções (o único obrigatório)
  functions.php         funções, classes no <body> e páginas novas   → todos (rotas: Vizinhança)
  home.php, post.php…   substituem os templates de theme/            → todos (home.php)
  partials/card.php     substitui só um pedaço                        → Bússola
  slots/{lugar}.php     acrescenta um bloco num lugar do site         → todos
  assets/style.css      CSS, carregado depois do site.css             → todos
  assets/script.js      JavaScript, carregado com defer               → Passarela, Bússola
  assets/screenshot.svg miniatura no painel (png, jpg, webp ou svg)
  assets/…              imagens, fontes etc.: theme_asset('img/x.png')
  fields/{nome}.php     campos de uma página editável pelo negócio    → veja "Páginas com campos"
  templates/{nome}.php  HTML dessa página
  content/              conteúdo inicial dessas páginas (opcional)
```

### theme.json

```json
{
    "name": "Meu Tema",
    "description": "Uma frase sobre o que ele muda.",
    "version": "1.0.0",
    "author": "Loja X",
    "options": {
        "banner_image": { "group": "Banner", "type": "image", "label": "Imagem", "hint": "Texto de ajuda." },
        "banner_title": { "group": "Banner", "type": "text", "label": "Título", "default": "Ofertas" },
        "columns": { "group": "Lista", "type": "select", "label": "Colunas", "choices": { "2": "Duas", "3": "Três" }, "default": "3" }
    }
}
```

Cada opção vira um campo em **Temas → Personalizar**, agrupado por `group`
(uma seção por grupo), com prévia ao vivo. Campos de uma opção: `type`, `label`,
`hint`, `placeholder`, `default`, `group`, `choices` (select), `min`/`max` (number).
A chave (`banner_title`) usa letras minúsculas, números e `_`.

| type | Campo no painel | Valor em `theme_option()` |
|---|---|---|
| `text` | uma linha | texto (imprima com `e()`) |
| `textarea` | várias linhas | texto (`nl2br(e(...))`) |
| `html` | código HTML (só o admin edita) | HTML, imprima direto |
| `url` | endereço (`https://`, `/caminho/`, `mailto:`, `tel:`) | texto (`e()`) |
| `image` | escolher da Mídia | endereço da imagem; use `image_tag()` |
| `color` | seletor de cor | `#rrggbb` |
| `checkbox` | caixa de marcar | `true`/`false` |
| `select` | lista com `choices` | a chave escolhida |
| `number` | número entre `min` e `max` | inteiro |
| `category` | lista das categorias do blog | nome da categoria (`''` = nenhuma); filtre com `Posts::query(['category' => slugify($nome)])` |
| `datetime` | data e hora | `2026-11-30T23:59` (no fuso do blog); `strtotime()` dá o momento |

Padrão de imagem que vem dentro do tema: `"default": "assets/banner.jpg"`.

### Templates e slots

`theme('home', ...)` e `partial('card', ...)` procuram primeiro no tema ativo e
depois em `theme/`. Para trocar a página inicial, por exemplo, crie `home.php`.
Para usar o template do Padrão de dentro do seu (como fazem os temas daqui nas
páginas 2, 3... da lista):

```php
<?php if ($pagination['page'] > 1) { theme_parent('home', get_defined_vars()); return; } ?>
```

Para **acrescentar** algo sem copiar o template inteiro, crie um arquivo em
`slots/`. Ele recebe as mesmas variáveis do template onde está o lugar.

| Slot (`slots/{nome}.php`) | Onde aparece |
|---|---|
| `head` | dentro do `<head>`, depois do CSS (ex.: `<style>` com uma opção de cor) |
| `header-before` | antes do cabeçalho, em todas as páginas (ex.: aviso no topo) |
| `header-actions` | dentro do cabeçalho, depois da busca e do botão claro/escuro (ex.: botão "Loja online"); com CSS grid, o tema pode pôr o bloco em outro lugar da linha |
| `header-after` | logo depois do cabeçalho, em todas as páginas |
| `home-top` | topo da página inicial, só na primeira página (também quando o início é uma página fixa) |
| `home-bottom` | fim da página inicial, só na primeira página |
| `post-before-content` | no post, antes do texto |
| `post-after-content` | no post, logo depois do texto |
| `post-bottom` | no post, depois do "Leia também" e dos links de anterior/próximo |
| `page-after-content` | nas páginas fixas, depois do texto |
| `footer-before` | antes do rodapé, em todas as páginas |
| `body-end` | antes do `</body>` (ex.: botão flutuante) |

Um template que substitui um do Padrão deve manter os `slot()` dele, se quiser
que temas filhos e blocos continuem funcionando ali.

### functions.php

Carregado uma vez por página, só quando o tema está ativo. Pode declarar funções
(use o nome do tema como prefixo: `meutema_lojas()`) e devolve um array:

```php
return [
    // Classes extras no <body>, para o CSS do tema seguir as opções (recebe as variáveis da página).
    'body_class' => fn(array $vars) => [theme_option('header_style') === 'dark' ? 'meutema-escuro' : ''],

    // Páginas novas. Devolver false = "não é comigo" (o endereço segue para posts, páginas ou 404).
    'routes' => function (Router $router): void {
        $router->get('/nossas-lojas', function () {
            theme('stores', ['meta' => ['title' => Seo::title('Nossas lojas'), 'canonical' => absolute_url('/nossas-lojas/')]]);
            return true;
        });
    },
];
```

As rotas do tema ficam antes da rota genérica `/{slug}`: um endereço do tema vale
mais que um post com o mesmo nome. Escolha endereços que não existam no blog.

### Funções úteis nos templates

| Função | Para quê |
|---|---|
| `theme_option('chave')` / `Themes::options()` | opções do tema (uma ou todas) |
| `theme_asset('img/x.svg')` | endereço de um arquivo de `assets/` ('' se não existir) |
| `slot('nome', get_defined_vars())` | marcar um lugar para blocos |
| `theme_parent('home', get_defined_vars())` | mostrar o template do Padrão |
| `Posts::query([...])`, `Posts::terms('categories')` | buscar posts e categorias (veja `app/Posts.php`) |
| `partial('card', ['post' => $p])`, `image_tag()`, `post_url()`, `term_url()`, `e()` | as mesmas do tema Padrão |
| `SiteContent::pagesOf('modelo')`, `SiteContent::shared('modelo')` | páginas de um modelo repetido e o conteúdo comum a todas as páginas (veja [Páginas com campos](#páginas-com-campos-conteúdo-do-site)) |

## Páginas com campos (Conteúdo do site)

Para sites institucionais e landing pages: quem desenvolve monta a página uma vez
(campos e HTML) e o time de negócio troca textos, imagens, links e itens das listas
num formulário em **Painel → Conteúdo do site**, sem conseguir quebrar o layout.
Cada página continua sendo uma página do blog: tem endereço, SEO, sitemap, busca,
lixeira e versões ("Restaurar" no próprio formulário).

O tema Padrão já traz um modelo, **Página de apresentação** (`theme/fields/landing.php`
e `theme/templates/landing.php`), que vale para todos os temas e serve de exemplo.

### Criar uma página nova (passo a passo)

Ex.: uma página "Serviços" com abertura, lista de serviços e chamada no fim.

1. **Campos**: `themes/meu-tema/fields/servicos.php`

   ```php
   <?php
   return [
       'label' => 'Serviços',              // nome no painel
       'description' => 'Texto curto acima do formulário (opcional).',
       'order' => 3,                       // posição na lista do painel
       'fields' => [
           'intro' => Field::textarea('Texto de abertura', 'O título é o título da página.'),
           'foto' => Field::image('Foto de fundo'),
           'servicos' => Field::list('Serviços', 'Serviço', [
               'nome' => Field::text('Nome'),
               'texto' => Field::html('Descrição'),
               'icone' => Field::image('Ícone'),
           ], 'nome'),                     // 'nome' aparece no item fechado
           'chamada' => Field::group('Chamada no fim', [
               'titulo' => Field::heading('Título'),
               'botao' => Field::button(),
           ]),
       ],
   ];
   ```

2. **HTML**: `themes/meu-tema/templates/servicos.php`. Ele entra no lugar de `page.php`
   (dentro do `layout.php`) e recebe `$post` (a página) e `$fields`, os valores, com
   **todas as chaves** (vazias quando ninguém preencheu):

   ```php
   <section class="servicos">
     <h1><?= e($post['title']) ?></h1>
     <?php if ($fields['intro'] !== ''): ?><p><?= nl2br(e($fields['intro'])) ?></p><?php endif ?>
     <?php foreach ($fields['servicos'] as $s): ?>
       <article>
         <?php if ($s['icone'] !== ''): ?><?= image_tag($s['icone'], '64px', ['alt' => '']) ?><?php endif ?>
         <h2><?= e($s['nome']) ?></h2>
         <?= $s['texto'] ?>
       </article>
     <?php endforeach ?>
   </section>
   ```

3. **CSS** em `assets/style.css` do tema, com as variáveis de Configurações (`--accent`,
   `--surface`, `--radius`...), para respeitar a cor do site e o modo escuro.

4. **Conteúdo inicial** (opcional): `themes/meu-tema/content/servicos.json`

   ```json
   { "title": "Serviços", "slug": "servicos", "seo_description": "...", "fields": { "intro": "..." } }
   ```

   Com o tema ativo, o admin clica em **Criar as páginas** em Conteúdo do site (ou roda
   `php tools/site-content.php`). Sem o arquivo, a página é criada pelo botão **Criar**
   da lista, em rascunho. `"home": true` no JSON faz dela a página inicial do site.

A página aparece em Conteúdo do site com o formulário montado a partir dos campos.
Para uma página que já existe (com endereço e SEO), o admin escolhe o modelo no editor
de páginas, na caixa **Modelo**.

### Opções do modelo

| Chave | Para quê |
|---|---|
| `label` | nome no painel (obrigatório, junto com `fields`) |
| `description` | texto acima do formulário |
| `order` | ordem na lista do painel (padrão 50) |
| `repeatable` | `true` = várias páginas com o mesmo modelo (uma por marca, unidade ou campanha). Aparece o botão **+ {label}** e o formulário ganha endereço e situação (publicada ou rascunho). Para listar essas páginas em outra: `SiteContent::pagesOf('marca')` devolve as publicadas, em ordem alfabética, com os campos completos |
| `shared` | `true` = conteúdo de todas as páginas (menu, contatos, rodapé). Fica numa página em rascunho, fora do Google. Leia no `layout.php` ou num slot com `SiteContent::shared('nome')` |

### Tipos de campo

| Método | No formulário | No template |
|---|---|---|
| `Field::text('Rótulo', 'dica')` | uma linha | texto: `e()` |
| `Field::textarea(...)` | várias linhas | texto: `nl2br(e())` |
| `Field::heading(...)` | uma linha que aceita `<strong>`, `<em>` e `<br>` (títulos com destaque) | HTML já limpo: imprima direto |
| `Field::html(...)` | editor visual do painel | HTML já limpo (`Html::clean`): imprima direto |
| `Field::image(...)` | escolher da Mídia | endereço: `image_tag($v, $sizes, [...])` |
| `Field::file(...)` | qualquer arquivo da Mídia (PDF...) | endereço: `e()` |
| `Field::link(...)` | `/pagina/`, `https://...`, `mailto:`, `tel:`, `#ancora` | endereço, já com a pasta do blog: `e()` |
| `Field::select('Rótulo', ['a' => 'Opção A'])` | lista de opções | a chave (sem escolha, a primeira) |
| `Field::post(...)` | um post publicado | o slug: `Posts::findBySlug($v)` |
| `Field::group('Rótulo', [campos])` | campos juntos numa caixa | array com os campos |
| `Field::list('Rótulo', 'Item', [campos], 'resumo')` | itens repetidos: adicionar, mover, remover (até 100) | lista de arrays |
| `Field::button('Rótulo')` | grupo com texto e link | `['text' => ..., 'link' => ...]` |

Ao salvar, cada valor é conferido pelo tipo: link inválido vira `''`, HTML perigoso sai e
chave que não está no modelo some. Por isso, no template, só `html` e `heading` vão sem `e()`.

### Regras dos modelos

- **Campo novo** num modelo é seguro: as páginas antigas recebem o campo vazio.
  **Renomear ou apagar** uma chave perde o que estava nela.
- Chaves dos campos: letras minúsculas, números e `_`. Nome do modelo (o arquivo): letras
  minúsculas, números e `-`.
- O título da página (o `<h1>`) é o título do post, editado no próprio formulário: não crie
  um campo para ele.
- Não declare funções em `fields/`: ponha em `functions.php`.
- Os valores ficam no post (`data/posts/`), não no tema: trocar de tema não apaga nada. Sem o
  modelo no tema ativo, a página aparece como página comum até ele voltar.
- `php tools/site-content.php --export` grava em `content/` o que está nas páginas, para levar
  o conteúdo junto com o tema (o .zip do tema leva `fields/`, `templates/` e `content/`).

## Regras

- Imprima dados com `e()`. A exceção são opções do tipo `html`.
- Use as variáveis de cor e estilo de Configurações (`--accent`, `--on-accent`,
  `--bg`, `--surface`, `--text`, `--muted`, `--line`, `--radius`, `--space`...):
  assim o tema respeita a cor da loja e o modo escuro. O CSS do tema vem antes
  do `<style>` de Configurações, que continua valendo.
- Toda página precisa de um `<h1>` (os temas daqui usam o nome do blog, escondido).
- Não grave nada em `data/` a partir do tema: o conteúdo é do blog, não do tema.
- Arquivos aceitos no .zip: php, json, css, js, mjs, map, png, jpg, jpeg, gif,
  webp, avif, svg, ico, woff, woff2, ttf, otf, mp4, webm, md, txt. Até 1000
  arquivos e 50 MB. Arquivos ocultos (como `.htaccess`) ficam de fora.
- Um tema tem PHP e roda com as mesmas permissões do blog: instale só temas de
  quem você confia.
