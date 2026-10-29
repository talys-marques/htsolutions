# HT Solutions

Novo site da [HT Solutions](https://htsolutions.com.br), uma empresa do Grupo Herval, para substituir o site atual em Angular com Strapi.

**Apresentação:** https://talys-marques.github.io/htsolutions/

| Pasta | O que é |
|---|---|
| [`site/`](site/README.md) | Site institucional estático, em [Astro](https://astro.build) |
| [`blog/`](blog/README.md) | Blog em PHP sem banco de dados ([blog-system](https://github.com/fabianokunst/blog-system)), com o tema da HT e duas extensões: importação do Strapi e últimos posts em JSON |
| `dados/` | Cópia do conteúdo do Strapi antigo (29/10/2026), de onde vieram os textos, imagens e posts |
| [`ANALISE.md`](ANALISE.md) | Análise do site antigo, decisões e pendências |

## No servidor definitivo

Apache com PHP 8.1+, tudo no mesmo domínio:

```
htsolutions.com.br/          site/dist/ (npm run build)
htsolutions.com.br/blog/     blog/ (blog-system, com painel em /blog/admin)
```

## Na apresentação (GitHub Pages)

O GitHub Pages não roda PHP. A cada push na `main`, `.github/workflows/pages.yml` gera o site, sobe o blog só durante o build (com os posts importados de `dados/`), salva uma cópia estática dele com `site/scripts/blog-estatico.mjs` e publica os dois juntos.

- Na cópia do blog não há busca nem painel `/admin`: só existem com PHP.
- A consulta de garantias não funciona na apresentação: a base, com nomes de clientes, não vai para este repositório público.
- As páginas da apresentação não aparecem no Google (`noindex`) e não enviam dados ao Google Tag Manager.

Para ligar: em **Settings > Pages** do repositório, escolha **GitHub Actions** como fonte.
