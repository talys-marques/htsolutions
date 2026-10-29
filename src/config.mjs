// Site-wide data: contacts, menus, forms. Page texts live in src/data/*.json (from the old Strapi)
// or in the pages themselves.

// Presentation build (GitHub Pages, PUBLIC_APRESENTACAO=1): no analytics, pages kept out of
// search engines, and the blog is a static copy (see scripts/blog-estatico.mjs).
export const APRESENTACAO = Boolean(import.meta.env.PUBLIC_APRESENTACAO);

export const SITE = {
  nome: 'HT Solutions',
  titulo: 'HT Solutions – Infraestrutura de TI e transformação digital',
  descricao:
    'Especialistas em infraestrutura de TI, com as mais elevadas certificações. Acelerando a transformação digital na sua empresa.',
  idioma: 'pt-BR',
  gtm: APRESENTACAO ? '' : 'GTM-TJ2Q8TFS',
};

export const CONTATO = {
  telefone: { texto: '(51) 4020 1585', link: 'tel:+555140201585' },
  email: 'evendas@htsolutions.com.br',
  endereco: 'Rodovia BR 116, 7070 · CEP 93950-000 · Dois Irmãos – RS',
  horario: 'Atendimento corporativo de segunda a quinta das 7h50 às 18h. Na sexta, das 7h50 às 17h.',
  suporte: { email: 'suporte@htsolutions.com.br', telefone: { texto: '0800 025 8855', link: 'tel:+5508000258855' } },
};

export const REDES = [
  { nome: 'Facebook', icone: 'facebook', link: 'https://facebook.com/htsolutionsoficial' },
  { nome: 'LinkedIn', icone: 'linkedin', link: 'https://linkedin.com/company/htsolutions' },
  { nome: 'Instagram', icone: 'instagram', link: 'https://instagram.com/htsolutionsbr' },
];

// The blog is the blog-system installed in /blog/ on the same domain (see ../blog/)
export const BLOG = {
  href: '/blog/',
  cases: '/blog/category/cases/',
  // JSON with the latest posts (extension blog/extensions/ultimos-posts). In `npm run dev` the
  // blog isn't in /blog/: set PUBLIC_BLOG_URL=http://localhost:8080 to read a local blog-system.
  ultimos: (import.meta.env.PUBLIC_BLOG_URL ?? '/blog') + '/ultimos.json',
  // The static copy can't read ?categoria=: it has one file per list (ultimos-cases.json, ultimos-todos.json)
  ultimosEstatico: (categoria) => '/blog/ultimos-' + (categoria || 'todos') + '.json',
};

export const EXTERNOS = {
  loja: 'https://produtos.htsolutions.com.br/p/produtos/',
  atas: 'https://atas.htsolutions.com.br/a/atas/atas/',
  vagas: 'https://htsolutions.gupy.io/',
  chamado: 'https://helpdesk.htsolutions.com.br/loginUsuario.php',
};

export const MENU = [
  {
    titulo: 'Soluções',
    href: '/servicos/',
    submenu: [
      { titulo: 'Soluções para Educação', href: '/educacao/' },
      { titulo: 'Google para Empresas', href: '/googleparaempresas/' },
      { titulo: 'HP Inc', href: '/hp/' },
    ],
  },
  { titulo: 'Serviços', href: '/servicos/#servicos' },
  { titulo: 'Atas', href: '/atas/', submenu: [{ titulo: 'Itens Disponíveis', href: EXTERNOS.atas }] },
  { titulo: 'Loja Online', href: EXTERNOS.loja },
  { titulo: 'Blog', href: BLOG.href, submenu: [{ titulo: 'Cases', href: BLOG.cases }] },
  { titulo: 'Quem somos', href: '/quem-somos/' },
  { titulo: 'Garantias', href: '/consulta-garantias/' },
];

export const RODAPE = {
  menu: [
    { titulo: 'Quem somos', href: '/quem-somos/' },
    { titulo: 'Serviços', href: '/servicos/#servicos' },
    { titulo: 'Atas', href: '/atas/' },
    { titulo: 'Cases', href: BLOG.cases },
    { titulo: 'Blog', href: BLOG.href },
  ],
  solucoes: [
    { titulo: 'Infraestrutura', href: '/servicos/#enterprise' },
    { titulo: 'Redes', href: '/servicos/#servicos' },
    { titulo: 'Computação pessoal', href: '/servicos/#client' },
  ],
  links: [
    { titulo: 'Política de Privacidade', href: '/politica-de-privacidade/' },
    { titulo: 'Trabalhe Conosco', href: EXTERNOS.vagas },
  ],
};

// Emarsys forms (link.htsolutions.com.br). Each page sends to its own form id (`f`), which
// decides the list and the e-mail the lead goes to. `campo` = the extra field of that form.
export const EMARSYS = {
  acao: 'https://link.htsolutions.com.br/u/register.php',
  cid: '1090780807',
  formularios: {
    empresa: { f: '1128', campo: { nome: 'inp_18', rotulo: 'Empresa' }, mensagem: 'inp_15463' },
    atas: { f: '1193', campo: { nome: 'inp_15895', rotulo: 'Órgão Público' }, mensagem: 'inp_15896' },
    educacao: { f: '1129', campo: { nome: 'inp_15462', rotulo: 'Instituição de Ensino' }, mensagem: 'inp_15464' },
    enterprise: { f: '1194', campo: { nome: 'inp_18', rotulo: 'Empresa' }, mensagem: 'inp_15898' },
  },
  newsletter: { f: '1404' },
};
