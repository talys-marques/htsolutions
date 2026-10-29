#!/usr/bin/env node
// One-time conversion of the old Strapi content into src/data/*.json, with the images
// downloaded to src/assets/strapi/ and PDFs to public/arquivos/.
// Usage: npm run importar [-- ../dados/strapi-2026-10-29]
// After the migration the JSON files are the content: edit them directly, don't run this again
// (it would overwrite the edits).

import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { join, basename, extname } from 'node:path';
import sharp from 'sharp';

const ORIGEM = process.argv[2] ?? '../dados/strapi-2026-10-29';
const IMAGENS = 'src/assets/strapi';
const ARQUIVOS = 'public/arquivos';
const DADOS = 'src/data';
const LARGURA_MAXIMA = 2400;

// Strapi file → output file in src/data/
const PAGINAS = {
  home: 'home',
  servico: 'servicos',
  'quem-somos': 'quem-somos',
  'pagina-ata': 'atas',
  'google-empresa': 'googleparaempresas',
  'google-for-education': 'educacao',
  parceiros: 'parceiros',
  'politica-de-privacidade': 'politica-de-privacidade',
};

// Fields that only matter to Strapi
const DESCARTAR = new Set(['id', 'createdAt', 'updatedAt', 'publishedAt', '__component', 'locale', 'destaque']);

// Videos are too big for the repository (90-130 MB): they stay on the Azure blob
const MAX_DOWNLOAD = 20 * 1024 * 1024;

mkdirSync(IMAGENS, { recursive: true });
mkdirSync(ARQUIVOS, { recursive: true });
mkdirSync(DADOS, { recursive: true });

const nomeLocal = (url) => {
  const ext = extname(new URL(url).pathname).toLowerCase().replace('.jpeg', '.jpg');
  const nome = basename(new URL(url).pathname, extname(new URL(url).pathname))
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 90);
  return { nome, ext };
};

const baixados = new Map();
async function baixar(url) {
  if (baixados.has(url)) return baixados.get(url);
  const { nome, ext } = nomeLocal(url);
  const ehImagem = ['.jpg', '.png', '.webp', '.gif'].includes(ext);
  const destino = ehImagem ? join(IMAGENS, nome + ext) : join(ARQUIVOS, nome + ext);
  let resultado = ehImagem ? nome : `/arquivos/${nome}${ext}`;

  if (!existsSync(destino)) {
    const cabeca = await fetch(url, { method: 'HEAD' });
    const tamanho = Number(cabeca.headers.get('content-length') || 0);
    if (!cabeca.ok || tamanho > MAX_DOWNLOAD) {
      resultado = url;
      console.log(`  mantido no Azure: ${basename(url)} (${(tamanho / 1048576).toFixed(0)} MB)`);
    } else {
      const corpo = Buffer.from(await (await fetch(url)).arrayBuffer());
      if (ehImagem && ext !== '.gif') {
        const meta = await sharp(corpo).metadata();
        const saida = meta.width > LARGURA_MAXIMA ? await sharp(corpo).resize(LARGURA_MAXIMA).toBuffer() : corpo;
        writeFileSync(destino, saida);
      } else {
        writeFileSync(destino, corpo);
      }
    }
  }
  baixados.set(url, resultado);
  return resultado;
}

// HTML pasted from Google Docs/ChatGPT: keep the tags, drop the formatting
const limparHtml = (html) =>
  html
    .replace(/\s(?:data-[\w-]+|class|style|dir|id|aria-[\w-]+|role|_ngcontent[\w-]*)="[^"]*"/g, '')
    .replace(/<\/?(?:span|div)>|<meta[^>]*>/g, '')
    .replace(/&nbsp;/g, ' ')
    .replace(/<p>\s*<\/p>/g, '')
    .replace(/<(strong|em|i)>\s*<\/\1>/g, '')
    .trim();

// Links to the site itself become root-relative with a trailing slash; the blog moved to
// the blog-system, where categories live in /blog/category/{slug}/
const normalizarLink = (link) => {
  let caminho = link.replace(/^https?:\/\/(www\.)?htsolutions\.com\.br/, '');
  if (/^[a-z]+:/i.test(caminho)) return link;
  caminho = '/' + caminho.replace(/^\/+/, '');
  const [base, ancora] = caminho.split('#');
  let saida = base.replace(/^\/blog\/categoria\//, '/blog/category/');
  if (!/\.\w+$/.test(saida) && !saida.endsWith('/')) saida += '/';
  return ancora ? `${saida}#${ancora}` : saida;
};

const ehMidia = (v) =>
  v && typeof v === 'object' && 'data' in v && Object.keys(v).length === 1 &&
  (v.data === null || (Array.isArray(v.data) ? v.data.every((d) => d.attributes?.url) : v.data.attributes?.url));

async function converter(valor) {
  if (ehMidia(valor)) {
    if (valor.data === null) return null;
    if (Array.isArray(valor.data)) return Promise.all(valor.data.map((d) => baixar(d.attributes.url)));
    return baixar(valor.data.attributes.url);
  }
  if (Array.isArray(valor)) return Promise.all(valor.map(converter));
  if (valor && typeof valor === 'object') {
    // Strapi relations: { data: { id, attributes } } or { data: [...] }
    if ('data' in valor && Object.keys(valor).length === 1) {
      const d = valor.data;
      if (d === null) return null;
      return Array.isArray(d) ? Promise.all(d.map((x) => converter(x.attributes))) : converter(d.attributes);
    }
    const saida = {};
    for (const [chave, v] of Object.entries(valor)) {
      // Related blog posts (they have a slug) carry the full text, which lives in the blog
      if (DESCARTAR.has(chave) || (chave === 'conteudo' && 'slug' in valor)) continue;
      const linkDoSite = chave === 'link' && typeof v === 'string' && !v.includes('blob.core.windows.net');
      saida[chave] = linkDoSite ? normalizarLink(v) : await converter(v);
    }
    return saida;
  }
  if (typeof valor === 'string') {
    if (/^https:\/\/htsolutions\.blob\.core\.windows\.net\/.+\.(pdf|png|jpe?g)$/i.test(valor)) return baixar(valor);
    const texto = valor.replace(/ {2,}/g, ' ');
    return /<[a-z]/i.test(texto) ? limparHtml(texto) : texto.trim();
  }
  return valor;
}

for (const [origem, destino] of Object.entries(PAGINAS)) {
  const json = JSON.parse(readFileSync(join(ORIGEM, `${origem}.json`), 'utf8'));
  const dados = Array.isArray(json.data)
    ? await Promise.all(json.data.map((d) => converter(d.attributes)))
    : await converter(json.data.attributes);
  writeFileSync(join(DADOS, `${destino}.json`), JSON.stringify(dados, null, 2) + '\n');
  console.log(`${destino}.json`);
}
console.log(`${baixados.size} arquivos`);
