# HT Solutions

Site institucional estático da [HT Solutions](https://htsolutions.com.br), uma empresa do Grupo Herval, feito em [Astro](https://astro.build). Substitui o site em Angular com Strapi: não há banco de dados nem painel, cada alteração no código gera o site de novo.

O blog não está aqui: é o [blog-system](../blog/README.md) (PHP, sem banco), instalado na pasta `/blog/` do mesmo domínio, com painel em `/blog/admin`.

Páginas: início, quem somos, serviços, atas, Google para Empresas, Google for Education, HP, Certigov, consulta de garantias, política de privacidade e 404. Os endereços são os mesmos do site antigo (`/quem-somos`, `/servicos`, `/educacao`...); o Apache acrescenta a barra final.

## Rodar no computador

Requer Node.js 22.23 ou mais novo.

```bash
npm install
npm run dev        # site em http://localhost:4321, atualiza ao salvar
npm run build      # gera o site final em dist/
npm run preview    # serve o dist/ para conferir antes de publicar
npm run verificar  # depois do build: procura links internos quebrados (ignora /blog/)
```

No `npm run dev` o blog não está em `/blog/`, então os blocos "Cases" e "Últimas notícias" ficam só com o botão. Para vê-los, rode o blog (`../blog/run.bat`) e inicie o site com o endereço dele:

```bash
PUBLIC_BLOG_URL=http://localhost:8080 npm run dev
```

## Onde fica cada coisa

| Arquivo | O que tem |
|---|---|
| `src/config.mjs` | Contatos, redes sociais, menu do topo, links do rodapé, endereços externos (loja, atas, vagas, chamado técnico) e os formulários da Emarsys |
| `src/data/*.json` | Textos e imagens das páginas, trazidos do Strapi: `home`, `servicos`, `quem-somos`, `atas`, `googleparaempresas`, `educacao`, `parceiros` e `politica-de-privacidade`. Os campos `imagem`, `logo`, `foto` etc. são nomes de arquivo em `src/assets/strapi/`, sem extensão. Textos com HTML (`<p>`, `<strong>`, `<br>`) são exibidos como HTML |
| `src/assets/strapi/` | Imagens do conteúdo. São convertidas para WebP/AVIF em vários tamanhos no build |
| `src/assets/img/` | Logos (HT e Herval) e as imagens fixas do Certigov |
| `src/pages/` | Uma página por arquivo. Certigov, garantias e HP têm os textos no próprio arquivo (no site antigo também estavam no código) |
| `public/arquivos/` | E-book do Chrome Enterprise (PDF) |
| `public/assets/db_943hf3.bin` | Base da consulta de garantias (veja abaixo) |
| `public/.htaccess` | 404, redirecionamentos e cache para o Apache |

### Visual

Fonte Plus Jakarta Sans (`@fontsource-variable/plus-jakarta-sans`), azul da marca `#2673f0` com degradê para ciano, e fundos escuros (`--noite`) no topo, nos cabeçalhos das páginas e no rodapé. Cores, espaços e componentes ficam em `src/styles/global.css`. O blog repete o topo, o rodapé e a fonte em `../blog/public/assets/ht/ht.css`: se mudar um, mude o outro.

A página inicial não tem mais o carrossel do site antigo:

- **Abertura**: o título e o texto vêm do primeiro item de `banner` em `home.json`. A última palavra do título ganha o degradê. As três fotos ao lado (projeto de educação, equipe e prêmio) estão em `COLAGEM`, em `src/pages/index.astro`.
- **Atalhos**: os demais itens de `banner` (produtos, atas, educação, parcerias, quem somos) viram cartões com ícone. O ícone é escolhido pelo link do botão.
- **Números**: a lista `NUMEROS` em `src/pages/index.astro`, com dados tirados das outras páginas (19 anos, 50 mil Chromebooks, 11 fabricantes).
- **Soluções**: os três itens de `solucoes.solucao` em grade; o primeiro ocupa o espaço maior.
- **Parceiros**: só os logos (em cinza, coloridos ao passar o mouse). Os textos de cada parceiro aparecem em Quem somos.

### Formulários

Contato e newsletter vão para a Emarsys (`link.htsolutions.com.br`), como no site antigo. Cada página manda para o formulário dela, que define a lista e quem recebe o contato:

| Página | Formulário | Campo extra |
|---|---|---|
| Início, serviços, quem somos, HP e as demais | 1128 | Empresa |
| Atas e Certigov | 1193 | Órgão Público |
| Educação | 1129 | Instituição de Ensino |
| Google para Empresas | 1194 | Empresa |
| Newsletter | 1404 | – |

O envio é feito num iframe escondido: o visitante continua na página e vê a mensagem de confirmação. A página usa `formulario="atas"` (ou outro) no `<Base>` para escolher qual aparece no rodapé.

### Últimos posts e cases

A home e a página Google para Empresas mostram os últimos posts do blog. Eles são lidos no navegador de `/blog/ultimos.json`, criado pela extensão `../blog/extensions/ultimos-posts/`. Um post novo no blog aparece no site na hora, sem gerar o site de novo.

### Consulta de garantias

`public/assets/db_943hf3.bin` é a lista de equipamentos com garantia, cifrada (AES-GCM, chave por PBKDF2). **Ele não está no repositório** (`.gitignore`), porque o repositório é público: copie o arquivo do site antigo (`https://htsolutions.com.br/assets/db_943hf3.bin`) direto para o servidor. A página baixa o arquivo e decifra no navegador, como no site antigo. **A senha está no código da página, então qualquer pessoa consegue ler a lista inteira**, com nomes de clientes e contratos. Se isso for um problema, a consulta precisa de um servidor (um PHP pequeno no mesmo Apache resolveria). Para atualizar a lista, substitua o `.bin` por um gerado com a mesma senha e o mesmo formato.

### Conteúdo vindo do Strapi

`npm run importar` (`scripts/importar-strapi.mjs`) fez a conversão uma única vez, a partir da cópia da API em `../dados/strapi-2026-10-29/`: limpou o HTML, baixou as imagens para `src/assets/strapi/` (reduzidas a 2.400 px) e gravou `src/data/*.json`. **Não rode de novo**: ele sobrescreve os JSON e perde o que foi editado depois. Ajustes feitos à mão depois da importação: nomes das marcas em `parceiros.json` (texto alternativo dos logos), `<o:p>` do Word na política de privacidade e um erro de digitação em `servicos.json`.

## Publicação

A apresentação no GitHub Pages está descrita no [README da raiz](../README.md). Para gerar localmente como lá, com o blog rodando em `http://127.0.0.1:8080` e `HT_SITE_BASE=/htsolutions`:

```bash
SITE_URL=https://talys-marques.github.io BASE_PATH=/htsolutions PUBLIC_APRESENTACAO=1 npm run build
node scripts/blog-estatico.mjs http://127.0.0.1:8080 dist/blog https://talys-marques.github.io/htsolutions/blog
```

No Git Bash do Windows, coloque `MSYS_NO_PATHCONV=1` antes dos comandos; sem isso ele troca `/htsolutions` por `C:/Program Files/Git/htsolutions`.

O site definitivo precisa de Apache com PHP 8.1+ (por causa do blog). No servidor:

```
public_html/            conteúdo de dist/ (npm run build), incluindo o .htaccess
public_html/blog/       a pasta ../blog inteira (blog-system)
```

Depois de publicar, confira `https://htsolutions.com.br/blog/ultimos.json`: tem que devolver os posts em JSON.

## O que mudou em relação ao site antigo

- **Título, descrição e imagem de compartilhamento por página.** No Angular, todas as páginas tinham o mesmo título.
- **`sitemap-index.xml`, `robots.txt` e 404 de verdade.** Antes, endereços inexistentes respondiam com a página 404 e status 200.
- **`/campanha-hp`** (promoção de notebook com Windows 10 e "estoque limitado") redireciona para `/hp/`. O formulário dela gravava no Strapi.
- **`/termos-de-uso`** não foi recriada: estava no ar sem conteúdo.
- **reCAPTCHA** saiu dos formulários. Ele só bloqueava o botão no navegador; a Emarsys não conferia a resposta.
- **Envio duplicado do formulário de Educação** para `a.newshtsolutions.com.br/pub/rf` saiu. O lead continua indo para a Emarsys.
- **Vídeos** "A história da HT" (122 MB) e "Certificações" (90 MB) continuam no Azure (`htsolutions.blob.core.windows.net`): são grandes demais para o repositório. Se o Azure for desligado, suba no YouTube e troque o endereço em `home.json` e `quem-somos.json`.
- **Logo**: o arquivo do site antigo tem 171 px de largura e fica levemente borrado em telas de alta densidade. Vale pedir a versão em SVG.

## Pendências de conteúdo

- **Política de privacidade**: é um modelo que cita a lei portuguesa de proteção de dados (Lei n.º 67/98), não a LGPD. Precisa de revisão do jurídico.
- **Página `/termos-de-uso`**: pedir o texto, se for necessária.
