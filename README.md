# HT Solutions

Novo site da [HT Solutions](https://htsolutions.com.br), uma empresa do Grupo Herval, para substituir o site atual em Angular com Strapi.

**Apresentação:** https://talys-marques.github.io/htsolutions/

| Pasta | O que é |
|---|---|
| [`site/`](site/README.md) | O site inteiro, no [blog-system](https://github.com/fabianokunst/blog-system) (PHP, sem banco de dados): páginas institucionais, blog e painel em `/admin` |
| `site/themes/ht/` | Tema da HT: visual, modelo de cada página (formulário de campos fixos em **Conteúdo do site**, para o negócio editar textos, imagens, links e menus) e conteúdo inicial. Detalhes em [`site/CLAUDE.md`](site/CLAUDE.md) |
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
php themes/ht/instalar.php
```

Depois disso, o conteúdo das páginas é editado no painel, em **Conteúdo do site**. Os posts ficam em **Posts**.

## No ar (Hostinger)

O código vai pelo Git e o conteúdo fica só no servidor:

| O quê | Caminho | Quando entra no ar |
|---|---|---|
| Código, visual, modelos das páginas, atualizações do blog-system | push na `main` → `.github/workflows/producao.yml` atualiza a branch `producao` (só a pasta `site/`, na raiz) → a Hostinger puxa a branch para o `public_html` | segundos depois do push |
| Textos, imagens, menus, posts, configurações | painel `/admin` do próprio servidor (`data/` e `public/uploads/`, fora do Git por `site/.gitignore`) | na hora |

Os deploys não apagam o conteúdo: `data/`, os uploads novos e a base de garantias são ignorados pelo Git, então sobrevivem a `git pull`, `git reset --hard` e `git clean -fd`.

### Preparar a primeira vez

1. **hPanel → Avançado → Git**: conectar com o GitHub, repositório `talys-marques/htsolutions`, branch **`producao`**, pasta de instalação em branco (`public_html`, que precisa estar vazia) e deploy automático ligado.
2. **hPanel → PHP**: versão 8.3, com `mbstring`, `gd` e `fileinfo` (vêm ligadas).
3. **Conteúdo**, pelo Gerenciador de Arquivos:
   - `public_html/data/`: o conteúdo de `site/data/` do computador (posts, páginas, contas, configurações), sem `sessions/` e `cache/`.
   - `public_html/public/uploads/`: as pastas de ano (`2026/`...) de `site/public/uploads/`, se houver. `site/` e `strapi/` já vêm pelo Git.
   - `public_html/public/assets/db_943hf3.bin`: a base da consulta de garantias, do site antigo.
   - Sem `site/data/` do computador, outro caminho é instalar do zero por SSH: criar a conta em `/admin` e rodar `php themes/ht/instalar.php` dentro do `public_html` (os posts do Strapi exigiriam subir `dados/` também).
4. **Testar antes de trocar o DNS**: abrir pelo endereço temporário da Hostinger, conferir páginas, painel, um envio de imagem e um push de teste (a imagem enviada tem que continuar lá depois do deploy).
5. **Domínio**: apontar o DNS de htsolutions.com.br para a Hostinger e ativar o SSL. Os endereços do site antigo já redirecionam (estão nos dados).

### No dia a dia

- **Negócio**: edita em `/admin`, entra no ar ao salvar. Backup: botão em Backup e ferramentas e os backups da própria Hostinger (o Git não guarda o conteúdo).
- **Desenvolvimento**: muda o código, testa com `site/run.bat`, faz push na `main`. A Action atualiza a `producao` e a Hostinger publica. Para publicar sem push (ou de novo), Actions → **Publicar na Hostinger** → Run workflow.
- **Comandos no servidor** (trocar senha com `tools/reset-password.php`, migrações de dados): precisam de SSH no plano da Hostinger.
- **`site/themes/ht/settings.json`**: as configurações da HT aplicadas na primeira instalação (e no GitHub Pages). Depois, as configurações valem as do painel; mudar este arquivo não altera o site no ar.

## Na apresentação (GitHub Pages)

O GitHub Pages não roda PHP. A cada push na `main`, `.github/workflows/pages.yml` monta o site com o conteúdo do repositório (posts de `dados/`, páginas de `site/themes/ht/content/`), salva uma cópia em HTML com `apresentacao/estatico.mjs` e publica.

- Sem painel, sem busca e sem consulta de garantias (a base, com nomes de clientes, não vai para este repositório público).
- O que o negócio edita no painel só aparece na apresentação depois de `php themes/ht/instalar.php --exportar` e um push.
- As páginas da apresentação não aparecem no Google (`noindex`) e não enviam dados ao Google Tag Manager.
