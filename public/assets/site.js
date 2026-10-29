/*
 * Site público — JavaScript puro, sem dependências nem build.
 * Carregado no <head> sem defer, para aplicar o tema antes de a página
 * aparecer (sem piscar no modo errado). O site funciona sem ele.
 */
(() => {
  'use strict';

  /* ===================================================== Modo claro/escuro */

  // A escolha fica no localStorage do visitante (não é cookie). Sem escolha, segue o sistema.
  const root = document.documentElement;
  const system = window.matchMedia('(prefers-color-scheme: dark)');
  const systemTheme = () => (system.matches ? 'dark' : 'light');
  const storage = (() => { try { return window.localStorage; } catch { return null; } })();

  const apply = () => {
    const saved = storage?.getItem('theme');
    root.dataset.theme = saved === 'light' || saved === 'dark' ? saved : systemTheme();
  };

  apply();
  system.addEventListener('change', apply);

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.theme-toggle')) return;
    const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
    try {
      // Voltou ao mesmo modo do sistema: esquece a escolha e volta a seguir o sistema.
      if (next === systemTheme()) storage?.removeItem('theme');
      else storage?.setItem('theme', next);
    } catch { /* navegação privada sem armazenamento: vale só nesta página */ }
    root.dataset.theme = next;
  });

  /* ===================================================== Submenus do topo */

  // Com mouse, a lista abre ao passar por cima (site.css) e o clique segue o link do item.
  // No toque, e em itens sem link, o clique abre e fecha a lista.
  const closeMenus = (except) => document.querySelectorAll('.nav-sub.is-open').forEach((sub) => {
    if (sub === except) return;
    sub.classList.remove('is-open');
    sub.querySelector('.nav-sub-toggle')?.setAttribute('aria-expanded', 'false');
  });
  let pointer = '';
  document.addEventListener('pointerdown', (event) => { pointer = event.pointerType; });
  document.addEventListener('click', (event) => {
    const sub = event.target.closest('.nav-sub');
    closeMenus(sub);
    const toggle = event.target.closest('.nav-sub-toggle');
    // detail 0 = Enter no teclado: o link abre normalmente (a lista já aparece com o foco).
    if (!toggle || (toggle.tagName === 'A' && (pointer === 'mouse' || event.detail === 0))) return;
    event.preventDefault();
    toggle.setAttribute('aria-expanded', String(sub.classList.toggle('is-open')));
  });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeMenus(null); });

  // Lista que passaria da borda direita da tela é puxada para a esquerda.
  const fitMenu = (event) => {
    const list = event.target.closest?.('.nav-sub')?.querySelector('.nav-sub-list');
    if (!list) return;
    list.style.translate = '';
    const over = list.getBoundingClientRect().right - (root.clientWidth - 12);
    if (over > 0) list.style.translate = `${-Math.ceil(over)}px 0`;
  };
  document.addEventListener('pointerover', fitMenu);
  document.addEventListener('focusin', fitMenu);

  /* ===================================================== Sugestões da busca */

  // Formulários com data-suggest (theme/) mostram posts, categorias e tags enquanto o visitante digita.
  // Os dados vêm de /search.json, a mesma busca da página de resultados.
  const fold = (text) => text.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();

  // Texto com os termos buscados em <mark>, ignorando acentos e maiúsculas como a busca do servidor.
  // Só marca no começo das palavras: "ar" destaca "ar-condicionado", mas não "cuidar".
  const highlight = (text, terms) => {
    let folded = '';
    const origin = []; // posição no texto sem acentos → posição no original
    for (let i = 0; i < text.length; i++) {
      const f = fold(text[i]);
      folded += f;
      for (let j = 0; j < f.length; j++) origin.push(i);
    }
    const marked = new Array(text.length).fill(false);
    for (const term of terms) {
      for (let at = folded.indexOf(term); at !== -1; at = folded.indexOf(term, at + 1)) {
        if (at > 0 && /[\p{L}\p{N}]/u.test(folded[at - 1])) continue;
        for (let i = origin[at]; i <= origin[at + term.length - 1]; i++) marked[i] = true;
      }
    }
    const out = document.createDocumentFragment();
    for (let start = 0, end = 0; start < text.length; start = end) {
      while (end < text.length && marked[end] === marked[start]) end++;
      const piece = text.slice(start, end);
      if (marked[start]) {
        const mark = document.createElement('mark');
        mark.textContent = piece;
        out.append(mark);
      } else {
        out.append(piece);
      }
    }
    return out;
  };

  let suggestCount = 0;

  const setupSuggest = (form) => {
    const input = form.querySelector('input[name=s]');
    if (!input) return;
    const id = `suggest-${++suggestCount}`;
    const box = document.createElement('div');
    box.className = 'suggest';
    box.id = id;
    box.hidden = true;
    box.setAttribute('role', 'listbox');
    box.setAttribute('aria-label', 'Sugestões da busca');
    form.append(box);
    input.autocomplete = 'off'; // o histórico do navegador ficaria por cima das sugestões
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('aria-controls', id);
    input.setAttribute('aria-expanded', 'false');

    const cache = new Map();
    let timer = 0;
    let controller = null;
    let active = -1;

    const options = () => [...box.querySelectorAll('[role=option]:not([aria-disabled])')];

    const select = (index) => {
      const list = options();
      list.forEach((el, i) => el.classList.toggle('is-active', i === index));
      active = list[index] ? index : -1;
      if (list[index]) {
        input.setAttribute('aria-activedescendant', list[index].id);
        list[index].scrollIntoView({ block: 'nearest' });
      } else {
        input.removeAttribute('aria-activedescendant');
      }
    };

    const open = (show) => {
      box.hidden = !show;
      input.setAttribute('aria-expanded', String(show));
      if (!show) select(-1);
    };

    const option = (item, terms, className) => {
      const a = document.createElement('a');
      a.className = className;
      a.href = item.url;
      a.id = `${id}-${box.children.length}`;
      a.tabIndex = -1;
      a.setAttribute('role', 'option');
      const title = document.createElement('span');
      title.className = 'suggest-title';
      title.append(highlight(item.title, terms));
      a.append(title);
      if (item.meta) {
        const meta = document.createElement('span');
        meta.className = 'suggest-meta';
        meta.textContent = item.meta;
        a.append(meta);
      }
      box.append(a);
    };

    const render = (query, data) => {
      box.replaceChildren();
      const terms = fold(query).split(/[^\p{L}\p{N}]+/u).filter((t) => t.length >= 2);
      [...data.posts, ...data.terms].forEach((item) => option(item, terms, 'suggest-item'));
      if (data.total > data.posts.length) {
        const all = new URL(form.action);
        all.searchParams.set('s', query);
        option({ url: all.href, title: `Ver todos os ${data.total} resultados` }, [], 'suggest-all');
      }
      if (!box.children.length) {
        const empty = document.createElement('p');
        empty.className = 'suggest-empty';
        empty.setAttribute('role', 'option');
        empty.setAttribute('aria-disabled', 'true');
        empty.textContent = `Nada encontrado para “${query}”.`;
        box.append(empty);
      }
      select(-1);
      open(true);
    };

    const load = () => {
      const query = input.value.trim();
      controller?.abort();
      if (query.length < 2) {
        box.replaceChildren();
        open(false);
        return;
      }
      if (cache.has(query)) {
        render(query, cache.get(query));
        return;
      }
      controller = new AbortController();
      const url = new URL(form.dataset.suggest, location.href);
      url.searchParams.set('s', query);
      fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } })
        .then((res) => (res.ok ? res.json() : Promise.reject(res)))
        .then((data) => {
          cache.set(query, data);
          if (input.value.trim() === query && document.activeElement === input) render(query, data);
        })
        .catch(() => { /* cancelado ou sem conexão: Enter continua levando à página de resultados */ });
    };

    input.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(load, 150);
    });

    input.addEventListener('keydown', (event) => {
      const list = options();
      if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        if (!box.children.length) return;
        event.preventDefault();
        open(true);
        // Fora da lista, ↓ vai para o primeiro e ↑ para o último; passando da ponta, volta ao campo.
        const next = active + (event.key === 'ArrowDown' ? 1 : -1);
        select(next < -1 ? list.length - 1 : next >= list.length ? -1 : next);
      } else if (event.key === 'Enter' && !box.hidden && list[active]) {
        event.preventDefault();
        location.href = list[active].href;
      } else if (event.key === 'Escape' && !box.hidden) {
        event.preventDefault(); // sem isso, o campo de busca também apagaria o texto
        open(false);
      }
    });

    // O clique numa sugestão não tira o foco do campo, então a lista não fecha antes de o link abrir.
    box.addEventListener('mousedown', (event) => event.preventDefault());
    input.addEventListener('blur', () => open(false));
    input.addEventListener('focus', () => { if (box.children.length) open(true); });
    window.addEventListener('pagehide', () => open(false)); // ao voltar para a página, a lista não aparece aberta
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-suggest]').forEach(setupSuggest);
  });
})();
