# HT Solutions: análise para site estático

Levantamento de [htsolutions.com.br](https://htsolutions.com.br) feito em 29/10/2026, para refazer o site em Astro no mesmo padrão de `hlar/` e `hquimica/`.

## Como o site funciona hoje

| Camada | O que é |
|---|---|
| Front-end | Angular 17.3 com SSR (servidor Express, `X-Powered-By: Express`), um módulo lazy por rota |
| Conteúdo | Strapi v4 em `https://ht-strapi.azurewebsites.net/api` (formato `data.attributes`) |
| Mídia | Azure Blob: `https://htsolutions.blob.core.windows.net/uploads/uploads/...` (cerca de 600 URLs, contando os formatos thumbnail/small/medium/large) |
| Imagens fixas | `assets/images/certigov/*` e `assets/images/hp-lp/*`, dentro do próprio build Angular |
| Analytics | Google Tag Manager `GTM-TJ2Q8TFS` |
| Captcha | reCAPTCHA v2, site key `6LenPUElAAAAANU6Nj92YhNeO94wGEuczpi_f5rF` |
| Fontes | Asap (Google Fonts, inline pelo critters), mais pedidos a "Museo" e "Mundial" no Google Fonts, que não existem lá |

A API do Strapi responde sem autenticação, então dá para exportar todo o conteúdo por script. O retrato de hoje está em `dados/strapi-2026-10-29/` (um JSON por tipo; `blogs.json` + `blogs2.json` são as duas páginas de posts).

## Páginas

| Rota | Origem do conteúdo | Observações |
|---|---|---|
| `/` | Strapi `home` (banner, soluções, quem somos com vídeo, parceiros, cases, blog/newsletter) | Vídeo `HT_A_Historia.mp4` no blob |
| `/quem-somos` | Strapi `quem-somos` (texto, vídeo, premiações) | |
| `/servicos` | Strapi `servico` (Google, client, enterprise, abrir chamado) | Âncora `#servicos` usada no menu |
| `/atas` | Strapi `pagina-ata` | O botão "Adquira aqui" leva a `atas.htsolutions.com.br`, outro sistema |
| `/blog` | Strapi `pagina-blog` + `blogs` | Destaques, recentes, por categoria |
| `/blog/:slug` | Strapi `blogs` | 117 posts, de 2021-04 a 2026-08 |
| `/blog/categoria/:slug` | Strapi `categorias` | 7 categorias: cases, educacao, escritorios-inteligentes, institucional, novidades, protecao-de-dados, transformacao-digital |
| `/googleparaempresas` | Strapi `google-empresa` | Layout próprio, sem o rodapé padrão |
| `/educacao` | Strapi `google-for-education` | Playlist do YouTube; layout próprio, sem o rodapé padrão |
| `/hp` | Fixo no código | Só carrega o script de vitrine da HP (`syndication.inc.hp.com`) |
| `/campanha-hp` | Fixo no código | Landing page com formulário (nome, WhatsApp, e-mail, reCAPTCHA) |
| `/certigov` | Fixo no código + `quem-somos` | Canais de transparência; e-mail do diretor e Contato Seguro |
| `/consulta-garantias` | Arquivo `assets/db_943hf3.bin` (50 KB) | Consulta por número de série, decifrada no navegador (ver abaixo) |
| `/politica-de-privacidade` | Strapi `politica-de-privacidade` | |
| `/termos-de-uso` | Strapi `termos-de-uso` | A API devolve 404: a página está no ar sem conteúdo |
| `**` | 404 | Responde com HTTP 200 |

Fora do escopo (são outros sistemas e continuam como links): `produtos.htsolutions.com.br`, `atas.htsolutions.com.br`, `htsolutions.gupy.io` (vagas).

## Formulários

| Formulário | Destino hoje | No estático |
|---|---|---|
| Contato (home, serviços, atas, enterprise, educação) | GET para `https://link.htsolutions.com.br/u/register.php` (Emarsys, campos `CID`, `f`, `inp_*`) | Funciona igual: é um `<form>` HTML comum apontando para fora |
| Educação (extra) | POST JSON para `https://a.newshtsolutions.com.br/pub/rf` | Funciona por `fetch`, se o endpoint aceitar CORS do novo domínio |
| Contato Strapi | POST `/api/form-contatoes` | Precisa de destino novo se o Strapi for desligado |
| Newsletter | POST `/api/form-newsletters` | Idem |
| Leads da `/campanha-hp` | POST `/api/landing-page-leads` | Idem |

## Problemas encontrados no site atual

- **SEO**: todas as páginas têm o mesmo `<title>` ("HT Solutions") e a mesma descrição, inclusive os posts.
- **Sem `sitemap.xml` e `robots.txt`**: os dois endereços devolvem a página 404 do Angular com HTTP 200.
- **404 com status 200**: o Google indexa páginas inexistentes.
- **Imagens enormes**: a capa do post mais recente tem 7373 × 4147 px. O build do Astro resolve gerando WebP/AVIF nos tamanhos certos.
- **Imagens quebradas**: dois posts apontam para um IP interno (`http://172.28.0.11:1337`): `dell-optiplex-7090-excelente-desempenho-em-pouco-espaco` e `2021-um-ano-de-sucesso-para-a-ht`.
- **HTML sujo nos posts**: textos colados do ChatGPT trazem atributos `data-start`/`data-end` e classes como `PDq2pG_selectionAnchorContainer`. Dá para limpar na importação.
- **Slugs genéricos**: `blog`, `blog-1`, `blog-3`, `blog-4`, `blog-5`. Manter para não quebrar links, mas os novos posts deveriam ter slug pelo título.
- **Consulta de garantias**: a base é cifrada, mas a senha está no JavaScript público, então qualquer um consegue ler a base inteira. Não muda nada no estático (é o mesmo modelo), mas vale saber que não há proteção real.
- **Fontes**: Museo e Mundial são pedidas ao Google Fonts, que não as tem; o navegador cai na fonte padrão.
- **Logo do `/certigov`** vem de `www.htsolutions.com.br/wp-content/uploads/...`, resto do WordPress antigo.

## Arquitetura escolhida: páginas estáticas + blog-system em `/blog/`

Não há acesso ao painel do Strapi, então o Strapi sai. As páginas institucionais viram HTML estático (Astro, mesmo padrão de `hquimica/`), e o blog passa para o [blog-system](blog/README.md) (PHP sem banco), instalado na pasta `/blog/` do mesmo domínio.

```
htsolutions.com.br/            páginas estáticas (Astro → dist/)
htsolutions.com.br/blog/       blog-system (PHP), com painel em /blog/admin
```

- **Hospedagem**: o blog-system precisa de PHP 8.1+, então o conjunto não vai para o GitHub Pages. Serve qualquer Apache com PHP (hospedagem compartilhada, cPanel): o `dist/` do Astro na raiz e a pasta `blog/` dentro dele.
- **Endereços**: com o blog em `/blog/`, `/blog/{slug}` continua funcionando (redireciona para `/blog/{slug}/`, o formato do blog-system). `/blog/categoria/{slug}` redireciona para `/blog/category/{slug}/`.
- **Slug com maiúsculas**: o post `blog-Tecnologias-que-estao-moldando-o-futuro` virou `blog-tecnologias-...` no blog-system. O `.htaccess` do site redireciona o endereço antigo.
- **Home institucional**: os blocos "blog" e "cases" leem os últimos posts no navegador, de `/blog/ultimos.json`.

## O que já está feito

- `blog/`: cópia do blog-system baixada de `github.com/fabianokunst/blog-system` em 29/10/2026, **só o código**. Ficaram de fora o `.git`, os hooks do `.claude/` (que fazem commit e push automático para o repositório do Fabiano) e os dados do blog da taQi que estão no repositório (posts, usuários, uploads). O `run.bat` é o da `nova intranet`, que liga as extensões do PHP do winget.
- `blog/extensions/strapi-import/`: extensão que importa os posts do Strapi, a partir da cópia em `dados/` ou da API. Detalhes no [README dela](blog/extensions/strapi-import/README.md).
- `blog/public/uploads/strapi/`: as 147 imagens e vídeos dos posts, já baixados do Azure (52 MB, imagens reduzidas a 2000 px de largura).
- Teste completo numa pasta de dados temporária: 117 posts, todos com capa, texto limpo, vídeos do YouTube e `<video>` funcionando, categoria redirecionando e 404 de verdade. Rodar de novo atualiza os mesmos posts.
- Perdidas de vez: as 2 imagens que apontavam para `172.28.0.11` (não existem no Azure). Saíram do texto.

**Falta para os posts aparecerem no blog de verdade**: rodar `blog/run.bat`, criar a conta de administrador em `/admin` (no campo do nome do blog, digite **Blog HT Solutions**: se ficar vazio, ele grava "Meu Blog" por cima da configuração do tema) e depois rodar `php extensions/strapi-import/cli.php ../dados/strapi-2026-10-29` (comando completo no README da extensão). As imagens já estão baixadas, então leva segundos.

## Páginas estáticas (feito)

O site institucional está em [site/](site/README.md), em Astro, no padrão do `hquimica`. São 11 páginas com os mesmos endereços do Angular. Os textos e imagens foram convertidos do Strapi para `site/src/data/*.json`. O build passa sem erros e sem links internos quebrados, e as páginas foram conferidas no navegador, no desktop e no celular.

- **Formulários**: contato e newsletter iam para a Emarsys, não para o Strapi, então nada muda. O único que gravava no Strapi era o da `/campanha-hp`, uma promoção encerrada que agora redireciona para `/hp/`.
- **Últimos posts e cases** na home e na página Google: vêm do blog em tempo real, por `/blog/ultimos.json` (extensão `blog/extensions/ultimos-posts/`).
- **Consulta de garantias**: funciona igual ao site antigo, decifrando a base no navegador. Testada.
- O README do site lista o que mudou e as pendências de conteúdo (política de privacidade com lei portuguesa, termos de uso, vídeos no Azure, logo em baixa resolução).

## Decisões pendentes

- **Hospedagem com PHP** para o conjunto (onde roda hoje o Express não serve).
- **Tema do blog (feito)**: o blog usa o topo, o rodapé, as cores e a fonte Asap do site, com uma barra própria abaixo do topo (nome do blog, menu de Configurações → Menu e busca). Formulário de contato da Emarsys no rodapé, sem modo escuro. Detalhes na seção "Tema da HT Solutions" de `blog/CLAUDE.md`.
- **Termos de uso**: a página existe sem conteúdo. Pedir o texto ou tirar do ar.
- **`/hp` e `/campanha-hp`**: confirmar se a campanha ainda está ativa antes de migrar.
- **Mídia**: baixar tudo do blob para o repositório (site independente do Azure) ou continuar apontando para o blob.
