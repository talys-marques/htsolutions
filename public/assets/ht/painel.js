// Formulário de conteúdo do site (extensions/site-ht/views/editar.php).
// Listas: adicionar, mover e remover itens. Ao salvar, junta todos os campos num JSON
// (campo "dados"), na mesma estrutura do modelo; o PHP confere cada campo de novo.
(() => {
  const form = document.querySelector('[data-sh-form]');
  const admin = window.BlogAdmin;
  if (!form || !admin) return;

  // Campos que pertencem diretamente a um contêiner (form, grupo ou item de lista)
  const filhos = (container) =>
    [...container.querySelectorAll('[data-campo]')].filter((el) => el.parentElement.closest('[data-campo], .sh-item, form') === container);

  const valor = (el) => {
    switch (el.dataset.tipo) {
      case 'grupo':
        return ler(el);
      case 'lista':
        return [...el.querySelector('.sh-itens').children].map(ler);
      case 'html':
        return el.querySelector('.editor-source').value;
      case 'imagem':
        return el.querySelector('input[type=hidden]').value;
      default:
        return el.querySelector('input, textarea, select').value;
    }
  };

  const ler = (container) => Object.fromEntries(filhos(container).map((el) => [el.dataset.campo, valor(el)]));

  // Nome do item fechado: o valor do campo de resumo (ex.: o nome do parceiro)
  const nomear = (item) => {
    const lista = item.closest('.sh-lista');
    const campo = lista.dataset.resumo && filhos(item).find((el) => el.dataset.campo === lista.dataset.resumo);
    const texto = campo ? String(valor(campo)).replace(/<[^>]+>/g, ' ').trim() : '';
    if (texto) item.querySelector('.sh-item-nome').textContent = texto;
  };

  const ativar = (root) => {
    root.querySelectorAll('[data-image-field]').forEach(admin.initImageField);
    root.querySelectorAll('[data-editor]').forEach(admin.initEditor);
  };

  form.querySelectorAll('.sh-item').forEach(nomear);
  form.addEventListener('input', (e) => {
    const item = e.target.closest('.sh-item');
    if (item) nomear(item);
  });

  form.addEventListener('click', (e) => {
    const botao = e.target.closest('[data-sh-adicionar], [data-sh-subir], [data-sh-descer], [data-sh-remover]');
    if (!botao) return;
    e.preventDefault();
    const item = botao.closest('.sh-item');
    if (botao.matches('[data-sh-adicionar]')) {
      const lista = botao.closest('.sh-lista');
      const novo = lista.querySelector(':scope > template').content.firstElementChild.cloneNode(true);
      lista.querySelector(':scope > .sh-itens').append(novo);
      ativar(novo);
      novo.open = true;
      novo.querySelector('input, textarea, [contenteditable]')?.focus();
    } else if (botao.matches('[data-sh-subir]') && item.previousElementSibling) {
      item.previousElementSibling.before(item);
    } else if (botao.matches('[data-sh-descer]') && item.nextElementSibling) {
      item.nextElementSibling.after(item);
    } else if (botao.matches('[data-sh-remover]')) {
      if (!confirm('Remover este item? Ele só sai do site quando você salvar.')) return;
      item.remove();
    }
    admin.markDirty();
  });

  form.addEventListener('submit', () => {
    form.elements.dados.value = JSON.stringify(ler(form));
  });
})();
