/**
 * intl-phone.js — Seletor de DDI (código de país) para campos de telefone/celular
 * Brasil (BR +55) vem pré-selecionado. Quando país ≠ BR, a máscara brasileira é removida.
 *
 * Uso (jQuery):
 *   var phone = IntlPhone.init('#campo_celular', { onCountryChange: fn });
 *   phone.getDDI();            // '55'
 *   phone.getPaisISO();        // 'BR'
 *   phone.getNumeroCompleto(); // '5511999998888'
 *
 * Uso (vanilla):
 *   var phone = IntlPhone.init(document.getElementById('campo'), {});
 *
 * Uso (Vue — retorna dados reativos):
 *   IntlPhone.paises  → array para v-for
 *   IntlPhone.mascaraPorPais(iso, valor) → string formatada
 */
var IntlPhone = (function () {
  'use strict';

  var paises = [
    { iso: 'BR', ddi: '55',  emoji: '🇧🇷', nome: 'Brasil' },
    { iso: 'PT', ddi: '351', emoji: '🇵🇹', nome: 'Portugal' },
    { iso: 'AO', ddi: '244', emoji: '🇦🇴', nome: 'Angola' },
    { iso: 'MZ', ddi: '258', emoji: '🇲🇿', nome: 'Moçambique' },
    { iso: 'CV', ddi: '238', emoji: '🇨🇻', nome: 'Cabo Verde' },
    { iso: 'GW', ddi: '245', emoji: '🇬🇼', nome: 'Guiné-Bissau' },
    { iso: 'ST', ddi: '239', emoji: '🇸🇹', nome: 'São Tomé e Príncipe' },
    { iso: 'TL', ddi: '670', emoji: '🇹🇱', nome: 'Timor-Leste' },
    { iso: 'US', ddi: '1',   emoji: '🇺🇸', nome: 'Estados Unidos' },
    { iso: 'AR', ddi: '54',  emoji: '🇦🇷', nome: 'Argentina' },
    { iso: 'CL', ddi: '56',  emoji: '🇨🇱', nome: 'Chile' },
    { iso: 'CO', ddi: '57',  emoji: '🇨🇴', nome: 'Colômbia' },
    { iso: 'MX', ddi: '52',  emoji: '🇲🇽', nome: 'México' },
    { iso: 'PE', ddi: '51',  emoji: '🇵🇪', nome: 'Peru' },
    { iso: 'UY', ddi: '598', emoji: '🇺🇾', nome: 'Uruguai' },
    { iso: 'PY', ddi: '595', emoji: '🇵🇾', nome: 'Paraguai' },
    { iso: 'BO', ddi: '591', emoji: '🇧🇴', nome: 'Bolívia' },
    { iso: 'EC', ddi: '593', emoji: '🇪🇨', nome: 'Equador' },
    { iso: 'VE', ddi: '58',  emoji: '🇻🇪', nome: 'Venezuela' },
    { iso: 'DE', ddi: '49',  emoji: '🇩🇪', nome: 'Alemanha' },
    { iso: 'ES', ddi: '34',  emoji: '🇪🇸', nome: 'Espanha' },
    { iso: 'FR', ddi: '33',  emoji: '🇫🇷', nome: 'França' },
    { iso: 'GB', ddi: '44',  emoji: '🇬🇧', nome: 'Reino Unido' },
    { iso: 'IT', ddi: '39',  emoji: '🇮🇹', nome: 'Itália' },
    { iso: 'JP', ddi: '81',  emoji: '🇯🇵', nome: 'Japão' },
    { iso: 'CA', ddi: '1',   emoji: '🇨🇦', nome: 'Canadá' },
    { iso: 'IN', ddi: '91',  emoji: '🇮🇳', nome: 'Índia' },
    { iso: 'CN', ddi: '86',  emoji: '🇨🇳', nome: 'China' },
    { iso: 'KR', ddi: '82',  emoji: '🇰🇷', nome: 'Coreia do Sul' },
    { iso: 'AU', ddi: '61',  emoji: '🇦🇺', nome: 'Austrália' }
  ];

  // ---- CSS (injetado uma única vez) ----
  var cssInjetado = false;
  function injetarCSS() {
    if (cssInjetado) return;
    cssInjetado = true;
    var style = document.createElement('style');
    style.textContent =
      '.intl-phone-wrap{display:flex;align-items:stretch;gap:0;}' +
      '.intl-phone-wrap .intl-phone-ddi{' +
        'flex:0 0 auto;min-width:110px;max-width:140px;' +
        'padding:6px 8px;font-size:14px;' +
        'border:1px solid #ced4da;border-right:none;' +
        'border-radius:6px 0 0 6px;background:#f8f9fa;' +
        'cursor:pointer;appearance:auto;' +
      '}' +
      '.intl-phone-wrap .intl-phone-ddi:focus{outline:none;border-color:#2f74f0;box-shadow:0 0 0 3px rgba(47,116,240,.12);}' +
      '.intl-phone-wrap .intl-phone-input{' +
        'flex:1 1 auto;min-width:0;' +
        'border-radius:0 6px 6px 0 !important;' +
      '}' +
      /* Variante para sites (campo .field) */
      '.field .intl-phone-wrap .intl-phone-ddi{' +
        'padding:13px 10px;font-size:15px;' +
        'border:1px solid var(--line,#ced4da);border-right:none;' +
        'border-radius:12px 0 0 12px;background:var(--bg-soft,#f8f9fa);' +
      '}' +
      '.field .intl-phone-wrap .intl-phone-input{border-radius:0 12px 12px 0 !important;}' +
      '.field .intl-phone-wrap .intl-phone-ddi:focus{border-color:var(--blue-400,#2f74f0);box-shadow:0 0 0 4px rgba(47,116,240,.12);background:#fff;}' +
      /* Variante para parceria (par-field) */
      '.par-field .intl-phone-wrap .intl-phone-ddi{' +
        'padding:12px 10px;font-size:15px;' +
        'border:1px solid var(--line,#ced4da);border-right:none;' +
        'border-radius:12px 0 0 12px;background:var(--bg-soft,#f8f9fa);' +
      '}' +
      '.par-field .intl-phone-wrap .intl-phone-input{border-radius:0 12px 12px 0 !important;}';
    document.head.appendChild(style);
  }

  // ---- Máscara brasileira ----
  function mascaraBR(v) {
    var d = (v || '').replace(/\D/g, '').slice(0, 11);
    if (d.length > 10) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
    if (d.length > 6)  return '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
    if (d.length > 2)  return '(' + d.slice(0, 2) + ') ' + d.slice(2);
    return d;
  }

  // ---- Máscara genérica (só dígitos, max 15) ----
  function mascaraGenerica(v) {
    return (v || '').replace(/\D/g, '').slice(0, 15);
  }

  // ---- Para uso em Vue: retorna valor formatado conforme país ----
  function mascaraPorPais(iso, valor) {
    if (iso === 'BR') return mascaraBR(valor);
    return mascaraGenerica(valor);
  }

  // ---- Buscar país por ISO ----
  function getPais(iso) {
    for (var i = 0; i < paises.length; i++) {
      if (paises[i].iso === iso) return paises[i];
    }
    return paises[0]; // fallback Brasil
  }

  // ---- Criar o select HTML ----
  function criarSelect(paisInicial) {
    var select = document.createElement('select');
    select.className = 'intl-phone-ddi';
    select.setAttribute('aria-label', 'Código do país');
    for (var i = 0; i < paises.length; i++) {
      var opt = document.createElement('option');
      opt.value = paises[i].iso;
      opt.textContent = paises[i].emoji + ' ' + paises[i].iso + ' +' + paises[i].ddi;
      if (paises[i].iso === paisInicial) opt.selected = true;
      select.appendChild(opt);
    }
    return select;
  }

  // ---- Inicializar o componente ----
  function init(inputEl, opcoes) {
    if (typeof inputEl === 'string') {
      inputEl = document.querySelector(inputEl);
    }
    if (!inputEl) return null;

    opcoes = opcoes || {};
    var paisInicial = opcoes.paisInicial || 'BR';

    injetarCSS();

    // Criar wrapper
    var wrapper = document.createElement('div');
    wrapper.className = 'intl-phone-wrap';

    // Criar select
    var select = criarSelect(paisInicial);

    // Inserir wrapper no lugar do input
    inputEl.parentNode.insertBefore(wrapper, inputEl);
    wrapper.appendChild(select);
    wrapper.appendChild(inputEl);

    // Adicionar classe ao input
    inputEl.classList.add('intl-phone-input');

    // Ajustar placeholder e máscara conforme país
    function ajustarPais() {
      var iso = select.value;
      if (iso === 'BR') {
        inputEl.placeholder = '(00) 00000-0000';
        inputEl.maxLength = 15;
      } else {
        inputEl.placeholder = 'Número do celular';
        inputEl.maxLength = 15;
      }
      // Reformatar valor atual
      var digits = (inputEl.value || '').replace(/\D/g, '');
      if (iso === 'BR') {
        inputEl.value = mascaraBR(digits);
      } else {
        inputEl.value = digits;
      }
      // Atualizar hidden DDI
      if (hiddenDDI) hiddenDDI.value = getPais(iso).ddi;
      if (typeof opcoes.onCountryChange === 'function') {
        opcoes.onCountryChange(getPais(iso));
      }
    }

    // Evento de mudança do select
    select.addEventListener('change', ajustarPais);

    // Evento de input — aplicar máscara
    inputEl.addEventListener('input', function () {
      var pos = inputEl.selectionStart === inputEl.value.length;
      if (select.value === 'BR') {
        inputEl.value = mascaraBR(inputEl.value);
      } else {
        inputEl.value = mascaraGenerica(inputEl.value);
      }
      if (pos) {
        try { inputEl.setSelectionRange(inputEl.value.length, inputEl.value.length); } catch(e) {}
      }
    });

    // Hidden input para o DDI (para envio via form)
    var hiddenDDI = document.createElement('input');
    hiddenDDI.type = 'hidden';
    hiddenDDI.name = (inputEl.name || 'celular') + '_ddi';
    hiddenDDI.value = getPais(paisInicial).ddi;
    wrapper.appendChild(hiddenDDI);

    // Ajuste inicial
    ajustarPais();

    // API pública
    return {
      select: select,
      input: inputEl,
      wrapper: wrapper,
      getDDI: function () { return getPais(select.value).ddi; },
      getPaisISO: function () { return select.value; },
      getNumeroCompleto: function () {
        var ddi = getPais(select.value).ddi;
        var num = (inputEl.value || '').replace(/\D/g, '');
        return ddi + num;
      },
      setPais: function (iso) {
        select.value = iso;
        ajustarPais();
      },
      getDigitos: function () {
        return (inputEl.value || '').replace(/\D/g, '');
      }
    };
  }

  // ---- API pública do módulo ----
  return {
    paises: paises,
    init: init,
    mascaraBR: mascaraBR,
    mascaraGenerica: mascaraGenerica,
    mascaraPorPais: mascaraPorPais,
    getPais: getPais
  };
})();
