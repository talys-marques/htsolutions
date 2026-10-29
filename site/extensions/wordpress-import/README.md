# Extensão: Importar do WordPress

Opcional. Traz posts, páginas, categorias, tags, imagens de capa (com o texto
alternativo) e o SEO do Yoast/Rank Math (título, descrição, palavra-chave,
"não indexar" e categoria principal) de uma exportação do WordPress (WXR).
Categorias e tags cujo endereço muda ganham redirecionamento, e as descrições
delas vão para **SEO → Categorias e tags**.

**Não vai usar?** Apague esta pasta. O item "Importar WordPress" some do menu
e nada mais muda. Se já importou, pode apagar também: o conteúdo importado
fica em `data/` e continua funcionando, inclusive os links antigos `/?p=123` e
os redirecionamentos.

## Como usar

1. No WordPress: **Ferramentas → Exportar → Todo o conteúdo**.
2. No painel: **Importar WordPress**, e envie o `.xml`. Arquivo grande: coloque
   em `data/import/` por FTP e escolha na lista, ou use o terminal:

   ```bash
   php extensions/wordpress-import/cli.php exportacao.xml
   php extensions/wordpress-import/cli.php exportacao.xml --url=https://blog-antigo.com.br --autor=usuario --sem-rascunhos
   ```

3. Traga as imagens para `public/wp-content/uploads`, de um destes jeitos:
   - **Baixar imagens do site antigo**, na mesma tela (ou `php extensions/wordpress-import/cli.php --imagens`).
     Baixa só as imagens que os posts usam, em partes, e pode ser interrompido e
     retomado. Faça antes de apontar o domínio para o novo sistema.
   - Copiar a pasta `wp-content/uploads` do WordPress, se você tiver acesso a ela.

   A importação já cria `public/wp-content/` com a proteção que impede a execução de scripts.

Importou antes de o sistema trazer o SEO completo? Rode
`php extensions/wordpress-import/cli.php exportacao.xml --so-seo`: completa só os
campos de SEO vazios e a categoria principal, sem mexer no texto nem no que foi editado aqui.

## Arquivos

| Arquivo | O que é |
|---|---|
| `extension.php` | registra a rota `/admin/import` e o item do menu |
| `WordPressImportPage.php` | tela do painel |
| `WordPressImporter.php` | leitura do XML e conversão do conteúdo |
| `WordPressImages.php` | download das imagens do site antigo |
| `views/import.php` | template da tela |
| `cli.php` | importação pelo terminal |
