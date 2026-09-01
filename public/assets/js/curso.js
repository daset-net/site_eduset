/* curso.js — comportamento da página do curso (sem Vue: a página é renderizada no PHP).
   O formulário de matrícula fica em matricula.js. */
(function () {
  'use strict';

  // Header ganha sombra ao rolar
  var header = document.getElementById('header');
  var onScroll = function () {
    if (header) header.classList.toggle('scrolled', window.scrollY > 40);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  // Gente vendo o curso agora — total do site × 5 (fator amplificador).
  // Mínimo de 5: mesmo com 1 visitante no ar já mostra atividade real.
  var online = document.getElementById('online');
  if (online) {
    var pingar = function () {
      fetch('api/online.php?curso=' + encodeURIComponent(online.dataset.curso))
        .then(function (r) { return r.json(); })
        .then(function (d) {
          var n = d && d.online ? d.online : 0;
          if (n < 1) { online.hidden = true; return; }
          var texto = n === 1
            ? '1 pessoa vendo este curso agora'
            : n + ' pessoas vendo este curso agora';
          document.getElementById('online-texto').textContent = texto;
          online.hidden = false;
        })
        .catch(function () { online.hidden = true; });
    };
    pingar();
    setInterval(pingar, 60000);
  }

  // Contador da oferta. O prazo vem do servidor (fim do ciclo da campanha) e não
  // reinicia a cada visita: quando zera, o preço muda mesmo.
  var contador = document.querySelector('.contador');
  if (!contador) return;

  var fim = new Date(contador.dataset.fim).getTime();
  if (isNaN(fim)) { contador.hidden = true; return; }

  var partes = {
    dias:  contador.querySelector('[data-parte=dias]'),
    horas: contador.querySelector('[data-parte=horas]'),
    min:   contador.querySelector('[data-parte=min]'),
    seg:   contador.querySelector('[data-parte=seg]')
  };

  var dois = function (n) { return String(n).padStart(2, '0'); };

  var tique = function () {
    var resta = fim - Date.now();
    if (resta <= 0) {
      clearInterval(relogio);
      contador.classList.add('contador--fim');
      contador.querySelector('.contador__titulo').textContent = 'Nova condição sendo carregada…';
      setTimeout(function () { location.reload(); }, 3000);
      return;
    }
    var seg = Math.floor(resta / 1000);
    partes.dias.textContent  = dois(Math.floor(seg / 86400));
    partes.horas.textContent = dois(Math.floor(seg % 86400 / 3600));
    partes.min.textContent   = dois(Math.floor(seg % 3600 / 60));
    partes.seg.textContent   = dois(seg % 60);
  };

  var relogio = setInterval(tique, 1000);
  tique();
})();
