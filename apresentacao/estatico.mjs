#!/usr/bin/env node
// Static copy of the site (blog-system, PHP) for the GitHub Pages presentation, where PHP can't run.
// Crawls the running site and saves pages, images, CSS/JS and the sitemap as plain files.
//
//   node apresentacao/estatico.mjs <site running> <output folder> <public address>
//   node apresentacao/estatico.mjs http://127.0.0.1:8080 _site https://usuario.github.io/htsolutions
//
// Links become absolute with the folder of the public address (/htsolutions/...).
// Search, the panel (/admin) and the warranty lookup data don't exist in the copy.

import { mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';

const [origem, saida, publico] = process.argv.slice(2);
if (!origem || !saida || !publico) {
  console.error('Uso: node apresentacao/estatico.mjs <site no ar> <pasta de saída> <endereço público>');
  process.exit(1);
}
const ORIGEM = origem.replace(/\/$/, '');
const PUBLICO = publico.replace(/\/$/, '');
const PASTA = new URL(PUBLICO).pathname.replace(/\/$/, ''); // /htsolutions
const IGNORAR = /^\/(admin|search\.json|feed)\b|[?&]s=/;

const fila = ['/', '/sitemap.xml'];
const vistos = new Set(fila);
let salvos = 0;
const falhas = [];

// Root path of a link on this site (null for other sites, mailto:, #...)
const caminho = (url, base = '/') => {
  if (!url || /^(data:|mailto:|tel:|#|javascript:)/i.test(url)) return null;
  let u;
  try {
    u = new URL(url.replace(/&amp;/g, '&'), ORIGEM + base);
  } catch {
    return null;
  }
  return u.origin === new URL(ORIGEM).origin ? u.pathname + u.search : null;
};

const enfileirar = (url, base) => {
  const c = caminho(url, base);
  if (c && !vistos.has(c) && !IGNORAR.test(c)) {
    vistos.add(c);
    fila.push(c);
  }
};

const arquivoDe = (c, html) => {
  const p = decodeURIComponent(c.split('?')[0]);
  if (p.endsWith('/')) return p + 'index.html';
  return html && !/\.\w+$/.test(p) ? p + '/index.html' : p;
};

// Running site's address → public one; root paths ("/servicos/") get the folder
const reescrever = (texto, html) => {
  // Plain, JSON-escaped (http:\/\/) and URL-encoded (share buttons) forms
  const formas = [(s) => s, (s) => s.replace(/\//g, '\\/'), encodeURIComponent];
  let t = texto;
  for (const f of formas) t = t.split(f(ORIGEM)).join(f(PUBLICO));
  if (!html) return t;
  const prefixar = (url) => (url.startsWith('/') && !url.startsWith('//') ? PASTA + url : url);
  t = t.replace(/\b(href|src|poster|action|data-suggest|data-garantia)="([^"]*)"/g, (_, a, v) => `${a}="${prefixar(v)}"`);
  t = t.replace(/\bsrcset="([^"]*)"/g, (_, v) => `srcset="${v.split(',').map((c) => c.trim().replace(/^\S+/, prefixar)).join(', ')}"`);
  // No search without PHP
  t = t.replace(/<form class="search-form"[\s\S]*?<\/form>/g, '');
  return t;
};

async function baixar(c, destinoFixo) {
  const resposta = await fetch(ORIGEM + c, { redirect: 'manual' });
  if (resposta.status >= 300 && resposta.status < 400) {
    enfileirar(resposta.headers.get('location'), c);
    return;
  }
  if (!resposta.ok && !destinoFixo) {
    falhas.push(`${resposta.status} ${c}`);
    return;
  }
  const tipo = resposta.headers.get('content-type') || '';
  const html = tipo.includes('text/html');
  const destino = join(saida, destinoFixo || arquivoDe(c, html));
  mkdirSync(dirname(destino), { recursive: true });

  if (!html && !/json|xml|css|javascript|text\//.test(tipo)) {
    writeFileSync(destino, Buffer.from(await resposta.arrayBuffer()));
  } else {
    const corpo = await resposta.text();
    if (html) {
      for (const [, , v] of corpo.matchAll(/\b(href|src|poster)="([^"]+)"/g)) enfileirar(v, c);
      for (const [, v] of corpo.matchAll(/\bsrcset="([^"]+)"/g)) for (const x of v.split(',')) enfileirar(x.trim().split(/\s+/)[0], c);
      for (const [, v] of corpo.matchAll(/<meta[^>]+content="(https?:[^"]+)"/g)) enfileirar(v, c);
    } else if (tipo.includes('xml')) {
      for (const [, v] of corpo.matchAll(/<loc>([^<]+)<\/loc>/g)) enfileirar(v, c);
    } else if (tipo.includes('css')) {
      for (const [, v] of corpo.matchAll(/url\(['"]?([^'")]+)['"]?\)/g)) enfileirar(v, c);
    }
    writeFileSync(destino, reescrever(corpo, html));
  }
  salvos++;
}

// A few requests at a time: the PHP built-in server answers one by one
await Promise.all(
  Array.from({ length: 4 }, async () => {
    while (fila.length) {
      const c = fila.shift();
      try {
        await baixar(c);
      } catch (erro) {
        falhas.push(`${c}: ${erro.message}`);
      }
      // The queue may get new items while the last requests finish
      if (!fila.length) await new Promise((r) => setTimeout(r, 50));
    }
  }),
);
// Not-found page: GitHub Pages serves 404.html for any missing address
await baixar('/pagina-inexistente-404/', '404.html');

console.log(`Site estático: ${salvos} arquivos em ${saida}`);
if (falhas.length) {
  console.log(`Links quebrados ou falhas (${falhas.length}):`);
  for (const f of falhas.slice(0, 30)) console.log('  ' + f);
  process.exitCode = 1;
}
