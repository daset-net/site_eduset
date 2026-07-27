/* unidades.js — busca e filtro da lista de unidades (unidades.php).
   A lista inteira já vem renderizada pelo PHP; aqui só se esconde o que não
   corresponde ao que o visitante digitou. Sem requisição, sem espera. */
(function () {
  'use strict';

  // Header ganha sombra ao rolar (mesmo comportamento das outras páginas).
  var header = document.getElementById('header');
  var onScroll = function () {
    if (header) header.classList.toggle('scrolled', window.scrollY > 40);
  };
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  var campo = document.getElementById('unid-filtro');
  var chips = document.getElementById('unid-ufs');
  if (!campo && !chips) return;

  var grupos = Array.prototype.slice.call(document.querySelectorAll('.unid-grupo'));
  var vazio  = document.getElementById('unid-vazio');
  var uf     = 'todos';

  // "Camaçari" e "camacari" têm de dar no mesmo resultado.
  var simples = function (texto) {
    return texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
  };

  var aplicar = function () {
    var termo = simples(campo ? campo.value : '');
    var achou = 0;

    grupos.forEach(function (grupo) {
      var visiveis = 0;

      Array.prototype.forEach.call(grupo.querySelectorAll('.unid-card'), function (card) {
        var casaUf    = uf === 'todos' || card.dataset.uf === uf;
        var casaTermo = termo === '' || (card.dataset.busca || '').indexOf(termo) !== -1;
        var mostra    = casaUf && casaTermo;

        card.hidden = !mostra;
        if (mostra) visiveis++;
      });

      grupo.hidden = visiveis === 0;
      achou += visiveis;
    });

    if (vazio) vazio.hidden = achou > 0;
  };

  if (campo) campo.addEventListener('input', aplicar);

  if (chips) {
    chips.addEventListener('click', function (ev) {
      var botao = ev.target.closest('button[data-uf]');
      if (!botao) return;

      uf = botao.dataset.uf;
      Array.prototype.forEach.call(chips.querySelectorAll('button'), function (b) {
        b.classList.toggle('ativo', b === botao);
      });
      aplicar();
    });
  }

  aplicar();
})();
