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
$titulo = $ehAfiliado ? 'Programa de Afiliados' : 'Unidade Flex';
$interesse = $ehAfiliado ? 'Programa de afiliados' : 'Abertura de nova unidade';
$mensagemWhatsapp = $ehAfiliado
  ? 'Olá! Quero saber mais sobre o programa de afiliados da ' . $marca . '.'
  : 'Olá! Quero saber mais sobre como abrir uma Unidade Flex da ' . $marca . '.';
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
    .flex-intro { margin-top:-36px; position:relative; z-index:3; }
    .flex-intro__box { display:grid; grid-template-columns:.8fr 1.2fr; gap:36px; align-items:center; padding:34px; border:1px solid var(--line); border-radius:24px; background:#fff; box-shadow:var(--shadow-md); }
    .flex-label { display:inline-flex; align-items:center; gap:8px; color:var(--brand-700,#1d4ed8); font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.09em; }
    .flex-intro h2 { margin:10px 0 0; font-size:clamp(26px,3vw,38px); line-height:1.16; }
    .flex-intro p { color:var(--muted); font-size:15px; }
    .flex-points { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:18px; }
    .flex-point { display:flex; gap:10px; align-items:flex-start; font-size:13px; color:var(--ink); }
    .flex-point i { flex:0 0 25px; width:25px; height:25px; display:grid; place-items:center; border-radius:8px; color:#fff; background:var(--grad-brand); }
    .flex-band { overflow:hidden; background:var(--ink); color:#fff; }
    .flex-band__grid { display:grid; grid-template-columns:1fr 1fr; gap:60px; align-items:center; }
    .flex-band h2 { color:#fff; font-size:clamp(30px,4vw,46px); line-height:1.15; margin:12px 0 16px; }
    .flex-band p { color:rgba(255,255,255,.7); }
    .flex-profile { display:grid; gap:12px; }
    .flex-profile__item { display:flex; gap:14px; padding:18px; border:1px solid rgba(255,255,255,.13); border-radius:15px; background:rgba(255,255,255,.06); }
    .flex-profile__item i { color:#fff; font-size:23px; }
    .flex-profile__item strong { display:block; font-size:14px; }
    .flex-profile__item span { display:block; margin-top:3px; color:rgba(255,255,255,.62); font-size:12px; }
    .flex-faq { max-width:820px; margin:0 auto; display:grid; gap:12px; }
    .flex-faq details { border:1px solid var(--line); border-radius:14px; background:#fff; padding:0 20px; }
    .flex-faq summary { cursor:pointer; list-style:none; padding:18px 30px 18px 0; position:relative; font-weight:600; font-size:14px; }
    .flex-faq summary:after { content:'+'; position:absolute; right:0; top:14px; font-size:23px; color:var(--brand-600,#2563eb); }
    .flex-faq details[open] summary:after { content:'−'; }
    .flex-faq details p { padding:0 0 18px; color:var(--muted); font-size:13px; }
    .aff-intro { margin-top:-36px; position:relative; z-index:3; }
    .aff-intro__box { padding:34px; border:1px solid var(--line); border-radius:24px; background:#fff; box-shadow:var(--shadow-md); }
    .aff-intro__grid { display:grid; grid-template-columns:.9fr 1.1fr; gap:42px; align-items:center; }
    .aff-intro h2 { margin:10px 0 12px; font-size:clamp(27px,3vw,39px); line-height:1.16; }
    .aff-intro p { color:var(--muted); font-size:14px; }
    .aff-flow { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; }
    .aff-flow__item { padding:18px 14px; border-radius:15px; background:var(--bg-soft); border:1px solid var(--line); }
    .aff-flow__item i { font-size:24px; color:var(--brand-700,#1d4ed8); }
    .aff-flow__item strong { display:block; margin:10px 0 3px; font-size:13px; }
    .aff-flow__item span { color:var(--muted); font-size:11px; }
    .aff-tools { display:grid; grid-template-columns:repeat(2,1fr); gap:16px; }
    .aff-tool { display:flex; gap:16px; padding:22px; border:1px solid var(--line); border-radius:17px; background:#fff; transition:.2s ease; }
    .aff-tool:hover { transform:translateY(-3px); box-shadow:var(--shadow-md); border-color:color-mix(in srgb,var(--brand-500,#2563eb) 35%,var(--line)); }
    .aff-tool__icon { flex:0 0 44px; width:44px; height:44px; display:grid; place-items:center; border-radius:13px; background:var(--grad-brand); color:#fff; font-size:22px; }
    .aff-tool h3 { margin:1px 0 6px; font-size:15px; }
    .aff-tool p { color:var(--muted); font-size:12px; }
    .aff-highlight { position:relative; overflow:hidden; background:var(--grad-hero); color:#fff; }
    .aff-highlight:after { content:''; position:absolute; width:380px; height:380px; border-radius:50%; right:-120px; bottom:-220px; background:rgba(255,255,255,.08); }
    .aff-highlight__grid { position:relative; z-index:1; display:grid; grid-template-columns:1fr 1fr; gap:50px; align-items:center; }
    .aff-highlight h2 { color:#fff; margin:10px 0 14px; font-size:clamp(29px,4vw,44px); line-height:1.16; }
    .aff-highlight p { color:rgba(255,255,255,.72); }
    .aff-values { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .aff-value { padding:19px; border:1px solid rgba(255,255,255,.15); border-radius:15px; background:rgba(255,255,255,.08); }
    .aff-value i { font-size:22px; }
    .aff-value strong { display:block; margin-top:8px; font-size:13px; }
    .aff-value span { display:block; margin-top:3px; color:rgba(255,255,255,.62); font-size:11px; }
    @media(max-width:900px){ .par-hero__grid,.par-form-wrap,.flex-intro__box,.flex-band__grid,.aff-intro__grid,.aff-highlight__grid{grid-template-columns:1fr}.par-visual{max-width:560px}.par-grid{grid-template-columns:1fr 1fr}.par-steps{grid-template-columns:1fr 1fr}.par-header .nav{display:none}.par-menu{display:block} }
    @media(max-width:600px){ .par-hero{padding:52px 0 64px}.par-visual,.par-grid,.par-steps,.par-fields,.flex-points,.aff-flow,.aff-tools,.aff-values{grid-template-columns:1fr}.par-metric:first-child,.par-field.full{grid-column:auto}.par-form{padding:21px}.par-header .header__cta .btn{display:none} }
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
        <h1><?= $ehAfiliado ? 'Sua influência pode abrir novos <strong>caminhos</strong>' : 'Uma unidade física. Uma operação mais <strong>flexível</strong>.' ?></h1>
        <p class="lead"><?= $ehAfiliado ? 'Conecte pessoas a novas oportunidades de formação, acompanhe cada indicação pelo seu painel e construa uma parceria transparente com a ' . e($marca) . '.' : 'Abra uma Unidade Flex da ' . e($marca) . ': presença física na sua cidade, estrutura planejada de acordo com a operação e tecnologia para atender, matricular e acompanhar seus alunos.' ?></p>
        <div class="par-actions"><a href="#candidatura" class="btn par-btn-light"><?= $ehAfiliado ? 'Enviar candidatura' : 'Quero abrir uma Unidade Flex' ?> <i class="ri-arrow-right-line"></i></a><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn par-btn-ghost"><i class="ri-whatsapp-line"></i> Falar com um consultor</a></div>
      </div>
      <div class="par-visual">
        <?php if ($ehAfiliado): ?>
          <div class="par-metric"><i class="ri-links-line"></i><strong>Um link que identifica suas indicações</strong><span>Compartilhe sua oportunidade e mantenha a origem de cada indicação organizada.</span></div>
          <div class="par-metric"><i class="ri-line-chart-line"></i><strong>Acompanhe</strong><span>Matrículas e comissões.</span></div>
          <div class="par-metric"><i class="ri-wallet-3-line"></i><strong>Gerencie</strong><span>Conta e solicitações.</span></div>
        <?php else: ?>
          <div class="par-metric"><i class="ri-store-2-line"></i><strong>Presença física, formato inteligente</strong><span>Um espaço real de acolhimento e atendimento, dimensionado para a sua realidade.</span></div>
          <div class="par-metric"><i class="ri-layout-masonry-line"></i><strong>Implantação flexível</strong><span>Comece com o essencial.</span></div>
          <div class="par-metric"><i class="ri-dashboard-3-line"></i><strong>Gestão integrada</strong><span>Operação no AVASET.</span></div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($ehAfiliado): ?>
  <section class="aff-intro"><div class="container"><div class="aff-intro__box"><div class="aff-intro__grid">
    <div><span class="flex-label"><i class="ri-sparkling-2-line"></i> Parceria inteligente</span><h2>Você indica. A plataforma organiza.</h2><p>O programa foi pensado para quem já conversa com pessoas, cria conteúdo, atua comercialmente ou simplesmente conhece alguém buscando uma nova formação.</p></div>
    <div class="aff-flow"><div class="aff-flow__item"><i class="ri-share-forward-line"></i><strong>Compartilhe</strong><span>Use seu link exclusivo.</span></div><div class="aff-flow__item"><i class="ri-user-follow-line"></i><strong>Conecte</strong><span>Apresente oportunidades.</span></div><div class="aff-flow__item"><i class="ri-bar-chart-box-line"></i><strong>Acompanhe</strong><span>Visualize seus resultados.</span></div></div>
  </div></div></div></section>
  <?php endif; ?>

  <?php if (!$ehAfiliado): ?>
  <section class="flex-intro">
    <div class="container">
      <div class="flex-intro__box">
        <div><span class="flex-label"><i class="ri-focus-3-line"></i> O conceito</span><h2>O físico que acompanha o seu ritmo</h2></div>
        <div><p>A Unidade Flex é uma unidade física de atendimento educacional com uma implantação mais adaptável. Você mantém presença real na cidade e organiza a estrutura de forma responsável, conforme o perfil da região e a evolução da operação.</p><div class="flex-points"><div class="flex-point"><i class="ri-check-line"></i><span>Atendimento presencial e humanizado</span></div><div class="flex-point"><i class="ri-check-line"></i><span>Estrutura planejada para cada fase</span></div><div class="flex-point"><i class="ri-check-line"></i><span>Tecnologia para gestão e matrículas</span></div><div class="flex-point"><i class="ri-check-line"></i><span>Orientação durante a implantação</span></div></div></div>
      </div>
    </div>
  </section>

  <?php if ($ehAfiliado): ?>
  <section class="par-section par-section--soft"><div class="container"><div class="par-head"><small>Seu ambiente de trabalho</small><h2>Recursos para uma parceria transparente</h2><p>Tenha acesso às informações essenciais para acompanhar sua atuação com clareza e autonomia.</p></div><div class="aff-tools">
    <article class="aff-tool"><div class="aff-tool__icon"><i class="ri-links-line"></i></div><div><h3>Link exclusivo de indicação</h3><p>Uma identificação própria para divulgar os cursos e registrar corretamente a origem das oportunidades.</p></div></article>
    <article class="aff-tool"><div class="aff-tool__icon"><i class="ri-group-line"></i></div><div><h3>Acompanhamento de alunos</h3><p>Consulte no painel os alunos relacionados à sua atuação e o andamento das matrículas.</p></div></article>
    <article class="aff-tool"><div class="aff-tool__icon"><i class="ri-pie-chart-line"></i></div><div><h3>Visão das comissões</h3><p>Acompanhe os valores apurados conforme as categorias e condições definidas na parceria.</p></div></article>
    <article class="aff-tool"><div class="aff-tool__icon"><i class="ri-secure-payment-line"></i></div><div><h3>Conta e solicitações de saque</h3><p>Visualize movimentações e solicite saques pelos recursos disponíveis no ambiente do afiliado.</p></div></article>
  </div></div></section>

  <section class="par-section aff-highlight"><div class="container aff-highlight__grid"><div><span class="flex-label" style="color:#fff"><i class="ri-fingerprint-line"></i> Do seu jeito</span><h2>Indique com autenticidade e responsabilidade</h2><p>Você escolhe como apresentar as oportunidades à sua rede, respeitando as informações oficiais dos cursos e as regras da parceria. A confiança de quem recebe sua indicação vem sempre em primeiro lugar.</p></div><div class="aff-values"><div class="aff-value"><i class="ri-smartphone-line"></i><strong>Atuação flexível</strong><span>Compartilhe pelos canais que fazem sentido para você.</span></div><div class="aff-value"><i class="ri-shield-check-line"></i><strong>Informação segura</strong><span>Divulgue condições e cursos com clareza.</span></div><div class="aff-value"><i class="ri-eye-line"></i><strong>Transparência</strong><span>Acompanhe sua atuação no painel.</span></div><div class="aff-value"><i class="ri-customer-service-2-line"></i><strong>Apoio</strong><span>Conte com os canais previstos para parceiros.</span></div></div></div></section>
  <?php endif; ?>
  <?php endif; ?>

  <section class="par-section">
    <div class="container">
      <div class="par-head"><small><?= $ehAfiliado ? 'Por que participar' : 'Estrutura para crescer' ?></small><h2><?= $ehAfiliado ? 'Uma parceria simples, transparente e digital' : 'Mais que uma unidade: uma operação conectada' ?></h2><p><?= $ehAfiliado ? 'Você aproxima novos alunos da formação que procuram; a plataforma organiza o restante do caminho.' : 'A Unidade Flex une relacionamento local, processos claros e recursos de gestão para você concentrar energia no atendimento e no desenvolvimento da sua região.' ?></p></div>
      <div class="par-grid">
        <?php $beneficios = $ehAfiliado ? [
          ['ri-user-add-line','Cadastro de alunos','Matricule e acompanhe os alunos indicados no seu próprio painel.'],
          ['ri-percent-line','Comissões por categoria','As condições são definidas no contrato da parceria.'],
          ['ri-bank-card-line','Conta virtual','Acompanhe os créditos e solicite saques com segurança.'],
        ] : [
          ['ri-book-open-line','Portfólio educacional','Apresente as modalidades e formações disponibilizadas pela instituição.'],
          ['ri-dashboard-3-line','Gestão com AVASET','Acompanhe matrículas, alunos e rotinas da unidade em um só ambiente.'],
          ['ri-customer-service-2-line','Implantação acompanhada','Receba orientação para organizar o início e conduzir a operação.'],
        ]; foreach ($beneficios as [$icone,$tit,$txt]): ?>
          <article class="par-card"><div class="ic"><i class="<?= $icone ?>"></i></div><h3><?= e($tit) ?></h3><p><?= e($txt) ?></p></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <?php if (!$ehAfiliado): ?>
  <section class="par-section flex-band">
    <div class="container flex-band__grid">
      <div><span class="flex-label" style="color:#fff"><i class="ri-user-star-line"></i> Perfil do parceiro</span><h2>Para quem quer construir presença na própria região</h2><p>Não é preciso chegar com uma grande operação pronta. Procuramos pessoas comprometidas com atendimento, organização e desenvolvimento local, dispostas a seguir os critérios acadêmicos e operacionais da instituição.</p></div>
      <div class="flex-profile">
        <div class="flex-profile__item"><i class="ri-community-line"></i><div><strong>Conexão com a cidade</strong><span>Conhece o público e deseja gerar oportunidades por meio da educação.</span></div></div>
        <div class="flex-profile__item"><i class="ri-service-line"></i><div><strong>Vocação para atender</strong><span>Valoriza proximidade, clareza e uma boa experiência para cada aluno.</span></div></div>
        <div class="flex-profile__item"><i class="ri-rocket-2-line"></i><div><strong>Visão de crescimento</strong><span>Quer começar de forma planejada e evoluir com consistência.</span></div></div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($ehAfiliado): ?>
  <section class="par-section"><div class="container"><div class="par-head"><small>Dúvidas frequentes</small><h2>Entenda o programa de afiliados</h2></div><div class="flex-faq">
    <details><summary>Preciso pagar para enviar minha candidatura?</summary><p>O envio da candidatura não cria cobrança nem garante aprovação. As condições da parceria são apresentadas pela equipe antes da formalização.</p></details>
    <details><summary>Como minhas indicações são identificadas?</summary><p>Depois da aprovação, você recebe um acesso e um link próprio. As matrículas realizadas a partir dessa identificação ficam relacionadas à sua conta conforme as regras do programa.</p></details>
    <details><summary>Como funcionam as comissões?</summary><p>Os percentuais e critérios podem variar conforme a categoria do curso e são definidos nas condições e no contrato da parceria. Não há promessa de renda ou resultado garantido.</p></details>
    <details><summary>Onde acompanho alunos e valores?</summary><p>No painel do afiliado, onde ficam disponíveis os recursos de acompanhamento vinculados à sua conta.</p></details>
    <details><summary>Posso divulgar em redes sociais?</summary><p>Sim, desde que a divulgação respeite as informações oficiais, a identidade da instituição e as regras apresentadas durante a ativação.</p></details>
  </div></div></section>
  <?php endif; ?>

  <section class="par-section par-section--soft">
    <div class="container">
      <div class="par-head"><small>Como funciona</small><h2><?= $ehAfiliado ? 'Da candidatura ao início da parceria' : 'Seu caminho até a abertura da Unidade Flex' ?></h2><p><?= $ehAfiliado ? '' : 'Cada candidatura é analisada individualmente para alinhar cidade, perfil, estrutura e condições da parceria.' ?></p></div>
      <div class="par-steps">
        <article class="par-step"><h3>Envie seus dados</h3><p>Preencha o formulário com seus contatos e sua região.</p></article>
        <article class="par-step"><h3><?= $ehAfiliado ? 'Análise da escola' : 'Análise da região' ?></h3><p>A equipe avalia o perfil e a disponibilidade na sua localidade.</p></article>
        <article class="par-step"><h3><?= $ehAfiliado ? 'Conversa e condições' : 'Plano de implantação' ?></h3><p><?= $ehAfiliado ? 'Você conhece as regras, percentuais e responsabilidades.' : 'Alinhamos estrutura, responsabilidades, documentação e próximos passos.' ?></p></article>
        <article class="par-step"><h3><?= $ehAfiliado ? 'Ativação' : 'Capacitação e abertura' ?></h3><p><?= $ehAfiliado ? 'Com a aprovação e o contrato, seu acesso é liberado.' : 'Após aprovação e contrato, a unidade recebe acesso e orientação para iniciar.' ?></p></article>
      </div>
    </div>
  </section>

  <?php if (!$ehAfiliado): ?>
  <section class="par-section">
    <div class="container"><div class="par-head"><small>Dúvidas frequentes</small><h2>Antes de dar o primeiro passo</h2></div><div class="flex-faq">
      <details><summary>A Unidade Flex é uma unidade física?</summary><p>Sim. É uma unidade com presença física na cidade, preparada para acolher, orientar e atender alunos. “Flex” descreve uma implantação mais adaptável e moderna, não uma operação exclusivamente digital.</p></details>
      <details><summary>Preciso ter uma grande estrutura pronta?</summary><p>Não necessariamente. A estrutura é avaliada conforme a região, o atendimento previsto e os requisitos aplicáveis. A equipe orientará o que é necessário antes da abertura.</p></details>
      <details><summary>Posso abrir uma Unidade Flex em qualquer cidade?</summary><p>A disponibilidade depende da análise territorial e estratégica da instituição. Informe sua cidade no formulário para que a equipe verifique a possibilidade.</p></details>
      <details><summary>Que suporte receberei?</summary><p>O parceiro recebe orientação de implantação, acesso aos processos e ao sistema de gestão, além dos canais de suporte definidos na formalização da parceria.</p></details>
      <details><summary>O envio da candidatura garante a aprovação?</summary><p>Não. A candidatura inicia o processo de análise. A abertura depende da aprovação, do alinhamento das condições e da formalização contratual.</p></details>
    </div></div>
  </section>
  <?php endif; ?>

  <section class="par-section" id="candidatura">
    <div class="container par-form-wrap">
      <div class="par-form-copy">
        <h2><?= $ehAfiliado ? 'Quero ser afiliado' : 'Quero abrir uma Unidade Flex' ?></h2>
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
          <?php if ($ehAfiliado): ?><div class="par-field full"><label for="canal_divulgacao">Principal canal de divulgação</label><select id="canal_divulgacao" name="canal_divulgacao"><option value="Não informado">Selecione...</option><option>Redes sociais</option><option>WhatsApp e contatos</option><option>Site ou blog</option><option>Atuação comercial presencial</option><option>Outro</option></select></div><?php endif; ?>
          <?php if (!$ehAfiliado): ?><div class="par-field"><label for="espaco">Já possui espaço físico?</label><select id="espaco" name="espaco"><option value="Não informado">Selecione...</option><option>Sim, já está pronto</option><option>Sim, precisa de adequações</option><option>Ainda estou procurando</option></select></div><div class="par-field"><label for="experiencia_educacional">Já atua na área educacional?</label><select id="experiencia_educacional" name="experiencia_educacional"><option value="Não informado">Selecione...</option><option>Sim</option><option>Não</option></select></div><?php endif; ?>
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
    <?php if ($ehAfiliado): ?>'Canal de divulgação: ' + dados.get('canal_divulgacao'),<?php endif; ?>
    <?php if (!$ehAfiliado): ?>'Espaço físico: ' + dados.get('espaco'),
    'Experiência educacional: ' + dados.get('experiencia_educacional'),<?php endif; ?>
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
