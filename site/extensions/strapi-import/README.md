# Extensão: Importar do Strapi

Opcional. Traz os posts da coleção `blogs` de um Strapi v4 (o blog antigo da HT
Solutions), com título, slug, data, categoria, resumo, capa e texto. As imagens e
vídeos do texto e as capas são baixados para `public/uploads/strapi/`.

**Não vai usar?** Apague esta pasta. Os posts importados ficam em `data/` e
continuam funcionando, assim como os redirecionamentos.

## Como usar

1. Abra `/admin` e crie a conta de administrador (os posts ficam no nome dela).
2. No terminal, na pasta do blog:

   ```bash
   php extensions/strapi-import/cli.php ../dados/strapi-2026-10-29
   ```

   Lê a cópia da API salva em `../dados/strapi-2026-10-29/` (`blogs.json` e
   `blogs2.json`). Para ler o Strapi direto, enquanto ele estiver no ar:

   ```bash
   php extensions/strapi-import/cli.php https://ht-strapi.azurewebsites.net/api
   ```

   `--autor=usuario` põe os posts no nome de outro usuário.

Pode rodar de novo: os mesmos posts são atualizados (pelo `strapi_id`), sem
duplicar, e as imagens que já estão em `public/uploads/strapi/` não são baixadas de novo.

No Windows, o PHP do winget vem sem as extensões ligadas. Rode assim (o `run.bat`
já faz isso para o servidor):

```bash
php -d extension_dir="CAMINHO_DO_PHP/ext" -d extension=mbstring -d extension=gd -d extension=openssl -d extension=fileinfo extensions/strapi-import/cli.php ../dados/strapi-2026-10-29
```

## O que a importação faz

- Slug, data e categoria iguais aos do Strapi. Os endereços do site antigo redirecionam:
  `/blog/{slug}` para `/{slug}/` e `/blog/categoria/{slug}` para `/category/{slug}/`.
- Posts com `destaque` no Strapi ganham a tag **Destaque**.
- O HTML é limpo: saem estilos, classes e atributos colados do Google Docs,
  ChatGPT e YouTube; spans e divs vazios somem; `h1` no texto vira `h2`.
  Vídeos do YouTube e `<video>` continuam.
- Imagens mais largas que `image_max_width` (`config.php`, 2000 px) são reduzidas.
- Arquivos que não puderam ser baixados saem do texto e aparecem no relatório.

## Arquivos

| Arquivo | O que é |
|---|---|
| `extension.php` | registra a extensão (não tem tela no painel) |
| `StrapiImporter.php` | leitura da API ou dos JSON, limpeza do HTML e download dos arquivos |
| `cli.php` | importação pelo terminal |
