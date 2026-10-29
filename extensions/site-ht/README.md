# Extensão: Conteúdo do site (HT Solutions)

Transforma o blog-system no site inteiro da HT Solutions: as páginas institucionais (início,
serviços, quem somos, atas, Google, educação, Certigov, HP, garantias, política) são páginas do
blog com **campos fixos**, editados em **Conteúdo do site**, no painel. Autores também editam.

Dá para trocar textos, imagens e links, e acrescentar, mover ou tirar itens das listas
(atalhos, números, parceiros, itens do menu...). A montagem de cada página não muda pelo painel.

## Como funciona

- Cada página do site é uma página do blog (type `page`) com `modelo` e `campos`.
  Ganha do blog o endereço, o SEO, o sitemap, a lixeira e o histórico de versões.
- `modelos/{modelo}.php`: os campos do formulário (tipos em `Campo.php`).
- `../../theme/site/{modelo}.php`: o template da página, que lê os campos.
- **Topo, rodapé e contatos** (menu, contatos, redes, rodapé, newsletter e parceiros) é uma
  página em rascunho, `site-geral`, que o tema lê em todas as páginas.
- Salvar publica na hora. Cada gravação guarda a versão anterior; o formulário lista as
  últimas 20 com o botão **Restaurar**.
- O HTML dos campos de texto passa pelo `Html::clean()` ao salvar.

## Instalar

Depois de criar a conta de administrador em `/admin` e importar os posts
(`extensions/strapi-import/`), na pasta do site:

```bash
php extensions/site-ht/instalar.php
```

Cria as páginas a partir de `conteudo/*.json` (as que já existem ficam como estão), põe a
página inicial em `/`, a lista de posts em `/blog/`, o nome do site e redireciona os endereços
do site antigo (`/blog/{slug}`, `/blog/categoria/{slug}`, `/campanha-hp`).

- `--atualizar`: sobrescreve os campos das páginas com o que está em `conteudo/`.
- `--exportar`: grava em `conteudo/` o que está publicado, para versionar no Git o que o
  negócio editou no painel. A apresentação do GitHub Pages é montada a partir desses arquivos.

No Windows, use o mesmo `php -d extension_dir=... -d extension=mbstring ...` do README da
extensão `strapi-import`.

## Consulta de garantias

A lista de equipamentos é `public/assets/db_943hf3.bin`, cifrada e decifrada no navegador
(`public/assets/ht/paginas.js`), como no site antigo. A senha está no JavaScript: qualquer
pessoa consegue ler a lista. O arquivo não vai para o repositório (que é público): copie do site
antigo (`https://htsolutions.com.br/assets/db_943hf3.bin`) direto para o servidor.

## Novo campo ou nova página

1. Campo: inclua no `modelos/{modelo}.php` e use em `theme/site/{modelo}.php`. Páginas que
   ainda não têm o campo recebem ele vazio (`SiteHT::completar()`).
2. Página: crie `modelos/{nome}.php`, `theme/site/{nome}.php` e `conteudo/{nome}.json` (com
   `titulo`, `slug`, `descricao` e `campos`), e rode `instalar.php`.

**Apagar esta pasta** tira o formulário do painel e o conteúdo das páginas do site, que passam
a mostrar só o texto comum de página.

## Arquivos

| Arquivo | O que é |
|---|---|
| `extension.php` | rotas do painel (`/admin/site`) e item do menu |
| `SiteHT.php` | lista de páginas, formulário, limpeza dos campos, versões |
| `Campo.php` | tipos de campo dos modelos |
| `modelos/` | campos de cada página |
| `conteudo/` | conteúdo inicial das páginas (vindo do site antigo) |
| `views/` | telas do painel |
| `instalar.php` | instalação, atualização e exportação pelo terminal |
