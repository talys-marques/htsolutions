// Tema da HT Solutions: menu do topo no celular e confirmação do formulário de contato.
(() => {
  const topo = document.querySelector('.ht-topo');
  const botao = topo?.querySelector('.ht-menu-botao');
  if (!topo || !botao) return;

  const fechar = () => {
    topo.classList.remove('ht-menu-aberto');
    botao.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('ht-sem-rolagem');
  };

  botao.addEventListener('click', () => {
    const aberto = topo.classList.toggle('ht-menu-aberto');
    botao.setAttribute('aria-expanded', String(aberto));
    document.body.classList.toggle('ht-sem-rolagem', aberto);
  });

  // "Fale conosco" leva ao formulário do rodapé: fecha o menu do celular
  for (const link of topo.querySelectorAll('a[href^="#"]')) link.addEventListener('click', fechar);

  // Submenus: passar o mouse no computador; tocar na seta no celular
  for (const sub of topo.querySelectorAll('.ht-sub-botao')) {
    sub.addEventListener('click', () => {
      const aberto = sub.closest('li').classList.toggle('ht-sub-aberto');
      sub.setAttribute('aria-expanded', String(aberto));
    });
  }

  // A Emarsys responde no iframe escondido; depois de enviar, limpa e confirma
  for (const form of document.querySelectorAll('[data-ht-formulario]')) {
    form.addEventListener('submit', () => {
      const enviar = form.querySelector('button[type=submit]');
      enviar.disabled = true;
      setTimeout(() => {
        form.reset();
        enviar.disabled = false;
        form.querySelector('.ht-ok').hidden = false;
      }, 800);
    });
  }
})();
