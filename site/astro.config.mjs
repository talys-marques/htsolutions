import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// The blog (blog-system, PHP) lives in /blog/ on the same domain and has its own sitemap.
// GitHub Pages (presentation) sets SITE_URL (https://usuario.github.io) and BASE_PATH (/repositorio).
const site = process.env.SITE_URL || 'https://htsolutions.com.br';
const base = process.env.BASE_PATH || '/';

export default defineConfig({
  site,
  base,
  // Same addresses as the old Angular site (/quem-somos, /servicos...); Apache adds the slash
  trailingSlash: 'always',
  build: { format: 'directory' },
  integrations: [sitemap()],
});
