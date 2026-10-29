/*
 * Painel do blog — JavaScript puro, sem dependências nem build.
 *
 * Seções: utilidades · formulários · envio de arquivos · seletor de mídia ·
 * campos de imagem · categorias/tags · atalhos das seções · prévia de fontes · estilo · página inicial · prévia do site · redes sociais · menu ·
 * editor visual · SEO · rascunho local ·
 * página de mídia
 */
(() => {
  'use strict';

  /* ===================================================== Utilidades */

  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
  const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content || '';
  const CSRF = meta('csrf-token');
  const BASE = meta('base-path') || '/';

  const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  const slugify = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase()
    .replace(/&/g, ' e ').replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 120);

  /** Mesmo HTML gerado por video_embed() no PHP. */
  const videoEmbed = (url) => {
    let m = url.match(/(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:.*&)?v=|embed\/|shorts\/|live\/)|youtu\.be\/)([\w-]{11})/i);
    let src = m && `https://www.youtube-nocookie.com/embed/${m[1]}`;
    if (!src && (m = url.match(/vimeo\.com\/(?:video\/)?(\d+)/i))) src = `https://player.vimeo.com/video/${m[1]}`;
    if (!src) return null;
    return `<figure class="embed"><iframe src="${src}" title="Vídeo" loading="lazy" allowfullscreen allow="accelerometer; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe></figure>`;
  };

  /* ===================================================== Formulários */

  let dirty = false;
  const markDirty = () => { dirty = true; };

  document.addEventListener('submit', (ev) => {
    const message = ev.submitter?.dataset.confirm || ev.target.dataset.confirm;
    if (message && !confirm(message)) {
      ev.preventDefault();
      return;
    }
    dirty = false;
    const busy = ev.submitter?.dataset.busy;
    if (busy) {
      setTimeout(() => { ev.submitter.disabled = true; ev.submitter.textContent = busy; }, 0);
    }
  });

  window.addEventListener('beforeunload', (ev) => {
    if (dirty) { ev.preventDefault(); ev.returnValue = ''; }
  });

  // Tarefa longa feita em partes: a página de cada parte já envia a seguinte.
  $$('form[data-autosubmit]').forEach((form) => setTimeout(() => form.submit(), 300));

  // "Voltar" das telas de erro (o CSP do painel não deixa usar href="javascript:...").
  $$('[data-back]').forEach((link) => link.addEventListener('click', (ev) => {
    if (history.length > 1) { ev.preventDefault(); history.back(); }
  }));

  /* ===================================================== Envio de arquivos */

  async function upload(files) {
    const body = new FormData();
    [...files].forEach((file) => body.append('files[]', file));
    const res = await fetch(BASE + 'admin/media/upload', {
      method: 'POST', body, credentials: 'same-origin',
      headers: { 'X-CSRF-Token': CSRF, Accept: 'application/json' },
    });
    let data;
    try { data = await res.json(); } catch { throw new Error('O servidor não respondeu como esperado. O arquivo pode ser grande demais.'); }
    if (!data.files?.length) throw new Error(data.error || 'Nenhum arquivo foi enviado.');
    if (data.error) alert(data.error);
    return data.files;
  }

  /* ===================================================== Seletor de mídia */

  /** Abre a biblioteca de mídia. Resolve com o arquivo escolhido ou null. */
  function pickMedia({ images = true } = {}) {
    return new Promise((resolve) => {
      const dlg = document.createElement('dialog');
      let done = false;
      const finish = (value) => {
        if (done) return;
        done = true;
        resolve(value);
        if (dlg.open) dlg.close();
        dlg.remove();
      };
      dlg.className = 'picker';
      dlg.innerHTML = `
        <div class="picker-head">
          <strong>Escolher ${images ? 'imagem' : 'arquivo'}</strong>
          <input type="search" placeholder="Buscar…" aria-label="Buscar">
          <label class="btn btn-primary btn-sm">Enviar do computador<input type="file" hidden ${images ? 'accept="image/*"' : ''}></label>
          <button type="button" class="btn btn-ghost btn-sm" data-close>Fechar</button>
        </div>
        <div class="picker-grid"></div>
        <div class="picker-foot"><button type="button" class="btn btn-ghost btn-sm" data-more hidden>Carregar mais</button></div>`;
      document.body.append(dlg);

      const grid = $('.picker-grid', dlg);
      const more = $('[data-more]', dlg);
      let page = 1;
      let query = '';

      const load = async (reset) => {
        if (reset) { page = 1; grid.innerHTML = '<p class="muted">Carregando…</p>'; }
        const params = new URLSearchParams({ page, q: query, images: images ? '1' : '' });
        try {
          const res = await fetch(BASE + 'admin/media/list?' + params, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
          const data = await res.json();
          if (reset) grid.innerHTML = '';
          if (!data.files?.length && page === 1) grid.innerHTML = '<p class="muted">Nenhum arquivo ainda. Envie o primeiro!</p>';
          (data.files || []).forEach((file) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'picker-item';
            item.title = file.name;
            item.innerHTML = file.is_image ? `<img src="${escapeHtml(file.url)}" alt="" loading="lazy">` : `<span class="file-icon">${escapeHtml(file.ext)}</span>`;
            item.addEventListener('click', () => finish(file));
            grid.append(item);
          });
          more.hidden = page * (data.per_page || 40) >= (data.total || 0);
        } catch {
          grid.innerHTML = '<p class="muted">Não foi possível carregar a biblioteca.</p>';
        }
      };

      let timer;
      $('input[type=search]', dlg).addEventListener('input', (e) => {
        clearTimeout(timer);
        timer = setTimeout(() => { query = e.target.value; load(true); }, 250);
      });
      more.addEventListener('click', () => { page++; load(false); });
      $('input[type=file]', dlg).addEventListener('change', async (e) => {
        try { finish((await upload(e.target.files))[0]); } catch (err) { alert(err.message); }
      });
      $('[data-close]', dlg).addEventListener('click', () => finish(null));
      dlg.addEventListener('close', () => finish(null)); // Esc
      dlg.showModal();
      load(true);
    });
  }

  /* ===================================================== Campos de imagem (capa, logo) */

  function initImageField(box) {
    const input = $('input[type=hidden]', box);
    const preview = $('.image-preview', box);
    const remove = $('[data-image-remove]', box);
    const render = () => {
      preview.innerHTML = input.value ? `<img src="${escapeHtml(input.value)}" alt="">` : '';
      remove.hidden = !input.value;
    };
    // Campo oculto não avisa sozinho quando muda (a prévia da página inicial escuta 'change').
    const changed = () => { render(); markDirty(); input.dispatchEvent(new Event('change', { bubbles: true })); };
    $('[data-image-pick]', box).addEventListener('click', async () => {
      const file = await pickMedia();
      if (file) { input.value = file.url; changed(); }
    });
    remove.addEventListener('click', () => { input.value = ''; changed(); });
    render();
  }
  $$('[data-image-field]').forEach(initImageField);

  /* ===================================================== Categorias e tags */

  $$('[data-terms]').forEach((box) => {
    const input = $('input', box);
    const list = () => input.value.split(',').map((s) => s.trim()).filter(Boolean);
    const sync = () => {
      const current = list().map((s) => s.toLowerCase());
      $$('.chip', box).forEach((chip) => chip.classList.toggle('active', current.includes(chip.dataset.name.toLowerCase())));
    };
    $$('.chip', box).forEach((chip) => chip.addEventListener('click', () => {
      const items = list();
      const i = items.findIndex((s) => s.toLowerCase() === chip.dataset.name.toLowerCase());
      if (i >= 0) items.splice(i, 1); else items.push(chip.dataset.name);
      input.value = items.join(', ');
      sync();
      markDirty();
    }));
    input.addEventListener('input', sync);
    sync();
  });

  /* ===================================================== Atalhos das seções (Configurações) */

  // Rola até a seção sem mudar o endereço (salvar volta ao topo, onde aparece o aviso) e marca a seção atual.
  $$('[data-jump]').forEach((nav) => {
    const links = $$('a', nav);
    const sections = links.map((a) => document.getElementById(a.hash.slice(1)));
    let current = null;
    let picked = null; // seção escolhida no atalho: fica marcada até a rolagem parar
    let timer;

    const mark = (section) => {
      if (section === current) return;
      current = section;
      links.forEach((a, i) => {
        const on = sections[i] === section;
        a.classList.toggle('active', on);
        if (on) a.setAttribute('aria-current', 'location'); else a.removeAttribute('aria-current');
        // No celular a barra rola de lado: mantém o atalho marcado à vista.
        if (on && nav.scrollWidth > nav.clientWidth) nav.scrollTo({ left: a.offsetLeft - (nav.clientWidth - a.offsetWidth) / 2 });
      });
    };
    // A atual é a última que já passou de 1/4 da tela; no fim da página, a última (pode ser curta demais para subir).
    const update = () => {
      const end = window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4;
      mark(end ? sections.at(-1) : sections.filter((s) => s.getBoundingClientRect().top <= window.innerHeight / 4).at(-1) || sections[0]);
    };

    window.addEventListener('scroll', () => {
      clearTimeout(timer);
      if (picked) timer = setTimeout(() => { picked = null; }, 150);
      else requestAnimationFrame(update);
    }, { passive: true });

    links.forEach((a, i) => a.addEventListener('click', (ev) => {
      ev.preventDefault();
      picked = sections[i];
      mark(picked);
      clearTimeout(timer);
      timer = setTimeout(() => { picked = null; }, 150); // caso já esteja na posição e nada role
      const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
      picked.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth' });
      picked.focus({ preventScroll: true }); // o Tab segue a partir da seção
    }));

    update();
  });

  /* ===================================================== Prévia de fontes (Configurações) */

  // As variáveis ficam no formulário: a amostra e as miniaturas de Estilo usam as fontes escolhidas.
  $$('[data-font-preview]').forEach((preview) => {
    const form = preview.closest('form');
    const text = $('[data-font-select=text]', form);
    const heading = $('[data-font-select=heading]', form);
    const stack = (select) => select.selectedOptions[0]?.dataset.stack || '';
    // Peso dos títulos (Estilo): o da miniatura marcada.
    const weight = () => $('[name="tokens[heading_weight]"]:checked + .choice-thumb .swatch', form)?.style.getPropertyValue('--heading-weight') || '';
    const render = () => {
      form.style.setProperty('--preview-text', stack(text));
      form.style.setProperty('--preview-heading', stack(heading) || stack(text)); // vazio = igual ao texto
      form.style.setProperty('--preview-weight', weight());
    };
    form.addEventListener('change', render);
    render();
  });

  /* ===================================================== Cor de destaque (Configurações) */

  // Mesmo que Admin::cleanColor(): "#2F5BEA", "2f5bea" ou "#25b" → "#2f5bea"; null se não for cor.
  const toHex = (value) => {
    let hex = value.trim().replace(/^#+/, '').toLowerCase();
    if (/^[0-9a-f]{3}$/.test(hex)) hex = hex.replace(/./g, '$&$&');
    return /^[0-9a-f]{6}$/.test(hex) ? `#${hex}` : null;
  };

  // O texto (hex) é o que vai no formulário e aceita colar; o seletor só acompanha.
  $$('[data-color-input]').forEach((box) => {
    const text = $('input[type=text]', box);
    const picker = $('input[type=color]', box);
    text.addEventListener('input', () => {
      const hex = toHex(text.value);
      if (hex) picker.value = hex;
    });
    text.addEventListener('change', () => { text.value = toHex(text.value) || text.value; });
    picker.addEventListener('input', () => {
      text.value = picker.value;
      text.dispatchEvent(new Event('input', { bubbles: true })); // aviso de contraste e prévia
    });
  });

  // Mesma conta de contrast_ratio() (helpers.php): abaixo de 4.5:1 sobre branco, os links ficam difíceis de ler.
  $$('[data-accent-warning]').forEach((warning) => {
    const input = $('input[name=accent_color]', warning.parentElement);
    const luminance = (hex) => [1, 3, 5]
      .map((i) => parseInt(hex.substr(i, 2), 16) / 255)
      .map((c) => (c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4))
      .reduce((sum, c, i) => sum + c * [0.2126, 0.7152, 0.0722][i], 0);
    // Cor incompleta (ainda digitando): sem aviso.
    const check = () => { const hex = toHex(input.value); warning.hidden = !hex || 1.05 / (luminance(hex) + 0.05) >= 4.5; };
    input.addEventListener('input', check);
    check();
  });

  /* ===================================================== Estilo (Configurações) */

  $$('[data-tokens-reset]').forEach((button) => button.addEventListener('click', () => {
    const form = button.closest('form');
    $$('[data-token-default]', form).forEach((group) => {
      const input = $(`input[value="${group.dataset.tokenDefault}"]`, group);
      if (input) input.checked = true;
    });
    form.dispatchEvent(new Event('change')); // atualiza amostras e prévia
  }));

  /* ===================================================== Página inicial (Configurações) */

  // "Página dos posts" só vale quando o início é uma página fixa; a frase de apresentação, quando não é.
  $$('[data-home-page]').forEach((select) => {
    const form = select.closest('form');
    const update = () => {
      $('[data-blog-page]', form).hidden = select.value === '0';
      $('[data-home-intro]', form).hidden = select.value !== '0';
    };
    select.addEventListener('change', update);
    update();
  });

  // Estrutura: mostra só os ajustes que valem para a escolhida.
  $$('[data-layout-picker]').forEach((picker) => {
    const form = picker.closest('form');
    const description = $('[data-layout-description]', form);
    const update = () => {
      const current = $('input:checked', picker);
      if (!current) return;
      const options = current.dataset.options.split(' ');
      $$('[data-home-option]', form).forEach((el) => { el.hidden = !options.includes(el.dataset.homeOption); });
      description.textContent = current.dataset.description;
    };
    picker.addEventListener('change', update);
    update();
  });

  /* ===================================================== Prévia do site (Configurações) */

  // Envia o formulário (sem salvar) e mostra a página resultante num iframe
  // reduzido, na largura de um computador ou de um celular.
  $$('[data-site-preview]').forEach((box) => {
    const form = box.closest('form');
    const stage = $('.site-preview-stage', box);
    const frame = $('iframe', stage);
    const status = $('[data-preview-status]', box);
    const widths = { desktop: 1280, mobile: 390 };
    let timer = 0;
    let controller = null;
    let visible = false;
    let stale = true;

    const fit = () => {
      const width = widths[box.dataset.device];
      const scale = Math.min(1, stage.clientWidth / width);
      Object.assign(frame.style, {
        width: `${width}px`,
        height: `${stage.clientHeight / scale}px`,
        transform: `scale(${scale})`,
        marginLeft: `${Math.max(0, (stage.clientWidth - width * scale) / 2)}px`,
      });
    };

    const refresh = async () => {
      stale = false;
      controller?.abort();
      controller = new AbortController();
      box.classList.add('is-loading');
      status.textContent = 'Atualizando…';
      try {
        const res = await fetch(box.dataset.sitePreview, { method: 'POST', body: new FormData(form), signal: controller.signal });
        const html = await res.text();
        const y = frame.contentWindow?.scrollY || 0;
        frame.addEventListener('load', () => {
          frame.contentWindow?.scrollTo(0, y); // continua no mesmo ponto da página
          box.classList.remove('is-loading');
          status.textContent = '';
        }, { once: true });
        frame.srcdoc = html;
      } catch (err) {
        if (err.name === 'AbortError') return;
        box.classList.remove('is-loading');
        status.textContent = 'Não foi possível atualizar a prévia.';
      }
    };

    const schedule = () => {
      stale = true;
      clearTimeout(timer);
      if (visible) timer = setTimeout(refresh, 400);
    };

    form.addEventListener('input', schedule);
    form.addEventListener('change', schedule);
    $$('[data-preview-device]', box).forEach((button) => button.addEventListener('click', () => {
      box.dataset.device = button.dataset.previewDevice;
      $$('[data-preview-device]', box).forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
      fit();
    }));
    new ResizeObserver(fit).observe(stage);
    // Só busca a prévia quando ela está na tela: digitar em outros campos não gera pedidos à toa.
    new IntersectionObserver(([entry]) => {
      visible = entry.isIntersecting;
      if (visible && stale) refresh();
    }).observe(box);
  });

  /* ===================================================== Redes sociais (Configurações) */

  $$('[data-social]').forEach((box) => {
    const list = $('[data-social-list]', box);
    const add = $('[data-social-add]', box);
    // Cada rede entra uma vez: fica desativada no seletor enquanto está na lista.
    const sync = () => {
      const used = $$('[data-social-row]', list).map((row) => row.dataset.socialRow);
      [...add.options].forEach((opt) => { opt.disabled = used.includes(opt.value); });
    };
    const bind = (row) => {
      const input = $('input[name="social_user[]"]', row);
      // Link completo (ex.: página de empresa): esconde o "instagram.com/" da frente.
      const checkUrl = () => row.classList.toggle('is-url', /^https?:\/\//i.test(input.value.trim()));
      input.addEventListener('input', () => {
        if (/^\s*@/.test(input.value)) input.value = input.value.replace(/^\s*@/, ''); // o "@" já vem no endereço
        checkUrl();
      });
      checkUrl();
      $('[data-social-remove]', row).addEventListener('click', () => { row.remove(); sync(); markDirty(); });
    };
    $$('[data-social-row]', list).forEach(bind);
    add.addEventListener('change', () => {
      const template = $(`template[data-social-template="${add.value}"]`, box);
      add.value = '';
      if (!template) return;
      const row = template.content.firstElementChild.cloneNode(true);
      list.append(row);
      bind(row);
      sync();
      markDirty();
      $('input[name="social_user[]"]', row).focus();
    });
    sync();
  });

  /* ===================================================== Menu (Configurações) */

  // Lista de itens (_menu-row.php) com um nível de subitens: cada subitem pertence ao
  // item do primeiro nível acima dele. O seletor busca as opções em data-menu (Menu::search()).
  $$('[data-menu]').forEach((box) => {
    const list = $('[data-menu-list]', box);
    const adder = $('.menu-add', box);
    const results = $('[data-menu-results]', box);
    const search = $('[data-menu-search]', box);
    const more = $('[data-menu-more]', box);
    const linkLabel = $('[data-menu-link-label]', box);
    const linkUrl = $('[data-menu-link-url]', box);
    const linkNewTab = $('[data-menu-link-newtab]', box);
    let tab = 'page';
    let counter = 0;

    const rows = () => $$('[data-menu-row]', list);
    const field = (row, name) => $(`[data-menu-${name}]`, row);
    const isSub = (row) => field(row, 'sub').value === '1';
    // O item e os subitens dele, que andam juntos.
    const group = (row) => {
      const out = [row];
      for (let next = row.nextElementSibling; !isSub(row) && next && isSub(next); next = next.nextElementSibling) out.push(next);
      return out;
    };
    const inMenu = (type, target) => rows().some((row) => field(row, 'type').value === type && field(row, 'target').value === target);
    const markResults = () => $$('.menu-result', results).forEach((button) => {
      const added = inMenu(button.dataset.type, button.dataset.target);
      button.setAttribute('aria-disabled', String(added));
      button.title = added ? 'Já está no menu' : 'Adicionar ao menu';
    });

    const sync = () => {
      rows().forEach((row, i) => {
        const type = field(row, 'type').value;
        if (i === 0 || type === 'categories') field(row, 'sub').value = ''; // o primeiro não tem onde entrar; "Categorias" já traz os subitens
        const sub = isSub(row);
        const prev = row.previousElementSibling;
        row.classList.toggle('is-sub', sub);
        $('[data-menu-move=up]', row).disabled = !prev || (sub && !prev.previousElementSibling);
        $('[data-menu-move=down]', row).disabled = !group(row).at(-1).nextElementSibling;
        const indent = field(row, 'indent');
        indent.textContent = sub ? '←' : '→';
        indent.title = sub ? 'Voltar para o primeiro nível' : 'Colocar dentro do item de cima';
        indent.setAttribute('aria-label', indent.title);
        indent.disabled = !sub && (!prev || type === 'categories');
        const noUrl = type === 'categories' || (type === 'link' && field(row, 'target').value.trim() === '');
        field(row, 'newtab').hidden = noUrl; // sem link próprio, não há o que abrir em nova aba
        if (type === 'link') {
          const note = field(row, 'note');
          note.textContent = 'Sem endereço: é só um título que abre os subitens.';
          note.hidden = !noUrl;
        }
      });
      markResults();
    };
    const changed = () => {
      sync();
      markDirty();
      list.dispatchEvent(new Event('change', { bubbles: true })); // atualiza a prévia do site
    };

    const move = (row, dir) => {
      const prev = row.previousElementSibling;
      if (isSub(row)) {
        // Subitem anda um passo; passando do item de cima, entra no grupo vizinho.
        if (dir === 'up' && prev?.previousElementSibling) prev.before(row);
        if (dir === 'down') row.nextElementSibling?.after(row);
        return;
      }
      if (dir === 'up') {
        let target = prev;
        while (target && isSub(target)) target = target.previousElementSibling;
        target?.before(...group(row));
      } else {
        const next = group(row).at(-1).nextElementSibling;
        if (next) group(next).at(-1).after(...group(row));
      }
    };

    list.addEventListener('click', (ev) => {
      const button = ev.target.closest('button');
      const row = button?.closest('[data-menu-row]');
      if (!row) return;
      if (button.matches('[data-menu-remove]')) {
        group(row).slice(1).forEach((child) => { field(child, 'sub').value = ''; }); // os subitens sobem um nível
        row.remove();
        changed();
        return;
      }
      if (button.matches('[data-menu-indent]')) field(row, 'sub').value = isSub(row) ? '' : '1';
      else move(row, button.dataset.menuMove);
      changed();
      // Mover tira o foco do botão: devolve, ou passa para outra seta se ele ficou desativado.
      (button.disabled ? $('[data-menu-move]:not(:disabled)', row) : button)?.focus();
    });
    list.addEventListener('input', (ev) => { if (ev.target.matches('[data-menu-target]')) sync(); });

    // Arrastar pela alça. Um item do primeiro nível leva os subitens junto e não cai no meio de outro grupo.
    let dragging = null;
    let moving = [];
    list.addEventListener('pointerdown', (ev) => {
      const handle = ev.target.closest('[data-menu-handle]');
      if (handle) handle.closest('[data-menu-row]').draggable = true; // só pela alça: o texto dos campos continua selecionável
    });
    list.addEventListener('pointerup', () => rows().forEach((row) => { row.draggable = false; }));
    list.addEventListener('dragstart', (ev) => {
      dragging = ev.target.closest('[data-menu-row]');
      if (!dragging) return;
      moving = group(dragging);
      moving.forEach((row) => row.classList.add('is-dragging'));
      ev.dataTransfer.effectAllowed = 'move';
      ev.dataTransfer.setData('text/plain', '');
    });
    list.addEventListener('dragover', (ev) => {
      if (!dragging) return;
      ev.preventDefault();
      let over = ev.target.closest('[data-menu-row]');
      if (!over || moving.includes(over)) return;
      let target = [over];
      if (!isSub(dragging)) {
        while (isSub(over) && over.previousElementSibling) over = over.previousElementSibling;
        target = group(over);
      }
      const top = target[0].getBoundingClientRect().top;
      const bottom = target.at(-1).getBoundingClientRect().bottom;
      if (ev.clientY > (top + bottom) / 2) target.at(-1).after(...moving);
      else target[0].before(...moving);
    });
    list.addEventListener('dragend', () => {
      if (!dragging) return;
      moving.forEach((row) => row.classList.remove('is-dragging'));
      dragging.draggable = false;
      dragging = null;
      changed();
    });

    const addRow = (item) => {
      const template = $(`template[data-menu-template="${item.type === 'link' ? 'link' : 'item'}"]`, box);
      const row = template.content.firstElementChild.cloneNode(true);
      const key = `new${counter++}`;
      $$('[name]', row).forEach((el) => { el.name = el.name.replace('__i__', key); });
      field(row, 'type').value = item.type;
      field(row, 'type-label').textContent = item.type_label;
      field(row, 'target').value = item.target;
      field(row, 'label').value = item.label || '';
      field(row, 'label').placeholder = item.auto || 'Nome no menu';
      $('input', field(row, 'newtab')).checked = !!item.new_tab;
      if (item.type !== 'link') field(row, 'title').textContent = item.title;
      field(row, 'note').textContent = item.note;
      field(row, 'note').hidden = !item.note;
      list.append(row);
      changed();
      row.classList.add('is-new');
      setTimeout(() => row.classList.remove('is-new'), 1500);
    };

    // Seletor: uma aba por tipo; Link tem campos próprios.
    const result = (item) => {
      const li = document.createElement('li');
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'menu-result';
      button.dataset.type = item.type;
      button.dataset.target = item.target;
      button.innerHTML = `<span class="menu-result-title">${escapeHtml(item.title)}</span><span class="menu-result-hint">${escapeHtml(item.hint)}</span>`;
      button.addEventListener('click', () => { if (button.getAttribute('aria-disabled') !== 'true') addRow(item); });
      li.append(button);
      return li;
    };
    const message = (text) => {
      const li = document.createElement('li');
      li.className = 'menu-results-empty';
      li.textContent = text;
      return li;
    };
    let controller = null;
    let timer = 0;
    const load = async () => {
      controller?.abort();
      controller = new AbortController();
      const query = search.value.trim();
      try {
        const res = await fetch(`${box.dataset.menu}?${new URLSearchParams({ type: tab, q: query })}`, {
          credentials: 'same-origin', headers: { Accept: 'application/json' }, signal: controller.signal,
        });
        const data = await res.json();
        results.replaceChildren(...(data.items.length ? data.items.map(result) : [message(query ? 'Nada encontrado.' : 'Nada por aqui ainda.')]));
        more.hidden = !data.more;
        markResults();
      } catch (err) {
        if (err.name !== 'AbortError') results.replaceChildren(message('Não foi possível carregar a lista.'));
      }
    };

    $$('[data-menu-tab]', box).forEach((button) => button.addEventListener('click', () => {
      tab = button.dataset.menuTab;
      $$('[data-menu-tab]', box).forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
      $('[data-menu-pane=search]', box).hidden = tab === 'link';
      $('[data-menu-pane=link]', box).hidden = tab !== 'link';
      if (tab === 'link') return;
      search.value = '';
      search.placeholder = tab === 'page' || tab === 'post' ? 'Buscar pelo título…' : 'Buscar pelo nome…';
      results.replaceChildren();
      load();
    }));
    search.addEventListener('input', () => {
      clearTimeout(timer);
      timer = setTimeout(load, 200);
    });

    const addLink = () => {
      const label = linkLabel.value.trim();
      const url = linkUrl.value.trim();
      if (!label && !url) { linkLabel.focus(); return; }
      addRow({ type: 'link', type_label: 'Link', target: url, label, new_tab: linkNewTab.checked, title: url, auto: '', note: '' });
      linkLabel.value = '';
      linkUrl.value = '';
      linkNewTab.checked = false;
      linkLabel.focus();
    };
    $('[data-menu-link-add]', box).addEventListener('click', addLink);

    // Os campos do seletor não fazem parte das Configurações: não salvam com Enter nem atualizam a prévia.
    adder.addEventListener('keydown', (ev) => {
      if (ev.key !== 'Enter' || !ev.target.matches('input')) return;
      ev.preventDefault();
      if (ev.target !== search) addLink();
    });
    ['input', 'change'].forEach((name) => adder.addEventListener(name, (ev) => ev.stopPropagation()));

    sync();
    load();
  });

  /* ===================================================== Editor visual */

  const ALLOWED = {
    P: [], BR: [], H2: [], H3: [], H4: [], STRONG: [], EM: [], S: [], U: [], SUB: [], SUP: [], A: ['href'],
    UL: [], OL: [], LI: [], BLOCKQUOTE: [], PRE: [], CODE: [], IMG: ['src', 'alt'], FIGURE: [], FIGCAPTION: [], HR: [],
    TABLE: [], THEAD: [], TBODY: [], TR: [], TH: [], TD: [],
  };
  const RENAME = { H1: 'H2', H5: 'H4', H6: 'H4', B: 'STRONG', I: 'EM', STRIKE: 'S', DEL: 'S' };
  const BLOCK_CHILD = /^(P|DIV|H[1-6]|UL|OL|TABLE|BLOCKQUOTE|PRE|FIGURE)$/;

  /** Limpa HTML colado (Word, Google Docs, outros sites): mantém só a estrutura. */
  function sanitize(html) {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    $$('script,style,meta,link,title,noscript,iframe,object,svg', doc).forEach((n) => n.remove());
    const out = document.createElement('div');
    const walk = (node, parent) => {
      for (const child of node.childNodes) {
        if (child.nodeType === Node.TEXT_NODE) { parent.append(child.textContent); continue; }
        if (child.nodeType !== Node.ELEMENT_NODE) continue;
        let tag = RENAME[child.tagName] || child.tagName;
        const style = child.getAttribute('style') || '';
        if (tag === 'STRONG' && /font-weight:\s*(normal|400)/.test(style)) tag = 'SPAN'; // Google Docs
        if (tag === 'SPAN' && /font-weight:\s*(bold|[6-9]00)/.test(style)) tag = 'STRONG';
        else if (tag === 'SPAN' && /font-style:\s*italic/.test(style)) tag = 'EM';
        if (tag === 'DIV') tag = [...child.children].some((c) => BLOCK_CHILD.test(c.tagName)) ? 'SPAN' : 'P';
        if (!ALLOWED[tag]) { walk(child, parent); continue; }
        const el = document.createElement(tag);
        for (const attr of ALLOWED[tag]) {
          const value = child.getAttribute(attr);
          if (value && !/^\s*(javascript|data):/i.test(value)) el.setAttribute(attr, value);
        }
        walk(child, el);
        if (el.tagName === 'P' && !el.textContent.trim() && !el.querySelector('img')) continue;
        parent.append(el);
      }
    };
    walk(doc.body, out);
    return out.innerHTML;
  }

  /** Texto puro colado → parágrafos. */
  const textToHtml = (text) => text.trim().split(/\n\s*\n/)
    .map((p) => `<p>${escapeHtml(p).replace(/\n/g, '<br>')}</p>`).join('');

  /** Quebra de linha depois dos blocos, para o modo HTML ficar legível. */
  const prettyHtml = (html) => html
    .replace(/\s*(<(?:p|h[2-4]|ul|ol|li|blockquote|figure|pre|table|tr|hr)[\s>])/g, '\n$1')
    .replace(/(<\/(?:ul|ol|table)>)/g, '\n$1')
    .trim();

  function initEditor(root) {
    const area = $('.editor-content', root);
    const source = $('.editor-source', root);
    const blockSelect = $('[data-block]', root);
    const status = $('.toolbar-status', root);
    let sourceMode = false;
    let range = null;

    const isEmpty = () => !area.textContent.trim() && !area.querySelector('img,iframe,hr');
    const sync = () => { if (!sourceMode) source.value = isEmpty() ? '' : area.innerHTML.trim(); };
    const changed = () => {
      sync();
      markDirty();
      root.dispatchEvent(new CustomEvent('editor:change', { bubbles: true }));
    };
    const setHtml = (html) => {
      area.innerHTML = html.trim() || '<p><br></p>';
      source.value = html;
    };

    setHtml(source.value);
    try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch { /* navegador antigo */ }

    // Guarda a posição do cursor: clicar na barra ou abrir a biblioteca tira o foco do texto.
    document.addEventListener('selectionchange', () => {
      const sel = getSelection();
      if (sel.rangeCount && area.contains(sel.anchorNode)) {
        range = sel.getRangeAt(0).cloneRange();
        updateToolbar();
      }
    });
    const restore = () => {
      if (document.activeElement !== area) area.focus();
      if (!range) {
        range = document.createRange();
        range.selectNodeContents(area);
        range.collapse(false);
      }
      const sel = getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    };
    const exec = (cmd, value = null) => {
      restore();
      document.execCommand(cmd, false, value);
      changed();
      updateToolbar();
    };
    const insertHtml = (html) => exec('insertHTML', html);

    const closest = (tag) => {
      for (let n = range?.startContainer; n && n !== area; n = n.parentNode) {
        if (n.nodeType === 1 && n.tagName === tag) return n;
      }
      return null;
    };
    const currentBlock = () => {
      for (let n = range?.startContainer; n && n !== area; n = n.parentNode) {
        if (n.nodeType === 1 && /^(P|H[1-6]|PRE|LI|BLOCKQUOTE)$/.test(n.tagName)) return n;
      }
      return null;
    };

    function updateToolbar() {
      const states = { bold: 'bold', italic: 'italic', strike: 'strikeThrough', ul: 'insertUnorderedList', ol: 'insertOrderedList' };
      for (const [cmd, query] of Object.entries(states)) {
        let on = false;
        try { on = document.queryCommandState(query); } catch { /* ignore */ }
        $(`[data-cmd="${cmd}"]`, root)?.classList.toggle('active', on);
      }
      $('[data-cmd="quote"]', root).classList.toggle('active', !!closest('BLOCKQUOTE'));
      $('[data-cmd="link"]', root).classList.toggle('active', !!closest('A'));
      const block = currentBlock();
      blockSelect.value = block && /^(H2|H3|H4|PRE)$/.test(block.tagName) ? block.tagName.toLowerCase() : 'p';
    }

    const imageHtml = (file) => `<figure><img src="${escapeHtml(file.url)}" alt=""></figure><p><br></p>`;

    async function uploadAndInsert(files) {
      const images = [...files].filter((f) => f.type.startsWith('image/'));
      if (!images.length) return;
      status.textContent = 'Enviando imagem…';
      try {
        const saved = await upload(images);
        insertHtml(saved.map(imageHtml).join(''));
      } catch (err) {
        alert(err.message);
      } finally {
        status.textContent = '';
      }
    }

    const commands = {
      bold: () => exec('bold'),
      italic: () => exec('italic'),
      strike: () => exec('strikeThrough'),
      ul: () => exec('insertUnorderedList'),
      ol: () => exec('insertOrderedList'),
      quote: () => exec('formatBlock', closest('BLOCKQUOTE') ? '<p>' : '<blockquote>'),
      hr: () => exec('insertHorizontalRule'),
      undo: () => exec('undo'),
      redo: () => exec('redo'),
      clear: () => { exec('removeFormat'); exec('unlink'); },
      link: () => {
        const a = closest('A');
        const url = prompt('Endereço do link (deixe vazio para remover):', a ? a.getAttribute('href') : 'https://');
        if (url === null) return;
        const href = url.trim();
        if (!href || href === 'https://') {
          if (a) { range.selectNode(a); exec('unlink'); }
          return;
        }
        if (a) { a.setAttribute('href', href); changed(); return; }
        if (!range || range.collapsed) { insertHtml(`<a href="${escapeHtml(href)}">${escapeHtml(href)}</a>&nbsp;`); return; }
        exec('createLink', href);
      },
      image: async () => {
        const file = await pickMedia();
        if (file) insertHtml(imageHtml(file));
      },
      video: () => {
        const url = prompt('Cole o link do vídeo (YouTube ou Vimeo):');
        if (!url) return;
        const html = videoEmbed(url.trim());
        if (!html) { alert('Link não reconhecido. Use um endereço do YouTube ou do Vimeo.'); return; }
        insertHtml(html + '<p><br></p>');
      },
      source: () => {
        if (!sourceMode) {
          source.value = isEmpty() ? '' : prettyHtml(area.innerHTML);
          source.style.height = Math.max(area.offsetHeight, 460) + 'px';
        } else {
          setHtml(source.value);
        }
        sourceMode = !sourceMode;
        area.hidden = sourceMode;
        source.hidden = !sourceMode;
        root.classList.toggle('is-source', sourceMode);
        (sourceMode ? source : area).focus();
      },
    };

    const toolbar = $('.editor-toolbar', root);
    toolbar.addEventListener('mousedown', (e) => { if (e.target.closest('button')) e.preventDefault(); });
    $$('[data-cmd]', root).forEach((btn) => btn.addEventListener('click', () => commands[btn.dataset.cmd]?.()));
    blockSelect.addEventListener('change', () => exec('formatBlock', `<${blockSelect.value}>`));

    area.addEventListener('input', changed);
    source.addEventListener('input', markDirty);
    area.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') { e.preventDefault(); commands.link(); }
    });

    area.addEventListener('paste', (e) => {
      const data = e.clipboardData;
      if (!data) return;
      const files = [...data.files];
      if (files.length) { e.preventDefault(); uploadAndInsert(files); return; }
      const html = data.getData('text/html');
      const text = data.getData('text/plain');
      e.preventDefault();
      if (closest('PRE')) { document.execCommand('insertText', false, text); return; }
      const video = !/\s/.test(text.trim()) && videoEmbed(text.trim());
      if (video) { document.execCommand('insertHTML', false, video + '<p><br></p>'); return; }
      document.execCommand('insertHTML', false, html ? sanitize(html) : textToHtml(text));
    });

    area.addEventListener('dragover', (e) => {
      if ([...e.dataTransfer.types].includes('Files')) { e.preventDefault(); area.classList.add('dragging'); }
    });
    area.addEventListener('dragleave', () => area.classList.remove('dragging'));
    area.addEventListener('drop', (e) => {
      area.classList.remove('dragging');
      if (!e.dataTransfer.files.length) return;
      e.preventDefault();
      const r = document.caretRangeFromPoint?.(e.clientX, e.clientY);
      if (r) { range = r; const sel = getSelection(); sel.removeAllRanges(); sel.addRange(r); }
      uploadAndInsert(e.dataTransfer.files);
    });

    // Barrinha de imagem: texto alternativo, legenda, remover.
    const bar = document.createElement('div');
    bar.className = 'img-bar';
    bar.hidden = true;
    bar.innerHTML = '<button type="button" data-img="alt">Texto alternativo</button><button type="button" data-img="caption">Legenda</button><button type="button" data-img="remove">Remover</button>';
    root.append(bar);
    let selected = null;
    const hideBar = () => { selected?.classList.remove('selected'); selected = null; bar.hidden = true; };
    area.addEventListener('click', (e) => {
      if (e.target.tagName !== 'IMG') { hideBar(); return; }
      hideBar();
      selected = e.target;
      selected.classList.add('selected');
      const box = root.getBoundingClientRect();
      const img = selected.getBoundingClientRect();
      bar.style.top = `${Math.max(48, img.top - box.top + 8)}px`;
      bar.style.left = `${img.left - box.left + 8}px`;
      bar.hidden = false;
    });
    bar.addEventListener('mousedown', (e) => e.preventDefault());
    bar.addEventListener('click', (e) => {
      const action = e.target.dataset.img;
      if (!selected || !action) return;
      if (action === 'alt') {
        const alt = prompt('Descreva a imagem (ajuda acessibilidade e Google):', selected.alt);
        if (alt !== null) selected.alt = alt.trim();
      } else if (action === 'caption') {
        let fig = selected.closest('figure');
        let cap = fig?.querySelector('figcaption');
        const text = prompt('Legenda (vazio remove):', cap?.textContent || '');
        if (text === null) return;
        if (!fig) {
          fig = document.createElement('figure');
          const holder = selected.parentElement.tagName === 'P' && selected.parentElement.textContent.trim() === '' ? selected.parentElement : selected;
          holder.replaceWith(fig);
          fig.append(selected);
        }
        if (!text.trim()) cap?.remove();
        else {
          if (!cap) { cap = document.createElement('figcaption'); fig.append(cap); }
          cap.textContent = text.trim();
        }
      } else if (action === 'remove') {
        const fig = selected.closest('figure');
        (fig && fig.querySelectorAll('img').length === 1 ? fig : selected).remove();
      }
      hideBar();
      changed();
    });

    root.closest('form')?.addEventListener('submit', sync);
    root._setHtml = (html) => { if (sourceMode) commands.source(); setHtml(html); };
  }

  $$('[data-editor]').forEach(initEditor);

  /* ===================================================== Formulário do post */

  const form = $('#post-form');
  if (form) {
    form.addEventListener('input', markDirty);

    // Endereço (slug) gerado a partir do título até ser editado à mão.
    const title = $('[name=title]', form);
    const slug = $('[name=slug]', form);
    let manualSlug = !slug.dataset.auto;
    slug.addEventListener('input', () => { manualSlug = true; });
    slug.addEventListener('change', () => { slug.value = slugify(slug.value); });
    title.addEventListener('input', () => { if (!manualSlug) slug.value = slugify(title.value); });

    // Ctrl+S salva mantendo o status atual.
    document.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
        e.preventDefault();
        const button = $('[data-default-save]', form);
        if (form.requestSubmit) form.requestSubmit(button); else button.click();
      }
    });

    initDraftBackup(form);
  }

  /* ===================================================== SEO */

  /*
   * Formulários com data-seo="post|term|home" (editor, categoria/tag, Painel → SEO):
   * prévia do resultado no Google ([data-snippet]), tamanho do título e da descrição
   * ([data-meter]) e, no editor, a análise da palavra-chave ([data-seo-checks]), como no Yoast.
   * O título segue Seo::title() no PHP: "Título – Nome do blog".
   */
  const fold = (s) => s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  const cut = (text, max) => (text.length > max ? text.slice(0, max).replace(/\s+\S*$/, '') + ' …' : text);

  $$('form[data-seo]').forEach((box) => {
    const kind = box.dataset.seo;
    const value = (name) => (box.elements[name]?.value || '').trim();
    const sep = () => $('[name=seo_separator]:checked', box)?.value || box.dataset.sep || '–';
    const site = box.dataset.site || '';
    const parse = (html) => new DOMParser().parseFromString(html, 'text/html');
    const words = (text) => (text ? text.split(/\s+/).filter(Boolean).length : 0);
    // Resumo automático de um HTML, como excerpt_from($html, 160) no PHP.
    const excerpt = (html) => {
      const text = parse(html).body.textContent.replace(/\s+/g, ' ').trim();
      return text.length > 160 ? text.slice(0, 160).replace(/\s+\S*$/, '').replace(/[\s,.;:!?–—-]+$/, '') + '…' : text;
    };

    // Título e descrição que o Google vai receber (os mesmos padrões do PHP quando o campo está vazio).
    const texts = () => {
      if (kind === 'home') {
        const tagline = box.dataset.tagline || '';
        return { own: '', title: value('seo_home_title') || site + (tagline ? ` ${sep()} ${tagline}` : ''), desc: value('description') || tagline, path: '/' };
      }
      const own = value('seo_title') || (kind === 'post' ? value('title') : box.dataset.name || '');
      let desc = value('seo_description');
      if (!desc && kind === 'post') desc = value('excerpt') || excerpt(value('content'));
      if (!desc && kind === 'term') desc = excerpt(value('description')) || box.dataset.auto || '';
      const path = kind === 'post' ? `/${value('slug') || slugify(value('title'))}/` : box.dataset.path || '/';
      return { own, title: `${own} ${sep()} ${site}`, desc, path };
    };

    const snippet = $('[data-snippet]', box);
    const renderSnippet = (t) => {
      if (!snippet) return;
      const parts = t.path.split('/').filter(Boolean);
      $('[data-snippet-url]', snippet).textContent = [box.dataset.host || '', ...parts].join(' › ');
      $('[data-snippet-title]', snippet).textContent = cut(t.title, 60);
      $('[data-snippet-desc]', snippet).textContent = t.desc ? cut(t.desc, 158) : 'Sem descrição: o Google vai mostrar um trecho qualquer da página.';
    };

    const renderMeters = (t) => $$('[data-meter]', box).forEach((meter) => {
      const isTitle = meter.dataset.meter === 'title';
      const length = (isTitle ? t.title : t.desc).length;
      const [min, max] = isTitle ? [30, 60] : [120, 160];
      meter.dataset.state = !length || length > max + 15 ? 'bad' : length < min || length > max ? 'ok' : 'good';
      meter.style.setProperty('--fill', `${Math.min(100, (length / (max + 15)) * 100)}%`);
      meter.textContent = `${length} caracteres · ideal ${isTitle ? `até ${max}` : `de ${min} a ${max}`}`
        + (value(meter.dataset.field) ? '' : ' · automático');
    });

    // Análise do editor: [estado, texto], estado = good | ok | bad.
    const analyze = (t) => {
      const doc = parse(value('content'));
      const text = doc.body.textContent.replace(/\s+/g, ' ').trim();
      const total = words(text);
      const key = fold(value('seo_keyword'));
      const has = (s) => !!key && fold(s || '').includes(key);
      const images = [...doc.images];
      const headings = [...doc.querySelectorAll('h2, h3, h4')];
      const links = [...doc.querySelectorAll('a[href]')].map((a) => a.getAttribute('href'));
      const host = (box.dataset.host || '').split('/')[0];
      const internal = links.filter((href) => href.startsWith('/') || (host && href.includes('//' + host)));
      const external = links.filter((href) => /^https?:\/\//i.test(href) && !internal.includes(href));
      const isPage = box.dataset.type === 'page';
      const checks = [];
      const add = (state, message) => checks.push([state, message]);

      if (!key) {
        add('ok', 'Defina a palavra-chave principal para ver se o texto está bem posicionado para ela.');
      } else {
        const at = fold(t.own).indexOf(key);
        add(at < 0 ? 'bad' : at <= fold(t.own).length / 3 ? 'good' : 'ok',
          at < 0 ? 'A palavra-chave não está no título para o Google.' : at <= fold(t.own).length / 3 ? 'Palavra-chave no começo do título.' : 'A palavra-chave está no título, mas longe do começo.');
        add(has(t.desc) ? 'good' : 'bad', has(t.desc) ? 'Palavra-chave na descrição.' : 'A palavra-chave não aparece na descrição.');
        const slug = value('slug') || slugify(value('title'));
        add(slug.includes(slugify(key)) ? 'good' : 'ok', slug.includes(slugify(key)) ? 'Palavra-chave no endereço.' : 'A palavra-chave não está no endereço (slug).');
        const first = [...doc.querySelectorAll('p')].find((p) => p.textContent.trim());
        add(has(first?.textContent) ? 'good' : 'bad', has(first?.textContent) ? 'Palavra-chave logo no primeiro parágrafo.' : 'Use a palavra-chave no primeiro parágrafo.');
        if (headings.length) {
          add(headings.some((h) => has(h.textContent)) ? 'good' : 'ok', headings.some((h) => has(h.textContent)) ? 'Palavra-chave em subtítulo.' : 'Nenhum subtítulo usa a palavra-chave.');
        }
        const count = total ? fold(text).split(key).length - 1 : 0;
        const density = total ? (count * words(key) * 100) / total : 0;
        if (!count) add('bad', 'A palavra-chave não aparece no texto.');
        else if (density > 3) add('bad', `Palavra-chave repetida demais (${count} vezes): parece forçado, para o leitor e para o Google.`);
        else if (density < 0.5 && total > 300) add('ok', `A palavra-chave aparece só ${count} ${count === 1 ? 'vez' : 'vezes'} num texto longo.`);
        else add('good', `A palavra-chave aparece ${count} ${count === 1 ? 'vez' : 'vezes'} no texto, na medida.`);
        if (images.length || value('cover')) {
          const inAlt = images.some((img) => has(img.alt)) || has(value('cover_alt'));
          add(inAlt ? 'good' : 'ok', inAlt ? 'Palavra-chave no texto alternativo de uma imagem.' : 'Nenhuma imagem tem a palavra-chave no texto alternativo.');
        }
      }

      if (total >= 300) add('good', `${total} palavras.`);
      else add(isPage || total >= 150 ? 'ok' : 'bad', `Texto curto (${total} palavras). Acima de 300 costuma ir melhor no Google.`);
      add(t.title.length > 75 ? 'bad' : t.title.length > 60 ? 'ok' : 'good',
        t.title.length > 60 ? `Título com ${t.title.length} caracteres: o Google corta depois de uns 60.` : 'Título no tamanho certo.');
      if (!value('seo_description')) add('ok', 'Escreva uma descrição para o Google. Hoje ela sai do resumo ou do começo do texto.');
      else add(t.desc.length >= 120 && t.desc.length <= 160 ? 'good' : 'ok',
        t.desc.length >= 120 && t.desc.length <= 160 ? 'Descrição no tamanho certo.' : `Descrição com ${t.desc.length} caracteres: o ideal é de 120 a 160.`);
      if (total > 300) {
        add(headings.length ? 'good' : 'bad', headings.length ? 'Texto dividido com subtítulos.' : 'Texto longo sem subtítulos: divida em partes com Título 2.');
      }
      add(internal.length ? 'good' : 'ok', internal.length ? 'Tem links para outras páginas do blog.' : 'Inclua links para outros posts do blog.');
      if (!isPage) add(external.length ? 'good' : 'ok', external.length ? 'Tem links para outros sites.' : 'Nenhum link para outros sites (citar fontes passa confiança).');
      const noAlt = images.filter((img) => !img.alt.trim()).length;
      if (noAlt) add('ok', `${noAlt} ${noAlt === 1 ? 'imagem sem' : 'imagens sem'} texto alternativo (clique na imagem no editor para preencher).`);
      if (!value('cover')) add('ok', 'Sem imagem de capa: ela aparece no compartilhamento e nos resultados.');
      else if (!value('cover_alt')) add('ok', 'Preencha o texto alternativo da imagem de capa.');
      if (box.elements.seo_noindex?.checked) add('bad', 'Marcado para ficar fora do Google.');
      return checks;
    };

    const list = $('[data-seo-checks]', box);
    const score = $('[data-seo-score]', box);
    const renderChecks = (t) => {
      if (!list) return;
      const order = { bad: 0, ok: 1, good: 2 };
      const checks = analyze(t).sort((a, b) => order[a[0]] - order[b[0]]);
      list.innerHTML = checks.map(([state, message]) => `<li data-state="${state}">${escapeHtml(message)}</li>`).join('');
      const points = checks.reduce((sum, [state]) => sum + order[state], 0) / (checks.length * 2);
      const [state, label] = points >= 0.8 ? ['good', 'Bom'] : points >= 0.55 ? ['ok', 'Razoável'] : ['bad', 'Precisa melhorar'];
      score.dataset.state = state;
      score.textContent = label;
    };

    let timer;
    const render = () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        const t = texts();
        renderSnippet(t);
        renderMeters(t);
        renderChecks(t);
      }, 150);
    };
    ['input', 'change', 'editor:change'].forEach((type) => box.addEventListener(type, render));
    render();
  });

  /* ===================================================== Rascunho local */

  /**
   * Guarda o texto no navegador enquanto você escreve. Se a página fechar,
   * a sessão expirar ou a internet cair antes de salvar, dá para recuperar.
   */
  function initDraftBackup(form) {
    const type = form.dataset.type;
    const id = form.dataset.postId;
    const key = 'blog-draft:' + (id !== '0' ? id : 'new-' + type);
    const base = form.dataset.updated || '';
    const source = $('.editor-source', form);
    const title = $('[name=title]', form);
    const editor = $('[data-editor]', form);
    const notice = $('.draft-notice', form);
    const storage = (() => { try { return window.localStorage; } catch { return null; } })();
    if (!storage) return;

    const params = new URLSearchParams(location.search);
    if (params.has('created')) {
      storage.removeItem('blog-draft:new-' + type);
      params.delete('created');
      const qs = params.toString();
      history.replaceState(null, '', location.pathname + (qs ? '?' + qs : ''));
    }

    let saved = null;
    try { saved = JSON.parse(storage.getItem(key) || 'null'); } catch { /* ignore */ }
    if (saved && saved.base === base && (saved.content !== source.value || saved.title !== title.value)) {
      notice.hidden = false;
      $('[data-draft-restore]', notice).addEventListener('click', () => {
        title.value = saved.title;
        editor._setHtml(saved.content);
        notice.hidden = true;
        markDirty();
      });
      $('[data-draft-discard]', notice).addEventListener('click', () => {
        storage.removeItem(key);
        notice.hidden = true;
      });
    } else if (saved) {
      storage.removeItem(key);
    }

    let timer;
    const store = () => {
      clearTimeout(timer);
      timer = setTimeout(() => {
        try { storage.setItem(key, JSON.stringify({ base, title: title.value, content: source.value, at: Date.now() })); } catch { /* cheio */ }
      }, 800);
    };
    form.addEventListener('input', store);
    form.addEventListener('editor:change', store);
  }

  /* ===================================================== Página de mídia */

  const dropzone = $('[data-dropzone]');
  if (dropzone) {
    const input = $('input[type=file]', dropzone);
    input.addEventListener('change', () => { if (input.files.length) dropzone.submit(); });
    ['dragenter', 'dragover'].forEach((ev) => dropzone.addEventListener(ev, (e) => { e.preventDefault(); dropzone.classList.add('dragging'); }));
    ['dragleave', 'drop'].forEach((ev) => dropzone.addEventListener(ev, () => dropzone.classList.remove('dragging')));
    dropzone.addEventListener('drop', (e) => {
      e.preventDefault();
      input.files = e.dataTransfer.files;
      dropzone.submit();
    });
  }

  $$('[data-copy]').forEach((btn) => btn.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(btn.dataset.copy);
      const label = btn.textContent;
      btn.textContent = 'Copiado!';
      setTimeout(() => { btn.textContent = label; }, 1500);
    } catch {
      prompt('Copie o endereço:', btn.dataset.copy);
    }
  }));

  // Para telas de extensões que criam campos depois do carregamento (ex.: itens de lista).
  window.BlogAdmin = { pickMedia, initImageField, initEditor, markDirty };
})();
