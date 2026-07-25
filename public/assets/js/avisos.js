/* avisos.js — balões de prova social (quem acabou de se matricular).
   Os dados e os tempos vêm de api/avisos.php (Directus → site_configuracoes).
   Roda fora do Vue: o balão é anexado direto ao <body>. */
(function () {
  'use strict';

  var CHAVE_FECHADO = 'eduset_avisos_fechados';

  function escapar(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* Modelo escrito no painel → HTML seguro.
     Tudo é escapado; só *entre asteriscos* vira negrito. */
  function montarTexto(modelo, item) {
    var campos = {
      nome: item.nome,
      primeiro_nome: item.primeiroNome,
      curso: item.curso,
      cidade: item.cidade,
      estado: item.estado,
      quando: item.quando
    };
    var html = escapar(modelo).replace(/\{(\w+)\}/g, function (todo, chave) {
      var v = campos[chave];
      return v === undefined ? todo : escapar(String(v).replace(/\*/g, ''));
    });
    return html.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
  }

  /* Sobra o "de  - " quando a linha não tem cidade/estado: limpa a pontuação solta. */
  function limpar(html) {
    return html
      .replace(/\s*-\s*(?=<|,|\.|$)/g, '')
      .replace(/\s{2,}/g, ' ')
      .replace(/\s+([,.])/g, '$1')
      .trim();
  }

  function avatar(item) {
    if (item.imagem) {
      return '<img class="aviso-pop__foto" src="' + escapar(item.imagem) + '&w=400" alt="" loading="lazy">';
    }
    if (item.emoji) {
      return '<span class="aviso-pop__foto aviso-pop__foto--emoji">' + escapar(item.emoji) + '</span>';
    }
    return '<span class="aviso-pop__foto aviso-pop__foto--iniciais">' + escapar(item.iniciais) + '</span>';
  }

  function iniciar(dados) {
    var cfg = dados.config || {};
    var itens = dados.itens || [];
    if (!dados.ativo || !itens.length) return;

    var caixa = document.createElement('div');
    caixa.className = 'aviso-pop' + (cfg.posicao === 'direita' ? ' aviso-pop--direita' : '');
    caixa.setAttribute('role', 'status');
    caixa.setAttribute('aria-live', 'polite');
    document.body.appendChild(caixa);

    // Cada visitante começa num ponto diferente da lista.
    var i = Math.floor(Math.random() * itens.length);
    var tempo = null;
    var parado = false;

    function agendar(fn, seg) {
      clearTimeout(tempo);
      tempo = setTimeout(fn, seg * 1000);
    }

    function esconder() {
      caixa.classList.remove('aberto');
      agendar(mostrar, Math.max(1, cfg.intervalo - cfg.duracao));
    }

    function mostrar() {
      if (parado) return;
      if (document.hidden) { agendar(mostrar, 5); return; }

      var item = itens[i % itens.length];
      i++;

      var corpo = '<div class="aviso-pop__texto">'
        + '<p>' + limpar(montarTexto(cfg.texto || '', item)) + '</p>'
        + (cfg.rodape ? '<small>' + limpar(montarTexto(cfg.rodape, item)) + '</small>' : '')
        + '</div>';

      caixa.innerHTML = avatar(item)
        + (item.link ? '<a class="aviso-pop__link" href="' + escapar(item.link) + '">' + corpo + '</a>' : corpo)
        + '<button class="aviso-pop__fechar" type="button" aria-label="Fechar aviso">'
        + '<i class="ri-close-line"></i></button>';

      caixa.querySelector('.aviso-pop__fechar').addEventListener('click', function (ev) {
        ev.preventDefault();
        parado = true;
        clearTimeout(tempo);
        caixa.classList.remove('aberto');
        try { sessionStorage.setItem(CHAVE_FECHADO, '1'); } catch (e) { /* modo privado */ }
      });

      // Força o cálculo do layout antes de animar, senão a transição não roda.
      // (reflow síncrono; requestAnimationFrame não vale — aba em segundo plano
      //  não recebe frames e o balão ficaria invisível)
      void caixa.offsetWidth;
      caixa.classList.add('aberto');
      agendar(esconder, cfg.duracao);
    }

    agendar(mostrar, cfg.primeiro);
  }

  try {
    if (sessionStorage.getItem(CHAVE_FECHADO) === '1') return;
  } catch (e) { /* sessionStorage indisponível: segue mostrando */ }

  window.addEventListener('load', function () {
    fetch('api/avisos.php')
      .then(function (r) { return r.json(); })
      .then(iniciar)
      .catch(function () { /* sem avisos, o site segue igual */ });
  });
})();
