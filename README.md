# HT Solutions

Novo site da [HT Solutions](https://htsolutions.com.br), uma empresa do Grupo Herval, para substituir o site atual em Angular com Strapi.

**Apresentação:** https://talys-marques.github.io/htsolutions/

| Pasta | O que é |
|---|---|
| [`site/`](site/README.md) | O site inteiro, no [blog-system](https://github.com/fabianokunst/blog-system) (PHP, sem banco de dados): páginas institucionais, blog e painel em `/admin` |
| `site/extensions/site-ht/` | [Conteúdo do site](site/extensions/site-ht/README.md): formulários de campos fixos para o negócio editar textos, imagens, links e menus das páginas |
| `site/extensions/strapi-import/` | Importação dos 117 posts do Strapi antigo |
| `dados/` | Cópia do conteúdo do Strapi antigo (29/10/2026) |
| `apresentacao/` | Gera a cópia estática do site para o GitHub Pages |
| [`ANALISE.md`](ANALISE.md) | Análise do site antigo, decisões e pendências |

## Rodar no computador

Requer PHP 8.1 ou mais novo. No Windows, dê dois cliques em `site/run.bat` e abra http://localhost:8080/admin. Na primeira vez:

1. Crie a conta de administrador. No nome do site, digite **HT Solutions**.
2. Importe os posts e instale as páginas do site, na pasta `site/` (no Windows, com o `php -d extension_dir=...` do README da extensão `strapi-import`):

```bash
php extensions/strapi-import/cli.php ../dados/strapi-2026-10-29
php extensions/site-ht/instalar.php
```

Depois disso, o conteúdo das páginas é editado no painel, em **Conteúdo do site**. Os posts ficam em **Posts**.

## No servidor definitivo

Apache com PHP 8.1+. A pasta `site/` inteira vai para o servidor (o `.htaccess` da raiz manda tudo para `site/public/`). As pastas `data/` e `public/uploads/` precisam de permissão de escrita. Depois, os mesmos passos de "Rodar no computador" e a cópia de `public/assets/db_943hf3.bin` (consulta de garantias) a partir do site antigo.

## Na apresentação (GitHub Pages)

O GitHub Pages não roda PHP. A cada push na `main`, `.github/workflows/pages.yml` monta o site com o conteúdo do repositório (posts de `dados/`, páginas de `site/extensions/site-ht/conteudo/`), salva uma cópia em HTML com `apresentacao/estatico.mjs` e publica.

- Sem painel, sem busca e sem consulta de garantias (a base, com nomes de clientes, não vai para este repositório público).
- O que o negócio edita no painel só aparece na apresentação depois de `php extensions/site-ht/instalar.php --exportar` e um push.
- As páginas da apresentação não aparecem no Google (`noindex`) e não enviam dados ao Google Tag Manager.
