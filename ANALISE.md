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
- **Imagens enormes**: a capa do post mais recente tem 7373 × 4147 px. O blog-system resolve: reduz no envio e gera versões menores em WebP.
- **Imagens quebradas**: dois posts apontam para um IP interno (`http://172.28.0.11:1337`): `dell-optiplex-7090-excelente-desempenho-em-pouco-espaco` e `2021-um-ano-de-sucesso-para-a-ht`.
- **HTML sujo nos posts**: textos colados do ChatGPT trazem atributos `data-start`/`data-end` e classes como `PDq2pG_selectionAnchorContainer`. Dá para limpar na importação.
- **Slugs genéricos**: `blog`, `blog-1`, `blog-3`, `blog-4`, `blog-5`. Manter para não quebrar links, mas os novos posts deveriam ter slug pelo título.
- **Consulta de garantias**: a base é cifrada, mas a senha está no JavaScript público, então qualquer um consegue ler a base inteira. Não muda nada no estático (é o mesmo modelo), mas vale saber que não há proteção real.
- **Fontes**: Museo e Mundial são pedidas ao Google Fonts, que não as tem; o navegador cai na fonte padrão.
- **Logo do `/certigov`** vem de `www.htsolutions.com.br/wp-content/uploads/...`, resto do WordPress antigo.

## Arquitetura escolhida: o site inteiro no blog-system

Primeiro o site foi refeito em Astro (HTML estático) com o blog-system em `/blog/`. Na apresentação, o negócio pediu para continuar editando textos, menus e imagens sozinho, como fazia no Strapi. Por isso o site inteiro passou para o blog-system (PHP, sem banco), com um painel de **Conteúdo do site**: um formulário de campos fixos por página.

- **Um painel só**, em `/admin`, para o site e o blog, com usuários e papéis. O papel "autor" edita conteúdo, mas não mexe em configurações técnicas.
- **Estrutura protegida**: dá para trocar textos, imagens e links, e acrescentar, mover ou tirar itens das listas. A montagem das páginas não muda pelo painel.
- **Publica na hora**, com histórico das últimas 20 versões de cada página e botão de restaurar.
- **Visual e conteúdo** são os mesmos da versão em Astro, que saiu do repositório (fica no histórico do primeiro commit).
- **Hospedagem**: Apache com PHP 8.1+, a pasta `site/` inteira.

### Endereços

| Antes (Angular) | Agora |
|---|---|
| `/`, `/servicos`, `/quem-somos`, `/atas`... | os mesmos, com barra no fim |
| `/blog` | `/blog/` (lista de posts) |
| `/blog/{slug}` | `/{slug}/` (redireciona) |
| `/blog/categoria/{slug}` | `/category/{slug}/` (redireciona) |
| `/campanha-hp` | `/hp/` (redireciona; promoção encerrada) |

O post que usava o endereço `blog` (case de Giruá) virou `/case-ht-solutions-google-for-education-em-girua-rs/`, porque `/blog/` é a lista de posts. O endereço antigo redireciona.

## O que está feito

- **11 páginas** do site como páginas do blog com campos fixos (`site/extensions/site-ht/`), mais a lista de posts em `/blog/`.
- **117 posts** importados do Strapi, com imagens e vídeos baixados (`site/public/uploads/strapi/`).
- **Topo, rodapé, contatos, redes e parceiros** editáveis num formulário só ("Topo, rodapé e contatos").
- **Formulários da Emarsys** como no site antigo, cada página com o seu.
- **Consulta de garantias** igual à do site antigo, decifrando a base no navegador.
- **SEO**: título e descrição por página (editáveis no formulário), sitemap, 404 de verdade, redirecionamentos.
- **Apresentação no GitHub Pages** gerada a cada push (cópia estática, sem painel e sem busca).
- Testado: edição de texto, lista nova, lista dentro de lista (submenu), troca de imagem pela biblioteca, restauração de versão, limpeza de HTML perigoso, páginas e painel no celular.

## Decisões pendentes

- **Hospedagem com PHP** (onde roda hoje o Express não serve).
- **Termos de uso**: a página existe sem conteúdo no site antigo. Pedir o texto ou deixar fora.
- **Política de privacidade**: cita a lei portuguesa de proteção de dados (Lei n.º 67/98), não a LGPD. Precisa de revisão do jurídico.
- **Vídeos institucionais** (122 MB e 90 MB) continuam no Azure. Se o Azure for desligado, subir no YouTube e trocar o endereço no painel.
- **Páginas novas** (landing pages de campanha) não entram pelo painel: cada página nova precisa de um modelo, feito por desenvolvedor.
- **Logo**: o arquivo do site antigo tem 171 px de largura; vale pedir a versão em SVG.
