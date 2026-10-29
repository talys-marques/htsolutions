#!/usr/bin/env node
// Checks the built site (dist/) for internal links and images that point nowhere.
// Run after `npm run build`: `npm run verificar` (with the same BASE_PATH as the build).
// Links into /blog/ are skipped: the blog is the blog-system, outside dist/.

import { readFileSync, readdirSync, existsSync, statSync } from 'node:fs';
import { join } from 'node:path';

const DIST = 'dist';
const BASE = (process.env.BASE_PATH || '').replace(/\/$/, '');

const pages = [];
(function walk(dir) {
  for (const entry of readdirSync(dir, { withFileTypes: true })) {
    const path = join(dir, entry.name);
    if (entry.isDirectory()) walk(path);
    else if (path.endsWith('.html')) pages.push(path);
  }
})(DIST);

const resolves = (url) => {
  let path = decodeURIComponent(url.split('#')[0].split('?')[0]);
  if (BASE && path.startsWith(BASE)) path = path.slice(BASE.length) || '/';
  if (path === '/') return true;
  const file = join(DIST, path);
  return (existsSync(file) && statSync(file).isFile()) || existsSync(join(file, 'index.html'));
};

const broken = new Map();
let total = 0;
for (const page of pages) {
  const html = readFileSync(page, 'utf8');
  for (const [, attr] of html.matchAll(/(?:href|src|srcset)="([^"]+)"/g)) {
    for (const url of attr.split(',').map((s) => s.trim().split(' ')[0])) {
      if (!url.startsWith('/') || url.startsWith('//') || url.startsWith(`${BASE}/blog/`)) continue;
      total++;
      if (!resolves(url)) broken.set(url, [...(broken.get(url) ?? []), page]);
    }
  }
}

console.log(`${pages.length} páginas, ${total} links internos, ${broken.size} quebrados`);
for (const [url, where] of broken) console.log(`  ${url}  (em ${where.length} página(s), ex.: ${where[0]})`);
process.exit(broken.size ? 1 : 0);
