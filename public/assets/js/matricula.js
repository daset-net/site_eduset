/* matricula.js — formulário de matrícula online da página do curso.
   Envia para api/matricula.php, que valida de novo e assina a chamada ao AVASET.
   Curso, plano e valores NÃO saem daqui: quem decide isso é o servidor. */
(function () {
  'use strict';

  var form = document.getElementById('form-matricula');
  if (!form) return;

  var alerta   = document.getElementById('form-alerta');
  var sucesso  = document.getElementById('matricula-sucesso');
  var idCurso  = form.dataset.curso || '';

  // ---------------------------------------------------------------- afiliado
  // Link de indicação (?af=email) vale por 30 dias, para creditar a comissão.
  var AF_DIAS = 30;

  function guardarAfiliado() {
    var af = new URLSearchParams(location.search).get('af');
    if (!af || af.indexOf('@') === -1) return;
    var validade = new Date(Date.now() + AF_DIAS * 864e5).toUTCString();
    document.cookie = 'eduset_af=' + encodeURIComponent(af) + ';expires=' + validade + ';path=/;SameSite=Lax';
  }

  function afiliado() {
    var achado = document.cookie.match(/(?:^|;\s*)eduset_af=([^;]*)/);
    return achado ? decodeURIComponent(achado[1]) : '';
  }

  guardarAfiliado();

  // -------------------------------------------------------------------- polo
  // Link de divulgação de uma unidade (?polo=codigo). Quem guarda o cookie é o
  // PHP (_catalogo.php), que já lê o link na primeira página aberta; aqui só
  // levamos o código junto com a matrícula para o AVASET travar a unidade.
  function polo() {
    var achado = document.cookie.match(/(?:^|;\s*)eduset_polo=([^;]*)/);
    var valor = achado ? decodeURIComponent(achado[1]).toLowerCase() : '';
    return /^[a-z0-9._-]{2,80}$/.test(valor) ? valor : '';
  }

  // ------------------------------------------------------- foco na matrícula
  // Link de campanha com ?ir=matricula abre a página já no formulário: o lead
  // não precisa rolar nem procurar. O header é fixo, então descontamos a altura
  // dele para a seção não ficar escondida atrás da barra.
  function focarMatricula() {
    if (new URLSearchParams(location.search).get('ir') !== 'matricula') return;

    var secao = document.getElementById('matricula');
    if (!secao || window.pageYOffset > 200) return; // já rolou: não puxamos de volta

    var topo = document.querySelector('.header');
    var folga = (topo ? topo.offsetHeight : 0) + 16;
    window.scrollTo({
      top: secao.getBoundingClientRect().top + window.pageYOffset - folga,
      behavior: 'smooth'
    });

    // No celular não damos foco: abrir o teclado sozinho atrapalha a leitura.
    if (window.innerWidth > 720) {
      setTimeout(function () { form.nome.focus({ preventScroll: true }); }, 800);
    }
  }

  // 'load' e não DOMContentLoaded: com as imagens no lugar a conta do topo fecha.
  window.addEventListener('load', focarMatricula);

  // ---------------------------------------------------------------- máscaras
  function mascarar(campo, formatar) {
    if (!campo) return;
    campo.addEventListener('input', function () {
      var pos = campo.selectionStart === campo.value.length;
      campo.value = formatar(campo.value);
      if (pos) campo.setSelectionRange(campo.value.length, campo.value.length);
    });
  }

  function digitos(v, max) { return v.replace(/\D/g, '').slice(0, max); }

  var mascaraCpf = function (v) {
    var d = digitos(v, 11);
    return d.replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d{1,2})$/, '.$1-$2');
  };

  var mascaraData = function (v) {
    var d = digitos(v, 8);
    if (d.length > 4) return d.slice(0, 2) + '/' + d.slice(2, 4) + '/' + d.slice(4);
    if (d.length > 2) return d.slice(0, 2) + '/' + d.slice(2);
    return d;
  };

  var mascaraFone = function (v) {
    var d = digitos(v, 11);
    if (d.length > 10) return '(' + d.slice(0, 2) + ') ' + d.slice(2, 7) + '-' + d.slice(7);
    if (d.length > 6)  return '(' + d.slice(0, 2) + ') ' + d.slice(2, 6) + '-' + d.slice(6);
    if (d.length > 2)  return '(' + d.slice(0, 2) + ') ' + d.slice(2);
    return d;
  };

  var mascaraCep = function (v) {
    var d = digitos(v, 8);
    return d.length > 5 ? d.slice(0, 5) + '-' + d.slice(5) : d;
  };

  mascarar(form.cpf, mascaraCpf);
  mascarar(form.cpf_responsavel, mascaraCpf);
  mascarar(form.nascimento, mascaraData);
  var intlCelular = IntlPhone.init(form.celular, {});
  mascarar(form.cep, mascaraCep);

  form.estado.addEventListener('input', function () {
    form.estado.value = form.estado.value.replace(/[^A-Za-z]/g, '').toUpperCase().slice(0, 2);
  });

  // ------------------------------------------------------- responsável (<18)
  var blocoResp = document.getElementById('bloco-responsavel');

  form.nascimento.addEventListener('blur', function () {
    var partes = form.nascimento.value.split('/');
    if (partes.length !== 3 || partes[2].length !== 4) return;

    var nasc = new Date(+partes[2], partes[1] - 1, +partes[0]);
    var hoje = new Date();
    var idade = hoje.getFullYear() - nasc.getFullYear();
    var fezAniversario = hoje.getMonth() > nasc.getMonth()
      || (hoje.getMonth() === nasc.getMonth() && hoje.getDate() >= nasc.getDate());
    if (!fezAniversario) idade--;

    var menor = idade > 0 && idade < 18;
    blocoResp.hidden = !menor;
    form.nome_responsavel.required = menor;
    form.cpf_responsavel.required = menor;
  });

  // ------------------------------------------------------------------- CEP
  form.cep.addEventListener('blur', function () {
    var cep = form.cep.value.replace(/\D/g, '');
    if (cep.length !== 8) return;

    fetch('https://viacep.com.br/ws/' + cep + '/json/')
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (d.erro) return;
        if (!form.endereco.value) form.endereco.value = d.logradouro || '';
        if (!form.bairro.value)   form.bairro.value   = d.bairro || '';
        form.cidade.value = d.localidade || form.cidade.value;
        form.estado.value = d.uf || form.estado.value;
        if (!form.endereco.value) form.endereco.focus();
        else form.numero.focus();
      })
      .catch(function () { /* sem internet ou ViaCEP fora: o aluno digita à mão */ });
  });

  // ---------------------------------------------------------------- envio
  function mostrar(tipo, msg) {
    alerta.className = 'form-alert ' + tipo;
    alerta.textContent = msg;
    alerta.hidden = false;
    alerta.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();

    if (!form.checkValidity()) {
      mostrar('err', 'Preencha todos os campos obrigatórios.');
      form.reportValidity();
      return;
    }

    var botao = form.querySelector('button[type=submit]');
    var textoOriginal = botao.innerHTML;
    botao.disabled = true;
    botao.textContent = 'Enviando…';

    var dados = {
      id_curso: idCurso,
      nome: form.nome.value,
      cpf: form.cpf.value,
      nascimento: form.nascimento.value,
      sexo: form.sexo.value,
      email: form.email.value,
      celular: form.celular.value,
      celular_ddi: intlCelular ? intlCelular.getDDI() : '55',
      cep: form.cep.value,
      endereco: form.endereco.value,
      numero: form.numero.value,
      complemento: form.complemento.value,
      bairro: form.bairro.value,
      cidade: form.cidade.value,
      estado: form.estado.value,
      nome_responsavel: form.nome_responsavel.value,
      cpf_responsavel: form.cpf_responsavel.value,
      afiliado_email: afiliado(),
      polo: polo(),
      site: form.site.value
    };

    fetch('api/matricula.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(dados)
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) {
          mostrar('err', d.mensagem || 'Verifique os dados e tente novamente.');
          return;
        }
        document.getElementById('suc-nome').textContent    = form.nome.value.split(' ')[0];
        document.getElementById('suc-curso').textContent   = d.curso || '';
        document.getElementById('suc-numero').textContent  = d.matricula || '';
        document.getElementById('suc-usuario').textContent = d.usuario || '';
        document.getElementById('suc-senha').textContent   = d.senha || '';
        if (d.portal) document.getElementById('suc-portal').href = d.portal;

        form.hidden = true;
        sucesso.hidden = false;
        sucesso.scrollIntoView({ behavior: 'smooth', block: 'center' });
      })
      .catch(function () {
        mostrar('err', 'Não foi possível concluir agora. Tente novamente em instantes.');
      })
      .finally(function () {
        botao.disabled = false;
        botao.innerHTML = textoOriginal;
      });
  });
})();
