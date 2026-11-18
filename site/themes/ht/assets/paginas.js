// Páginas do site da HT (theme/site/): lista de vídeos, newsletter e consulta de garantias.
(() => {
  // Lista de vídeos (Google for Education): o botão troca o vídeo do player
  for (const lista of document.querySelectorAll('[data-playlist]')) {
    const iframe = lista.querySelector('iframe');
    const botoes = [...lista.querySelectorAll('button[data-video]')];
    for (const b of botoes) {
      b.addEventListener('click', () => {
        iframe.src = b.dataset.video + '?autoplay=1';
        iframe.title = b.querySelector('span').textContent;
        botoes.forEach((x) => (x === b ? x.setAttribute('aria-current', 'true') : x.removeAttribute('aria-current')));
      });
    }
  }

  // Newsletter (Emarsys, num iframe escondido): limpa e confirma depois de enviar
  for (const form of document.querySelectorAll('[data-ht-newsletter]')) {
    form.addEventListener('submit', () => {
      setTimeout(() => {
        form.reset();
        form.parentElement.querySelector('.formulario-ok').hidden = false;
      }, 800);
    });
  }

  // Consulta de garantia: a lista é cifrada (AES-GCM, chave por PBKDF2) e decifrada aqui, como no
  // site antigo. A senha fica neste arquivo: a cifra só evita a leitura direta do arquivo.
  const area = document.querySelector('[data-garantia]');
  if (!area) return;

  const SENHA = 'f3rn4nd0-g4r4nt14';
  const MAGICO = new TextEncoder().encode('EQPTv1\0');
  const SAL = 16;
  const IV = 12;
  let base = null;

  async function carregar(endereco) {
    const resposta = await fetch(endereco, { cache: 'no-store' });
    if (!resposta.ok) throw new Error('não foi possível baixar a base de garantias.');
    const dados = new Uint8Array(await resposta.arrayBuffer());
    if (dados.length < MAGICO.length + SAL + IV || !MAGICO.every((b, i) => dados[i] === b)) {
      throw new Error('arquivo de garantias inválido.');
    }
    const sal = dados.slice(MAGICO.length, MAGICO.length + SAL);
    const iv = dados.slice(MAGICO.length + SAL, MAGICO.length + SAL + IV);
    const cifrado = dados.slice(MAGICO.length + SAL + IV);
    const material = await crypto.subtle.importKey('raw', new TextEncoder().encode(SENHA), 'PBKDF2', false, ['deriveKey']);
    const chave = await crypto.subtle.deriveKey(
      { name: 'PBKDF2', salt: sal, iterations: 120000, hash: 'SHA-256' },
      material,
      { name: 'AES-GCM', length: 256 },
      false,
      ['decrypt'],
    );
    const texto = new TextDecoder().decode(await crypto.subtle.decrypt({ name: 'AES-GCM', iv, tagLength: 128 }, chave, cifrado));
    const lista = JSON.parse(texto);
    if (!Array.isArray(lista)) throw new Error('conteúdo inesperado na base de garantias.');
    return lista;
  }

  // Datas vêm como 2025-01-31 ou 31/01/2025
  const lerData = (valor) => {
    if (!valor) return null;
    const v = String(valor).trim();
    let m = v.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) return new Date(+m[1], +m[2] - 1, +m[3]);
    m = v.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (m) return new Date(+m[3], +m[2] - 1, +m[1]);
    const d = new Date(v);
    return isNaN(d.getTime()) ? null : d;
  };
  const dataBR = (valor) => lerData(valor)?.toLocaleDateString('pt-BR') ?? '';

  const situacao = (r) => {
    if (!lerData(r.data_ativacao_garantia)) return { classe: 'info', texto: 'Não ativada' };
    const vencimento = lerData(r.data_vencimento_garantia);
    if (!vencimento) return { classe: 'ruim', texto: 'Data inválida' };
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    return hoje <= vencimento ? { classe: 'ok', texto: 'Vigente' } : { classe: 'ruim', texto: 'Expirada' };
  };

  const form = area.querySelector('form');
  const campo = area.querySelector('#serial');
  const mensagem = area.querySelector('.mensagem');
  const resultado = area.querySelector('.resultado');
  const avisar = (texto, erro = false) => {
    mensagem.textContent = texto;
    mensagem.classList.toggle('erro', erro);
    mensagem.hidden = false;
  };

  form.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    resultado.hidden = true;
    const normalizar = (s) => String(s ?? '').replace(/\s+/g, '').toUpperCase();
    const serial = normalizar(campo.value);
    if (!serial) return avisar('Digite um serial para pesquisar.', true);
    try {
      avisar('Pesquisando...');
      base ??= await carregar(area.dataset.garantia);
      const r = base.find((item) => normalizar(item.serial) === serial);
      if (!r) return avisar('Serial não localizado.', true);

      const linhas = [
        ['Serial', r.serial],
        ['Nome do equipamento', r.nome_equipamento],
        ['Tipo de equipamento', r.tipo_equipamento],
        ['Nome do cliente', r.nome_cliente],
        ['Contrato', r.contrato],
        ['Data da venda', dataBR(r.data_venda)],
        ['Período de garantia', r.periodo_garantia],
        ['Tipo de garantia', r.tipo_garantia],
        ['Data de ativação da garantia', dataBR(r.data_ativacao_garantia)],
        ['Data de vencimento da garantia', dataBR(r.data_vencimento_garantia)],
      ];
      const tbody = resultado.querySelector('tbody');
      tbody.replaceChildren(
        ...linhas.map(([rotulo, valor]) => {
          const tr = document.createElement('tr');
          const th = document.createElement('th');
          const td = document.createElement('td');
          th.textContent = rotulo;
          td.textContent = valor ?? '';
          tr.append(th, td);
          return tr;
        }),
      );
      const s = situacao(r);
      const tr = document.createElement('tr');
      tr.innerHTML = '<th>Situação da garantia</th><td><span class="selo"></span></td>';
      const selo = tr.querySelector('.selo');
      selo.textContent = s.texto;
      selo.classList.add(s.classe);
      tbody.append(tr);

      const email = resultado.querySelector('[data-email]');
      if (email) email.href = email.href.split('?')[0] + '?subject=' + encodeURIComponent(`Abertura de chamado - Serial ${r.serial}`);
      mensagem.hidden = true;
      resultado.hidden = false;
    } catch (erro) {
      avisar('Erro ao buscar: ' + (erro?.message || 'tente novamente.'), true);
    }
  });
})();
