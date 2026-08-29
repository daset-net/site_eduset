<?php
/** Página pública compartilhada: programa de afiliados ou abertura de unidade. */
require __DIR__ . '/api/_catalogo.php';

$tipo = ($tipo ?? '') === 'unidade' ? 'unidade' : 'afiliado';
$marca = $marca ?? 'Instituição de Ensino';
$logoNegativa = $logoNegativa ?? 'assets/img/logo-negativo.png';
$logoNormal = $logoNormal ?? 'assets/img/logo.png';
$corTema = $corTema ?? '#1d4ed8';
$ano = date('Y');
$ehAfiliado = $tipo === 'afiliado';
$titulo = $ehAfiliado ? 'Programa de Afiliados' : 'Abra sua Unidade';
$interesse = $ehAfiliado ? 'Programa de afiliados' : 'Abertura de nova unidade';
$mensagemWhatsapp = $ehAfiliado
  ? 'Olá! Quero saber mais sobre o programa de afiliados da ' . $marca . '.'
  : 'Olá! Quero saber mais sobre como abrir uma unidade da ' . $marca . '.';
// Parceria é analisada pela central da escola, nunca pelo polo guardado no cookie
// de uma campanha de matrícula.
$numeroCentral = preg_replace('/\D/', '', config('whatsapp', '5500000000000'));
$whatsapp = 'https://wa.me/' . $numeroCentral . '?text=' . rawurlencode($mensagemWhatsapp);

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($ehAfiliado ? 'Indique alunos, acompanhe suas matrículas e receba comissões com o programa de afiliados da ' . $marca . '.' : 'Leve a ' . $marca . ' para sua cidade e empreenda no setor educacional com estrutura e suporte.') ?>">
  <meta name="theme-color" content="<?= e($corTema) ?>">
  <title><?= e($titulo . ' · ' . $marca) ?></title>
  <link rel="icon" href="assets/img/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <link rel="stylesheet" href="<?= versao('assets/css/style.css') ?>">
  <style>
    .par-page { background:#fff; }
    .par-header { position:relative; z-index:20; background:var(--grad-hero); border-bottom:1px solid rgba(255,255,255,.12); }
    .par-header .header__inner { min-height:76px; }
    .par-header .brand__cor { display:none!important; }
    .par-header .brand__neg { display:block!important; }
    .par-header .nav a { color:rgba(255,255,255,.84); }
    .par-header .nav a:hover,.par-header .nav a.ativo { color:#fff; }
    .par-menu { display:none; border:0; background:transparent; color:#fff; font-size:26px; cursor:pointer; }
    .par-hero { background:var(--grad-hero); color:#fff; padding:72px 0 84px; position:relative; overflow:hidden; }
    .par-hero:after { content:""; position:absolute; width:460px; height:460px; border-radius:50%; right:-150px; top:-190px; background:rgba(255,255,255,.09); }
    .par-hero__grid { position:relative; z-index:1; display:grid; grid-template-columns:1.15fr .85fr; gap:60px; align-items:center; }
    .par-kicker { display:inline-flex; align-items:center; gap:8px; padding:7px 12px; border:1px solid rgba(255,255,255,.3); border-radius:999px; font-size:13px; font-weight:600; background:rgba(255,255,255,.08); }
    .par-hero h1 { font-size:clamp(36px,5vw,62px); line-height:1.08; margin:20px 0; letter-spacing:-.035em; }
    .par-hero .lead { max-width:670px; color:rgba(255,255,255,.82); font-size:18px; }
    .par-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:28px; }
    .par-btn-light { background:#fff; color:var(--ink); }
    .par-btn-ghost { color:#fff; border:1px solid rgba(255,255,255,.38); }
    .par-visual { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .par-metric { padding:22px; border:1px solid rgba(255,255,255,.18); border-radius:18px; background:rgba(255,255,255,.1); backdrop-filter:blur(10px); }
    .par-metric:first-child { grid-column:1/-1; }
    .par-metric i { font-size:28px; color:#fff; }
    .par-metric strong { display:block; margin-top:12px; font-size:17px; }
    .par-metric span { display:block; margin-top:4px; color:rgba(255,255,255,.72); font-size:13px; }
    .par-section { padding:82px 0; }
    .par-section--soft { background:var(--bg-soft); }
    .par-head { max-width:700px; text-align:center; margin:0 auto 42px; }
    .par-head small { color:var(--brand-600,#2563eb); font-weight:700; text-transform:uppercase; letter-spacing:.09em; }
    .par-head h2 { font-size:clamp(28px,4vw,42px); line-height:1.2; margin:10px 0 12px; }
    .par-head p { color:var(--muted); }
    .par-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; }
    .par-card { border:1px solid var(--line); border-radius:18px; padding:26px; background:#fff; box-shadow:var(--shadow-sm); }
    .par-card .ic { width:48px; height:48px; border-radius:13px; display:grid; place-items:center; background:var(--bg-soft); color:var(--brand-700,#1d4ed8); font-size:24px; }
    .par-card h3 { font-size:17px; margin:16px 0 7px; }
    .par-card p { color:var(--muted); font-size:14px; }
    .par-steps { counter-reset:passo; display:grid; grid-template-columns:repeat(4,1fr); gap:18px; }
    .par-step { counter-increment:passo; position:relative; padding:22px; border-radius:16px; background:#fff; border:1px solid var(--line); }
    .par-step:before { content:counter(passo); display:grid; place-items:center; width:34px; height:34px; border-radius:50%; background:var(--grad-brand); color:#fff; font-weight:700; margin-bottom:14px; }
    .par-step h3 { font-size:15px; margin-bottom:5px; }
    .par-step p { font-size:13px; color:var(--muted); }
    .par-form-wrap { display:grid; grid-template-columns:.78fr 1.22fr; gap:42px; align-items:start; }
    .par-form-copy { padding-top:12px; }
    .par-form-copy h2 { font-size:clamp(28px,4vw,40px); line-height:1.2; margin-bottom:14px; }
    .par-form-copy p { color:var(--muted); margin-bottom:22px; }
    .par-checks li { display:flex; gap:10px; margin:12px 0; font-size:14px; }
    .par-checks i { color:var(--brand-600,#2563eb); font-size:20px; }
    .par-form { background:#fff; border:1px solid var(--line); border-radius:22px; padding:30px; box-shadow:var(--shadow-md); }
    .par-fields { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
    .par-field.full { grid-column:1/-1; }
    .par-field label { display:block; font-size:12px; font-weight:700; margin-bottom:6px; color:var(--ink); }
    .par-field input,.par-field select,.par-field textarea { width:100%; border:1px solid var(--line); border-radius:10px; padding:11px 12px; font:inherit; font-size:14px; color:var(--ink); background:#fff; outline:none; }
    .par-field textarea { min-height:105px; resize:vertical; }
    .par-field input:focus,.par-field select:focus,.par-field textarea:focus { border-color:var(--brand-500,#2563eb); box-shadow:0 0 0 3px color-mix(in srgb,var(--brand-500,#2563eb) 14%,transparent); }
    .par-consent { display:flex; align-items:flex-start; gap:9px; font-size:12px; color:var(--muted); margin:18px 0; }
    .par-consent input { margin-top:3px; }
    .par-submit { width:100%; justify-content:center; border:0; }
    .par-status { display:none; margin-top:14px; border-radius:10px; padding:12px 14px; font-size:13px; }
    .par-status.ok { display:block; background:#ecfdf5; color:#047857; }
    .par-status.erro { display:block; background:#fef2f2; color:#b91c1c; }
    .par-hp { position:absolute!important; left:-9999px!important; opacity:0!important; }
    .par-alt { margin-top:14px; text-align:center; font-size:12px; color:var(--muted); }
    .par-alt a { color:var(--brand-700,#1d4ed8); font-weight:600; }
    @media(max-width:900px){ .par-hero__grid,.par-form-wrap{grid-template-columns:1fr}.par-visual{max-width:560px}.par-grid{grid-template-columns:1fr 1fr}.par-steps{grid-template-columns:1fr 1fr}.par-header .nav{display:none}.par-menu{display:block} }
    @media(max-width:600px){ .par-hero{padding:52px 0 64px}.par-visual,.par-grid,.par-steps,.par-fields{grid-template-columns:1fr}.par-metric:first-child,.par-field.full{grid-column:auto}.par-form{padding:21px}.par-header .header__cta .btn{display:none} }
  </style>
</head>
<body class="par-page">
<header class="header par-header">
  <div class="container header__inner">
    <a href="index.php" class="brand"><img class="brand__neg" src="<?= e($logoNegativa) ?>" alt="<?= e($marca) ?>"><img class="brand__cor" src="<?= e($logoNormal) ?>" alt="<?= e($marca) ?>"></a>
    <nav class="nav"><a href="index.php">Início</a><a href="index.php#cursos">Cursos</a><a href="unidades.php">Unidades</a><a href="afiliados.php"<?= $ehAfiliado ? ' class="ativo"' : '' ?>>Afiliados</a><a href="seja-uma-unidade.php"<?= !$ehAfiliado ? ' class="ativo"' : '' ?>>Abra sua unidade</a></nav>
    <div class="header__cta"><a href="#candidatura" class="btn btn-primary">Quero participar</a></div>
  </div>
</header>

<main>
  <section class="par-hero">
    <div class="container par-hero__grid">
      <div>
        <span class="par-kicker"><i class="<?= $ehAfiliado ? 'ri-hand-coin-line' : 'ri-community-line' ?>"></i> <?= e($titulo) ?></span>
        <h1><?= $ehAfiliado ? 'Transforme indicações em <strong>oportunidades</strong>' : 'Leve educação e oportunidades para <strong>sua cidade</strong>' ?></h1>
        <p class="lead"><?= $ehAfiliado ? 'Faça parte da rede de parceiros da ' . e($marca) . ', indique alunos, acompanhe suas matrículas e receba comissões pelas vendas realizadas.' : 'Empreenda com a ' . e($marca) . ' e conte com plataforma, catálogo de cursos e suporte para desenvolver uma operação educacional na sua região.' ?></p>
        <div class="par-actions"><a href="#candidatura" class="btn par-btn-light">Enviar candidatura <i class="ri-arrow-right-line"></i></a><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn par-btn-ghost"><i class="ri-whatsapp-line"></i> Tirar dúvidas</a></div>
      </div>
      <div class="par-visual">
        <?php if ($ehAfiliado): ?>
          <div class="par-metric"><i class="ri-links-line"></i><strong>Indique de qualquer lugar</strong><span>Atuação digital com apoio do painel do afiliado.</span></div>
          <div class="par-metric"><i class="ri-line-chart-line"></i><strong>Acompanhe</strong><span>Veja alunos e comissões.</span></div>
          <div class="par-metric"><i class="ri-wallet-3-line"></i><strong>Receba</strong><span>Conta virtual e saque.</span></div>
        <?php else: ?>
          <div class="par-metric"><i class="ri-school-line"></i><strong>Negócio educacional estruturado</strong><span>Uma operação local apoiada por tecnologia e portfólio de cursos.</span></div>
          <div class="par-metric"><i class="ri-customer-service-2-line"></i><strong>Suporte</strong><span>Apoio operacional.</span></div>
          <div class="par-metric"><i class="ri-map-pin-line"></i><strong>Presença local</strong><span>Atenda sua região.</span></div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="par-section">
    <div class="container">
      <div class="par-head"><small>Por que participar</small><h2><?= $ehAfiliado ? 'Uma parceria simples, transparente e digital' : 'Estrutura para você focar no crescimento local' ?></h2><p><?= $ehAfiliado ? 'Você aproxima novos alunos da formação que procuram; a plataforma organiza o restante do caminho.' : 'A unidade combina o relacionamento da sua região com a estrutura acadêmica e tecnológica da rede.' ?></p></div>
      <div class="par-grid">
        <?php $beneficios = $ehAfiliado ? [
          ['ri-user-add-line','Cadastro de alunos','Matricule e acompanhe os alunos indicados no seu próprio painel.'],
          ['ri-percent-line','Comissões por categoria','As condições são definidas no contrato da parceria.'],
          ['ri-bank-card-line','Conta virtual','Acompanhe os créditos e solicite saques com segurança.'],
        ] : [
          ['ri-book-open-line','Portfólio de cursos','Ofereça modalidades e formações disponibilizadas pela rede.'],
          ['ri-dashboard-3-line','Painel de gestão','Gerencie matrículas, alunos e operação em um só ambiente.'],
          ['ri-team-line','Apoio da rede','Conte com processos e suporte para iniciar sua unidade.'],
        ]; foreach ($beneficios as [$icone,$tit,$txt]): ?>
          <article class="par-card"><div class="ic"><i class="<?= $icone ?>"></i></div><h3><?= e($tit) ?></h3><p><?= e($txt) ?></p></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="par-section par-section--soft">
    <div class="container">
      <div class="par-head"><small>Como funciona</small><h2>Da candidatura ao início da parceria</h2></div>
      <div class="par-steps">
        <article class="par-step"><h3>Envie seus dados</h3><p>Preencha o formulário com seus contatos e sua região.</p></article>
        <article class="par-step"><h3>Análise da escola</h3><p>A equipe avalia o perfil e a disponibilidade na sua localidade.</p></article>
        <article class="par-step"><h3>Conversa e condições</h3><p>Você conhece as regras, percentuais e responsabilidades.</p></article>
        <article class="par-step"><h3>Ativação</h3><p>Com a aprovação e o contrato, seu acesso é liberado.</p></article>
      </div>
    </div>
  </section>

  <section class="par-section" id="candidatura">
    <div class="container par-form-wrap">
      <div class="par-form-copy">
        <h2><?= $ehAfiliado ? 'Quero ser afiliado' : 'Quero abrir uma unidade' ?></h2>
        <p>Preencha os dados abaixo. Sua candidatura será enviada diretamente para a equipe da <?= e($marca) ?>.</p>
        <ul class="par-checks"><li><i class="ri-check-line"></i><span>Envio seguro para o sistema da escola</span></li><li><i class="ri-check-line"></i><span>Análise sem compromisso</span></li><li><i class="ri-check-line"></i><span>Retorno pelos contatos informados</span></li></ul>
      </div>
      <form class="par-form" id="parForm">
        <input class="par-hp" type="text" name="empresa_site" tabindex="-1" autocomplete="off">
        <div class="par-fields">
          <div class="par-field full"><label for="nome">Nome completo *</label><input id="nome" name="nome" required minlength="3" maxlength="120" autocomplete="name"></div>
          <div class="par-field"><label for="email">E-mail *</label><input id="email" name="email" type="email" required maxlength="160" autocomplete="email"></div>
          <div class="par-field"><label for="telefone">WhatsApp *</label><input id="telefone" name="telefone" type="tel" required minlength="10" maxlength="20" autocomplete="tel" placeholder="(00) 00000-0000"></div>
          <div class="par-field"><label for="cidade">Cidade *</label><input id="cidade" name="cidade" required maxlength="100" autocomplete="address-level2"></div>
          <div class="par-field"><label for="estado">Estado *</label><select id="estado" name="estado" required><option value="">Selecione...</option><?php foreach (['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?><option><?= $uf ?></option><?php endforeach; ?></select></div>
          <div class="par-field full"><label for="experiencia"><?= $ehAfiliado ? 'Como pretende divulgar os cursos?' : 'Conte sobre sua experiência e estrutura atual' ?></label><textarea id="experiencia" name="experiencia" maxlength="1200" placeholder="<?= $ehAfiliado ? 'Redes sociais, contatos, atuação comercial...' : 'Experiência comercial ou educacional, espaço disponível, região de atuação...' ?>"></textarea></div>
        </div>
        <label class="par-consent"><input type="checkbox" name="consentimento" required><span>Autorizo o contato da <?= e($marca) ?> sobre esta candidatura e confirmo que os dados informados são verdadeiros.</span></label>
        <button class="btn btn-primary par-submit" type="submit" id="parSubmit">Enviar candidatura <i class="ri-send-plane-line"></i></button>
        <div class="par-status" id="parStatus" role="status"></div>
        <p class="par-alt">Prefere conversar agora? <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">Fale pelo WhatsApp</a></p>
      </form>
    </div>
  </section>
</main>

<footer class="footer"><div class="container"><div class="footer__bottom"><span>© <?= $ano ?> <?= e($marca) ?> · Todos os direitos reservados.</span><span><a href="index.php">Voltar ao site</a></span></div></div></footer>

<script>
document.getElementById('parForm').addEventListener('submit', async function (ev) {
  ev.preventDefault();
  const form = ev.currentTarget, btn = document.getElementById('parSubmit'), status = document.getElementById('parStatus');
  if (form.empresa_site.value) return;
  const original = btn.innerHTML;
  btn.disabled = true; btn.innerHTML = 'Enviando...'; status.className = 'par-status';
  const dados = new FormData(form);
  const mensagem = [
    'Tipo de candidatura: <?= e($titulo) ?>',
    'Cidade/UF: ' + dados.get('cidade') + '/' + dados.get('estado'),
    'Experiência/observações: ' + (dados.get('experiencia') || 'Não informado')
  ].join('\n');
  try {
    const r = await fetch('api/contato.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({ nome:dados.get('nome'), email:dados.get('email'), telefone:dados.get('telefone'), interesse:'<?= e($interesse) ?>', mensagem }) });
    const j = await r.json();
    if (!r.ok || !j.ok) throw new Error(j.mensagem || 'Não foi possível enviar.');
    status.className = 'par-status ok'; status.textContent = 'Candidatura enviada! Nossa equipe entrará em contato em breve.'; form.reset();
  } catch (e) { status.className = 'par-status erro'; status.textContent = e.message || 'Falha de comunicação. Tente novamente.'; }
  finally { btn.disabled = false; btn.innerHTML = original; }
});
</script>
</body>
</html>
