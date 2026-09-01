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

  // ── Contador de pessoas vendo o curso ────────────────────────────────────
  // Número aleatório entre 10 e 1000.
  // Regra: 99% das vezes é um número não-redondo (ex: 11, 45, 89, 137…).
  //        1% das vezes pode ser múltiplo de 10 (10, 20, 30…).
  // Nunca mostra zero.
  var online = document.getElementById('online');
  if (online) {
    var gerarNumero = function () {
      // 1% de chance de ser múltiplo de 10
      var redondo = Math.random() < 0.01;
      var n;
      if (redondo) {
        // Múltiplo de 10 entre 10 e 1000: sorteio de 1 a 100 × 10
        n = (Math.floor(Math.random() * 100) + 1) * 10;
      } else {
        // Número entre 10 e 1000 que NÃO seja múltiplo de 10
        do {
          n = Math.floor(Math.random() * 991) + 10; // 10..1000
        } while (n % 10 === 0);
      }
      return n;
    };

    var mostrar = function () {
      var n = gerarNumero();
      var texto = n + ' pessoas vendo este curso agora';
      document.getElementById('online-texto').textContent = texto;
      online.hidden = false;
    };

    mostrar();
    // Atualiza o número a cada 60 segundos com um novo sorteio
    setInterval(mostrar, 60000);
  }

  // ── Contador da oferta ───────────────────────────────────────────────────
  // O prazo vem do servidor (fim do ciclo da campanha) e não reinicia a cada
  // visita: quando zera, o preço muda mesmo.
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
