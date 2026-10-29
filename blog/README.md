# Blog em PHP sem banco de dados

Sistema de blog simples para sair do WordPress. PHP 8.1+, **sem banco de dados**
(tudo em arquivos JSON), **sem dependências** (nada de Composer, npm, plugins ou
build) e sem atualizações automáticas que quebram coisas.

- Visitantes só leem: não há comentários nem cadastro público.
- Um **administrador único** controla tudo. Opcionalmente, ele cria contas de
  **autores**, que cuidam de todo o conteúdo (posts, páginas, mídia e SEO de
  categorias e tags), mas não da parte técnica (usuários, configurações, SEO
  geral, redirecionamentos e backup).
- Funcionalidades novas são feitas sob medida, editando o código (vibe code).
  Veja o [CLAUDE.md](CLAUDE.md).

## Recursos

| Área | O que tem |
|---|---|
| Conteúdo | Posts e páginas, rascunho, agendamento (data futura), categorias, tags, imagem de capa, resumo, lixeira e **revisões** (últimas 20 versões de cada post) |
| Editor | Editor visual próprio (sem bibliotecas): títulos, negrito, listas, citação, código, links, imagens (arrastar, colar ou escolher da biblioteca), vídeos do YouTube/Vimeo, legenda e texto alternativo, modo HTML, `Ctrl+S`, limpeza do que é colado do Word/Google Docs e **cópia local de segurança** do texto enquanto você escreve |
| Mídia | Biblioteca com envio múltiplo e redução automática de imagens grandes |
| SEO | URLs iguais às do WordPress (`/slug/`, `/category/x/`, `/tag/x/`, `/?s=`, `/?p=123`), título e descrição por post, Open Graph, JSON-LD, sitemap.xml, robots.txt, **redirecionamentos 301** (automáticos quando o endereço de um post muda) |
| Migração | Importador do WordPress **opcional**, como extensão separada (veja [Extensões](#extensões)), pela tela ou pelo terminal, que mantém endereços, datas, categorias, tags, capas e descrições do Yoast/Rank Math |
| Operação | Backup para download, exportação no formato do WordPress, códigos de analytics no `<head>`, tema com modo escuro automático, painel responsivo |
| Segurança | Senhas com `password_hash`, CSRF em todo formulário, limite de tentativas de login, HTML dos autores filtrado, uploads sem execução de PHP, dados fora da pasta pública, nenhum cookie para visitantes |

## Dá para fazer sem banco de dados? Sim, e com folga para um blog

Um blog lê muito e grava pouco, com uma pessoa (ou poucas) escrevendo. É o
cenário ideal para arquivos. Como o sistema evita os problemas clássicos:

| Problema | Solução aqui |
|---|---|
| Duas gravações ao mesmo tempo | Trava exclusiva (`flock` em `data/.lock`) em toda operação "ler, alterar, gravar" |
| Arquivo corrompido se o servidor cair no meio | Gravação atômica: escreve num temporário e troca com `rename`, então o arquivo nunca fica pela metade |
| Listar posts sem abrir milhares de arquivos | `data/index.json` guarda só os metadados de todos os posts e é lido uma vez por página |
| Busca | `data/search.json` com as palavras de cada post já normalizadas (sem acento, sem repetição) |
| Índice desatualizado ou apagado | Ele é derivado e se refaz sozinho a partir de `data/posts/` (ou pelo botão "Reconstruir índice") |
| Erro humano | Revisões de cada post, lixeira e backup com um clique |

**Medições** com 3.000 posts de ~800 palavras, num notebook Windows, PHP 8.3
**sem opcache** (em servidor com opcache fica mais rápido):

| Operação | Tempo |
|---|---|
| Página inicial | ~29 ms |
| Post individual | ~29 ms |
| Categoria | ~21 ms |
| Busca | ~93 ms |
| Salvar um post | ~115 ms |
| Importar 3.000 posts | ~10 s |
| Memória máxima | 66 MB |

Para comparar: um WordPress sem cache costuma levar de 300 a 800 ms por página.

**Até onde vai:** fica confortável até uns **5 a 10 mil posts**. Acima disso o
`search.json` passa de algumas dezenas de MB e a busca e o salvamento ficam
lentos. Nesse ponto, o caminho natural é trocar só a busca por SQLite FTS (que
também não exige servidor de banco). Também **não serve** para hospedagens sem
disco persistente (Vercel, Heroku, containers efêmeros), nem para muita gente
escrevendo ao mesmo tempo ou para comentários. Isso está fora do escopo de
propósito.

## Requisitos

- PHP **8.1 ou superior**, com as extensões `json`, `mbstring` e `session`
  (vêm em qualquer hospedagem)
- Recomendado: `gd` (redimensiona imagens), `fileinfo` e `zip` (backup em .zip; sem ela o backup sai em .tar.gz). A extensão de importação do WordPress usa `xmlreader`.
- Apache com `mod_rewrite` (qualquer hospedagem compartilhada/cPanel) **ou** nginx

## Instalação

### Hospedagem compartilhada (cPanel, Hostinger, Locaweb, HostGator...)

1. Envie **a pasta inteira** para o servidor (ex.: `public_html/`). Pode ser
   numa subpasta (ex.: `public_html/blog/`, aberto em `seudominio.com.br/blog/`):
   imagens e links se ajustam sozinhos, e dá para trocar de pasta depois sem
   mexer em nada.
2. Se puder, aponte o domínio para a subpasta `public/`. Se não puder, tudo
   bem: o `.htaccess` da raiz já redireciona internamente para `public/` e
   bloqueia o resto.
3. Dê permissão de escrita (775) para `data/` e `public/uploads/`.
4. Abra `https://seudominio.com.br/admin` e crie a conta de administrador.
   **Faça isso logo depois de subir os arquivos**, porque o primeiro acesso cria o dono do blog.
5. Em **Configurações**, preencha o "Endereço do site" (ex.: `https://seudominio.com.br`).

Confira que a proteção funciona: `https://seudominio.com.br/data/users.json`
precisa dar **404**.

### nginx

Aponte o `root` para a pasta `public/`:

```nginx
server {
    server_name meublog.com.br;
    root /var/www/blog/public;
    index index.php;
    client_max_body_size 64m;

    location / {
        try_files $uri /index.php?$query_string;
    }
    location ~ ^/(uploads|wp-content)/.*\.php$ { deny all; }
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

### No seu computador

No Windows, dê dois cliques em `run.bat`: ele sobe o servidor e abre o painel.
Para usar outra porta, rode `run.bat 8090`. Em qualquer sistema:

```bash
php -d upload_max_filesize=64M -d post_max_size=64M -S localhost:8080 -t public public/index.php
```

Depois abra http://localhost:8080/admin. Nesse modo os erros aparecem
detalhados na tela.

## Extensões

Recursos opcionais ficam isolados, cada um numa pasta dentro de `extensions/`.
Você escolhe o que leva junto: **apagar a pasta remove o recurso** (rotas e item
do menu) e o resto do blog não muda. O núcleo nunca depende de uma extensão.

| Extensão | O que faz |
|---|---|
| `extensions/wordpress-import/` | Importa o conteúdo de um WordPress. Detalhes no [README dela](extensions/wordpress-import/README.md) |

Para criar uma extensão nova, veja o [CLAUDE.md](CLAUDE.md#extensões).

## Migrando do WordPress

Precisa da extensão `extensions/wordpress-import/`. Se não vai migrar, apague essa pasta.

1. No WordPress: **Ferramentas → Exportar → Todo o conteúdo** e baixe o `.xml`.
2. Aqui: **Importar WordPress** e envie o arquivo (até 64 MB, definido em
   `public/.user.ini`; algumas hospedagens só deixam mudar isso no painel delas).
   Se for maior, coloque-o por FTP em `data/import/` e escolha na lista, ou
   rode `php extensions/wordpress-import/cli.php exportacao.xml`.
3. Traga as imagens, que continuam com o mesmo endereço: na mesma tela, clique em
   **Baixar imagens do site antigo** (baixa só as que os posts usam, enquanto o
   site antigo estiver no ar), ou copie a pasta `wp-content/uploads` do WordPress
   para `public/wp-content/uploads`.
4. Aponte o domínio para o novo sistema.
5. Se quiser, apague `extensions/wordpress-import/`: o conteúdo importado, os
   redirecionamentos e os links antigos `/?p=123` continuam funcionando.

O que acontece na importação:

- Posts e páginas mantêm slug, data, categorias, tags, imagem destacada, resumo
  e descrição do Yoast/Rank Math. Os links internos viram relativos.
- Conteúdo do editor clássico ganha parágrafos (como o WordPress faz); blocos
  do Gutenberg são limpos; `[caption]` vira `<figure>`; links do YouTube/Vimeo viram players.
- Se o endereço antigo era diferente (ex.: `/2020/05/titulo/`), um
  redirecionamento 301 é criado. Links `/?p=123` também continuam funcionando.
- Shortcodes de plugins (`[contact-form-7]`, `[gallery]`...) ficam no texto, e o
  relatório lista quais apareceram para você ajustar.
- Pode importar de novo quantas vezes quiser: os mesmos posts são atualizados,
  sem duplicar.
- Para manter a autoria de outros autores, crie antes usuários com o mesmo
  login do WordPress.

## Backup

O blog inteiro são duas pastas:

- `data/`: posts, páginas, usuários, configurações e redirecionamentos (JSON legível)
- `public/uploads/` (e `public/wp-content/uploads/`, se importou do WordPress): arquivos

Baixe pelo painel (**Backup e ferramentas**) ou copie as pastas por FTP. Para
restaurar, descompacte por cima. Como são arquivos de texto, também dá para
versionar `data/posts/` no git.

## Exportar

Em **Backup e ferramentas → Exportar** sai um `.xml` no formato do WordPress
(o mesmo de **Ferramentas → Exportar** de lá): posts e páginas (com rascunhos e
agendados), categorias, tags, autores, capas, imagens e título/descrição SEO
(nos campos do Yoast e do Rank Math). Os ids são mantidos.

- No WordPress: **Ferramentas → Importar → WordPress**, marcando **Baixar e
  importar arquivos anexos**. As imagens são baixadas deste site, que precisa
  estar no ar, e os links delas no conteúdo são trocados automaticamente.
- Outras plataformas que importam do WordPress aceitam o mesmo arquivo.
- Configurações, menu e senhas ficam de fora (para isso existe o backup).

## Começar do zero

Todo o conteúdo fica em `data/` e `public/uploads/`; o código não muda com o uso.
No Windows, rode `reset.bat`: ele pede para você digitar **SIM** e então esvazia
`data/` e `public/uploads/` (mantendo os `.htaccess` de proteção) e apaga
`public/wp-content/`. Código, tema e extensões continuam intactos. No próximo
acesso a `/admin` aparece a configuração inicial de novo.

Em outro sistema, faça o mesmo à mão: apague o conteúdo dessas pastas, menos os `.htaccess`.

Para testar sem mexer nos dados de verdade, use uma pasta separada com a
variável `BLOG_DATA_DIR` (ex.: `set BLOG_DATA_DIR=C:\blog-teste` antes do `run.bat`).
Os uploads continuam indo para `public/uploads/`.

## Esqueci a senha

Não há "esqueci a senha" por e-mail (é menos uma coisa para dar errado). No servidor:

```bash
php tools/reset-password.php seu-usuario
```

O comando também libera o login se ele tiver sido bloqueado por tentativas erradas.

## Estrutura

```
config.php            configurações técnicas (pasta de dados, limites de upload)
app/                  código do sistema
  App.php             inicialização e despacho
  Router.php          rotas
  Storage.php         leitura/gravação de JSON (atômica e com trava)
  Posts.php           posts, páginas, índice, busca, revisões
  Auth.php            usuários, login, sessões e permissões
  Media.php           uploads
  Redirects.php       redirecionamentos 301
  Settings.php        configurações do painel
  Site.php            páginas públicas
  Admin.php           telas do painel
  Extensions.php      carrega as extensões de extensions/
  Html.php            filtro de HTML dos autores
  Backup.php
  Export.php          exportação no formato do WordPress (WXR)
  helpers.php         funções globais (e(), url(), slugify()...)
  routes/             mapa de URLs → métodos
  views/admin/        templates do painel
extensions/           recursos opcionais (apague a pasta para remover)
  wordpress-import/   importador do WordPress
theme/                templates do site público (layout, home, post...)
public/               única pasta acessível pela web
  index.php           porta de entrada
  assets/             site.css, admin.css, admin.js
  uploads/            arquivos enviados
data/                 conteúdo (criado na primeira execução)
tools/                scripts de terminal
```

## Atualizações

Não existe atualização automática nem dependência externa: o sistema só muda
quando você muda. O código usa apenas recursos estáveis do PHP 8.1, então
deve funcionar sem ajustes nas próximas versões do PHP. Antes de trocar a
versão do PHP na hospedagem, teste localmente com `php -S`.
