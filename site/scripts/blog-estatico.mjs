#!/usr/bin/env node
// Static copy of the blog (blog-system, PHP) for the GitHub Pages presentation, where PHP can't run.
// Crawls a running blog and saves pages, images, CSS/JS and the latest-posts JSON as plain files.
//
//   node scripts/blog-estatico.mjs <blog running> <output folder> <public address of the blog>
//   node scripts/blog-estatico.mjs http://127.0.0.1:8080 dist/blog https://usuario.github.io/htsolutions/blog
//
// Run the blog with HT_SITE_BASE set to the site folder (/htsolutions), so its links to the site
// already carry the folder. Search and /admin don't exist in the copy.

import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';

const [origem, saida, publico] = process.argv.slice(2);
if (!origem || !saida || !publico) {
  console.error('Uso: node scripts/blog-estatico.mjs <blog no ar> <pasta de saída> <endereço público do blog>');
  process.exit(1);
}
const ORIGEM = origem.replace(/\/$/, '');
const PUBLICO = publico.replace(/\/$/, '');
const PASTA_BLOG = new URL(PUBLICO).pathname.replace(/\/$/, ''); // /htsolutions/blog
const PASTA_SITE = PASTA_BLOG.replace(/\/blog$/, ''); // /htsolutions

// The site reads these lists; a static file can't answer ?categoria=, so each list gets its own file
const ESPECIAIS = {
  '/ultimos.json?n=3': '/ultimos-todos.json',
  '/ultimos.json?n=3&categoria=cases': '/ultimos-cases.json',
};
const IGNORAR = /^\/(admin|search\.json|feed)\b|[?&]s=/;

const fila = ['/', '/sitemap.xml', ...Object.keys(ESPECIAIS)];
const vistos = new Set(fila);
let salvos = 0;
const falhas = [];

// Root-relative path inside the blog (null for other sites and for the site's own pages)
const caminhoDoBlog = (url, base = '/') => {
  if (!url || /^(data:|mailto:|tel:|#|javascript:)/i.test(url)) return null;
  let u;
  try {
    u = new URL(url.replace(/&amp;/g, '&'), ORIGEM + base);
  } catch {
    return null;
  }
  if (u.origin !== new URL(ORIGEM).origin) return null;
  if (PASTA_SITE && (u.pathname === PASTA_SITE || u.pathname.startsWith(PASTA_SITE + '/'))) return null;
  return u.pathname + u.search;
};

const enfileirar = (url, base) => {
  const c = caminhoDoBlog(url, base);
  if (c && !vistos.has(c) && !IGNORAR.test(c)) {
    vistos.add(c);
    fila.push(c);
  }
};

const arquivoDe = (caminho, html) => {
  if (ESPECIAIS[caminho]) return ESPECIAIS[caminho];
  const p = decodeURIComponent(caminho.split('?')[0]);
  if (p.endsWith('/')) return p + 'index.html';
  return html && !/\.\w+$/.test(p) ? p + '/index.html' : p;
};

// Addresses of the running blog become the public ones; root paths get the blog folder
const reescrever = (texto, html) => {
  // Plain, JSON-escaped (http:\/\/) and URL-encoded (share buttons) forms
  const formas = [(s) => s, (s) => s.replace(/\//g, '\\/'), encodeURIComponent];
  let t = texto;
  for (const f of formas) t = t.split(f(ORIGEM)).join(f(PUBLICO));
  if (!html) return t;
  const prefixar = (url) => (url.startsWith('/') && !url.startsWith('//') && !(PASTA_SITE && url.startsWith(PASTA_SITE + '/')) ? PASTA_BLOG + url : url);
  t = t.replace(/\b(href|src|poster|action|data-suggest)="([^"]*)"/g, (_, a, v) => `${a}="${prefixar(v)}"`);
  t = t.replace(/\bsrcset="([^"]*)"/g, (_, v) => `srcset="${v.split(',').map((c) => c.trim().replace(/^\S+/, prefixar)).join(', ')}"`);
  // No search in a static copy
  t = t.replace(/<form class="search-form"[\s\S]*?<\/form>/g, '');
  return t;
};

async function baixar(caminho) {
  const resposta = await fetch(ORIGEM + caminho, { redirect: 'manual' });
  if (resposta.status >= 300 && resposta.status < 400) {
    enfileirar(resposta.headers.get('location'), caminho);
    return;
  }
  if (!resposta.ok) {
    if (resposta.status !== 404) falhas.push(`${resposta.status} ${caminho}`);
    return;
  }
  const tipo = resposta.headers.get('content-type') || '';
  const html = tipo.includes('text/html');
  const texto = html || /json|xml|css|javascript|text\//.test(tipo);
  const destino = join(saida, arquivoDe(caminho, html));
  mkdirSync(dirname(destino), { recursive: true });

  if (!texto) {
    writeFileSync(destino, Buffer.from(await resposta.arrayBuffer()));
  } else {
    const corpo = await resposta.text();
    if (html) {
      for (const [, , v] of corpo.matchAll(/\b(href|src|poster)="([^"]+)"/g)) enfileirar(v, caminho);
      for (const [, v] of corpo.matchAll(/\bsrcset="([^"]+)"/g)) for (const c of v.split(',')) enfileirar(c.trim().split(/\s+/)[0], caminho);
      for (const [, v] of corpo.matchAll(/<meta[^>]+content="(https?:[^"]+)"/g)) enfileirar(v, caminho);
    } else if (tipo.includes('xml')) {
      for (const [, v] of corpo.matchAll(/<loc>([^<]+)<\/loc>/g)) enfileirar(v, caminho);
    } else if (tipo.includes('css')) {
      for (const [, v] of corpo.matchAll(/url\(['"]?([^'")]+)['"]?\)/g)) enfileirar(v, caminho);
    }
    writeFileSync(destino, reescrever(corpo, html));
  }
  salvos++;
}

// A few requests at a time: the PHP server answers one by one
const trabalhadores = Array.from({ length: 4 }, async () => {
  while (fila.length) {
    const caminho = fila.shift();
    try {
      await baixar(caminho);
    } catch (erro) {
      falhas.push(`${caminho}: ${erro.message}`);
    }
    // The queue may get new items while the last requests finish
    if (!fila.length) await new Promise((r) => setTimeout(r, 50));
  }
});
await Promise.all(trabalhadores);

console.log(`Blog estático: ${salvos} arquivos em ${saida}`);
if (falhas.length) {
  console.log(`Falhas (${falhas.length}):`);
  for (const f of falhas.slice(0, 20)) console.log('  ' + f);
  process.exitCode = 1;
}
