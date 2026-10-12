/* busca.js — lupa do cabeçalho: busca de cursos em tempo real.
 *
 * Igual nos quatro sites. Qualquer elemento com [data-busca-abrir] abre a
 * busca; "/" e Ctrl+K também. O catálogo vem do mesmo api/cursos.php da
 * vitrine (cache de 5 min) e é baixado só na primeira abertura, então a lupa
 * não pesa no carregamento da página. A procura roda no próprio navegador:
 * cada letra digitada já refaz a lista, sem ir ao servidor.
 *
 * window.BuscaCursos.casa() e .normalizar() ficam expostos para o filtro do
 * catálogo da home (app.js) usar a mesma regra da lupa.
 */
(function () {
  'use strict';

  const LIMITE = 8;
  const RUIDO = new Set(['de', 'da', 'do', 'das', 'dos', 'em', 'e', 'o', 'a', 'para', 'curso', 'cursos']);

  function normalizar(s) {
    return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
  }

  function palavras(s) {
    return normalizar(s).split(/[^a-z0-9]+/).filter(Boolean);
  }

  function termos(q) {
    const todas = palavras(q);
    const uteis = todas.filter(p => !RUIDO.has(p));
    return uteis.length ? uteis : todas;
  }

  // Cada termo precisa começar alguma palavra do curso ("enf" acha
  // "Enfermagem"). Pontua mais quando casa no nome do que na descrição.
  // O índice fica fora do objeto do curso: no catálogo da home ele é reativo
  // (Vue), e gravar nele durante o filtro dispararia um novo cálculo.
  const indices = new WeakMap();
  function pontuar(curso, lista) {
    let idx = indices.get(curso);
    if (!idx) {
      idx = {
        nome: palavras(curso.nome + ' ' + (curso.nomeBusca || '') + ' ' + (curso.id || '')),
        cat: palavras(curso.categoriaLabel),
        desc: palavras(curso.descricao)
      };
      indices.set(curso, idx);
    }
    let total = 0;
    for (const t of lista) {
      const comeca = w => w.startsWith(t);
      if (idx.nome.some(w => w === t)) total += 4;
      else if (idx.nome.some(comeca)) total += 3;
      else if (idx.cat.some(comeca)) total += 2;
      else if (t.length >= 4 && idx.desc.some(comeca)) total += 1;
      else return 0;
    }
    if (normalizar(curso.nome).startsWith(lista.join(' '))) total += 2;
    return total;
  }

  function casa(curso, q) {
    const lista = termos(q);
    return !lista.length || pontuar(curso, lista) > 0;
  }

  function procurar(cursos, q, categoria) {
    const lista = termos(q);
    const base = categoria ? cursos.filter(c => c.categoria === categoria) : cursos;
    if (!lista.length) return base;
    return base
      .map(c => [c, pontuar(c, lista)])
      .filter(([, p]) => p > 0)
      .sort((a, b) => b[1] - a[1])
      .map(([c]) => c);
  }

  window.BuscaCursos = { normalizar, casa, procurar };

  /* ---------------- interface ---------------- */

  let cursos = null;
  let carregando = null;
  let painel, campo, lista, rodape, chips;
  let categoria = '';
  let ativo = -1;
  let resultados = [];
  let focoAnterior = null;

  function esc(s) {
    return String(s ?? '').replace(/[&<>"']/g, ch => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]));
  }

  function carregar() {
    if (cursos) return Promise.resolve(cursos);
    if (!carregando) {
      carregando = fetch('/api/cursos.php')
        .then(r => r.json())
        .then(d => (cursos = d.cursos || []))
        .catch(() => { carregando = null; return []; });
    }
    return carregando;
  }

  function linkCurso(c) {
    return '/curso.php?id=' + encodeURIComponent(c.slug || c.id);
  }

  function montar() {
    if (painel) return;
    painel = document.createElement('div');
    painel.className = 'busca';
    painel.hidden = true;
    painel.innerHTML =
      '<div class="busca__fundo" data-busca-fechar></div>' +
      '<div class="busca__caixa" role="dialog" aria-modal="true" aria-label="Buscar cursos">' +
        '<div class="busca__topo">' +
          '<i class="ri-search-line" aria-hidden="true"></i>' +
          '<input type="search" class="busca__campo" placeholder="Qual curso você procura?" autocomplete="off" enterkeyhint="search"' +
          ' role="combobox" aria-expanded="true" aria-controls="busca-lista" aria-autocomplete="list">' +
          '<button type="button" class="busca__fechar" data-busca-fechar aria-label="Fechar busca"><i class="ri-close-line"></i></button>' +
        '</div>' +
        '<div class="busca__chips">' +
          '<button type="button" data-cat="" class="ativo">Todos</button>' +
          '<button type="button" data-cat="eja">Supletivo EJA</button>' +
          '<button type="button" data-cat="tecnico">Técnico</button>' +
          '<button type="button" data-cat="tecnico-competencia">Técnico Competência</button>' +
          '<button type="button" data-cat="profissionalizante">Profissionalizantes</button>' +
        '</div>' +
        '<ul class="busca__lista" id="busca-lista" role="listbox"></ul>' +
        '<div class="busca__rodape"></div>' +
      '</div>';
    document.body.appendChild(painel);

    campo = painel.querySelector('.busca__campo');
    lista = painel.querySelector('.busca__lista');
    rodape = painel.querySelector('.busca__rodape');
    chips = painel.querySelector('.busca__chips');

    campo.addEventListener('input', desenhar);
    campo.addEventListener('keydown', teclas);
    painel.addEventListener('click', e => {
      if (e.target.closest('[data-busca-fechar]')) fechar();
    });
    chips.addEventListener('click', e => {
      const b = e.target.closest('button[data-cat]');
      if (!b) return;
      categoria = b.dataset.cat;
      chips.querySelectorAll('button').forEach(x => x.classList.toggle('ativo', x === b));
      desenhar();
      campo.focus();
    });
    lista.addEventListener('mousemove', e => {
      const li = e.target.closest('li[data-i]');
      if (li && +li.dataset.i !== ativo) marcar(+li.dataset.i);
    });
  }

  function desenhar() {
    const q = campo.value.trim();
    if (!cursos) {
      lista.innerHTML = '<li class="busca__aviso">Carregando cursos…</li>';
      rodape.innerHTML = '';
      return;
    }
    const todos = procurar(cursos, q, categoria);
    resultados = todos.slice(0, LIMITE);
    ativo = q && resultados.length ? 0 : -1;

    if (!todos.length) {
      lista.innerHTML = '<li class="busca__aviso"><i class="ri-emotion-sad-line"></i>' +
        'Nenhum curso encontrado para <strong>“' + esc(q) + '”</strong>.<br>Tente outra palavra ou fale com a gente no WhatsApp.</li>';
      rodape.innerHTML = '';
      return;
    }

    lista.innerHTML = resultados.map((c, i) =>
      '<li role="option" data-i="' + i + '"' + (i === ativo ? ' class="ativo" aria-selected="true"' : '') + '>' +
        '<a href="' + esc(linkCurso(c)) + '">' +
          '<span class="busca__ico" style="background:' + esc(c.cor || '') + '">' +
            (c.imagem ? '<img src="' + esc(c.imagem) + '" alt="" loading="lazy">' : esc(c.emoji || '🎓')) +
          '</span>' +
          '<span class="busca__txt">' +
            '<strong>' + esc(c.nome) + '</strong>' +
            '<small>' + esc(c.categoriaLabel) + (c.duracao ? ' · ' + esc(c.duracao) : '') + '</small>' +
          '</span>' +
          (c.preco ? '<span class="busca__preco">' + (c.parcelas ? '<em>' + esc(c.parcelas) + 'x</em> ' : '') + 'R$ ' + esc(c.preco) + '</span>' : '') +
          '<i class="ri-arrow-right-s-line busca__seta" aria-hidden="true"></i>' +
        '</a>' +
      '</li>'
    ).join('');

    const resto = todos.length - resultados.length;
    rodape.innerHTML = resto > 0
      ? todos.length + ' cursos encontrados · mostrando os ' + resultados.length + ' primeiros. Continue digitando para refinar.'
      : todos.length + (todos.length === 1 ? ' curso encontrado' : ' cursos encontrados');
  }

  function marcar(i) {
    ativo = i;
    lista.querySelectorAll('li[data-i]').forEach(li => {
      const sim = +li.dataset.i === i;
      li.classList.toggle('ativo', sim);
      li.setAttribute('aria-selected', sim ? 'true' : 'false');
      if (sim) li.scrollIntoView({ block: 'nearest' });
    });
  }

  function teclas(e) {
    if (e.key === 'ArrowDown' && resultados.length) {
      e.preventDefault();
      marcar((ativo + 1) % resultados.length);
    } else if (e.key === 'ArrowUp' && resultados.length) {
      e.preventDefault();
      marcar((ativo - 1 + resultados.length) % resultados.length);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      const c = resultados[ativo >= 0 ? ativo : 0];
      if (c) location.href = linkCurso(c);
    }
  }

  function abrir() {
    montar();
    if (!painel.hidden) return;
    focoAnterior = document.activeElement;
    painel.hidden = false;
    document.documentElement.classList.add('busca-aberta');
    requestAnimationFrame(() => painel.classList.add('aberta'));
    campo.focus();
    desenhar();
    carregar().then(() => { if (!painel.hidden) desenhar(); });
  }

  function fechar() {
    if (!painel || painel.hidden) return;
    painel.classList.remove('aberta');
    painel.hidden = true;
    document.documentElement.classList.remove('busca-aberta');
    if (focoAnterior && focoAnterior.focus) focoAnterior.focus();
  }

  document.addEventListener('click', e => {
    const gatilho = e.target.closest('[data-busca-abrir]');
    if (!gatilho) return;
    e.preventDefault();
    abrir();
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { fechar(); return; }
    const digitando = /^(input|textarea|select)$/i.test(e.target.tagName) || e.target.isContentEditable;
    if ((e.key === '/' && !digitando) || ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k')) {
      e.preventDefault();
      abrir();
    }
  });

  // Passar o mouse na lupa já adianta o download do catálogo.
  document.addEventListener('pointerover', e => {
    if (e.target.closest && e.target.closest('[data-busca-abrir]')) carregar();
  }, { passive: true });
})();
