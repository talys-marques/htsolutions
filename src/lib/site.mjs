// Prefixes internal paths with the base path (empty on the final domain)
const BASE = import.meta.env.BASE_URL.replace(/\/$/, '');
export const url = (path) => (/^(https?:|mailto:|tel:|#)/.test(path) ? path : BASE + path);

export const externo = (href) => /^https?:/.test(href);

// Current page check for menu highlighting; "/" only matches the home page itself.
// Links to a section (/servicos/#servicos) never count, so only the page link is highlighted.
export const ativo = (href, pathname) => {
  const atual = pathname.slice(BASE.length) || '/';
  if (href.includes('#') || externo(href)) return false;
  return href === '/' ? atual === '/' : atual.startsWith(href);
};

// Titles stored as HTML in Strapi ("<p>Conheça o <br>Chrome Enterprise.</p>"): keep <br> and <strong>, drop the <p>
export const semParagrafo = (html = '') => html.replace(/<\/?p>/g, '').trim();

export const dataPorExtenso = (iso) =>
  new Date(iso + 'T12:00:00Z').toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' });
