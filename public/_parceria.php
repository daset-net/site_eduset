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

// Prova social da rede. Hoje a coleção contém relatos de gestores de unidades;
// na página de afiliados eles aparecem identificados como parceiros da rede.
// A janela limitada evita trazer tudo; o embaralhamento renova três por visita.
$depoimentosUnidade = [];
$linhas = buscarColecao('unidade_depoimentos', [
    'fields' => 'nome,empresa,caso_de_sucesso,parceria,data',
    'filter' => ['parceria' => ['_eq' => 'unidade']],
    'sort'   => '-data',
    'limit'  => 120,
]) ?? [];
foreach ($linhas as $linha) {
    $nome = trim((string) ($linha['nome'] ?? ''));
    $empresa = trim((string) ($linha['empresa'] ?? ''));
    $relato = preg_replace('/\s+/u', ' ', trim((string) ($linha['caso_de_sucesso'] ?? '')));
    if ($nome === '' || $relato === '') continue;
    if (mb_strlen($relato) > 560) $relato = rtrim(mb_substr($relato, 0, 557)) . '…';
    $depoimentosUnidade[] = ['nome' => $nome, 'empresa' => $empresa, 'relato' => $relato];
}
if (count($depoimentosUnidade) > 1) shuffle($depoimentosUnidade);
$depoimentosUnidade = array_slice($depoimentosUnidade, 0, 3);

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
    .par-trust { display:flex; flex-wrap:wrap; gap:16px 24px; margin-top:24px; color:rgba(255,255,255,.76); font-size:12px; }
    .par-trust span { display:flex; align-items:center; gap:7px; }
    .par-trust i { color:#fff; font-size:17px; }
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
    .conviction { background:#fff; }
    .conviction__grid { display:grid; grid-template-columns:.82fr 1.18fr; gap:52px; align-items:start; }
    .conviction__copy { position:sticky; top:110px; }
    .conviction__copy h2 { margin:10px 0 15px; font-size:clamp(30px,4vw,44px); line-height:1.15; }
    .conviction__copy p { color:var(--muted); }
    .conviction__list { display:grid; gap:14px; }
    .conviction__item { display:grid; grid-template-columns:50px 1fr; gap:16px; padding:22px; border:1px solid var(--line); border-radius:17px; background:var(--bg-soft); }
    .conviction__item i { width:50px; height:50px; display:grid; place-items:center; border-radius:14px; background:#fff; color:var(--brand-700,#1d4ed8); box-shadow:var(--shadow-sm); font-size:24px; }
    .conviction__item h3 { margin:1px 0 6px; font-size:15px; }
    .conviction__item p { color:var(--muted); font-size:12px; }
    .compare { padding:32px; border-radius:22px; background:var(--ink); color:#fff; margin-top:22px; }
    .compare h3 { color:#fff; margin-bottom:18px; font-size:20px; }
    .compare__grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .compare__col { padding:18px; border-radius:14px; background:rgba(255,255,255,.07); }
    .compare__col.good { background:color-mix(in srgb,var(--brand-600,#2563eb) 38%,transparent); border:1px solid rgba(255,255,255,.14); }
    .compare__col strong { display:block; margin-bottom:10px; font-size:13px; }
    .compare__col span { display:flex; gap:7px; margin:8px 0; color:rgba(255,255,255,.7); font-size:11px; }
    .compare__col i { color:#fff; }
    .final-cta { padding:0 0 82px; background:var(--bg-soft); }
    .final-cta__box { position:relative; overflow:hidden; display:grid; grid-template-columns:1fr auto; gap:30px; align-items:center; padding:38px 42px; border-radius:24px; background:var(--grad-brand); color:#fff; box-shadow:var(--shadow-md); }
    .final-cta__box:after { content:''; position:absolute; width:220px; height:220px; border-radius:50%; right:-70px; top:-120px; background:rgba(255,255,255,.1); }
    .final-cta h2 { color:#fff; font-size:clamp(25px,3vw,35px); margin-bottom:7px; }
    .final-cta p { color:rgba(255,255,255,.76); font-size:13px; }
    .final-cta .btn { position:relative; z-index:1; white-space:nowrap; background:#fff; color:var(--ink); }
    .money-strip { padding:34px 0; background:var(--ink); color:#fff; }
    .money-strip__grid { display:grid; grid-template-columns:1.05fr repeat(3,1fr); gap:14px; align-items:stretch; }
    .money-strip__lead { padding:18px 24px 18px 0; }
    .money-strip__lead small { color:rgba(255,255,255,.58); text-transform:uppercase; letter-spacing:.09em; font-weight:700; }
    .money-strip__lead strong { display:block; margin-top:7px; color:#fff; font-size:clamp(28px,3vw,39px); line-height:1.08; }
    .money-card { padding:20px; border:1px solid rgba(255,255,255,.13); border-radius:16px; background:rgba(255,255,255,.07); }
    .money-card i { font-size:23px; }
    .money-card strong { display:block; margin:10px 0 4px; font-size:14px; }
    .money-card span { color:rgba(255,255,255,.62); font-size:11px; line-height:1.5; }
    .unit-stories { background:var(--bg-soft); }
    .unit-stories__grid { display:grid; grid-template-columns:repeat(3,1fr); gap:18px; }
    .unit-story { position:relative; display:flex; flex-direction:column; min-height:285px; padding:28px; border:1px solid var(--line); border-radius:20px; background:#fff; box-shadow:var(--shadow-sm); }
    .unit-story__quote { position:absolute; right:22px; top:17px; color:color-mix(in srgb,var(--brand-500,#2563eb) 18%,transparent); font-size:48px; }
    .unit-story__stars { display:flex; gap:2px; color:#f59e0b; font-size:15px; }
    .unit-story blockquote { flex:1; margin:20px 0 24px; color:var(--ink); font-size:13px; line-height:1.75; }
    .unit-story__person { display:flex; align-items:center; gap:12px; padding-top:17px; border-top:1px solid var(--line); }
    .unit-story__avatar { flex:0 0 42px; width:42px; height:42px; display:grid; place-items:center; border-radius:50%; background:var(--grad-brand); color:#fff; font-weight:700; }
    .unit-story__person strong { display:block; font-size:13px; }
    .unit-story__person span { display:block; margin-top:2px; color:var(--muted); font-size:10px; line-height:1.35; }
    @media(max-width:900px){ .par-hero__grid,.par-form-wrap,.flex-intro__box,.flex-band__grid,.aff-intro__grid,.aff-highlight__grid,.conviction__grid,.final-cta__box{grid-template-columns:1fr}.money-strip__grid{grid-template-columns:1fr 1fr}.money-strip__lead{grid-column:1/-1}.unit-stories__grid{grid-template-columns:1fr}.unit-story{min-height:0}.conviction__copy{position:static}.par-visual{max-width:560px}.par-grid{grid-template-columns:1fr 1fr}.par-steps{grid-template-columns:1fr 1fr}.par-header .nav{display:none}.par-menu{display:block} }
    @media(max-width:600px){ .par-hero{padding:52px 0 64px}.par-visual,.par-grid,.par-steps,.par-fields,.flex-points,.aff-flow,.aff-tools,.aff-values,.compare__grid,.money-strip__grid{grid-template-columns:1fr}.money-strip__lead{grid-column:auto}.par-metric:first-child,.par-field.full{grid-column:auto}.par-form{padding:21px}.final-cta__box{padding:28px 24px}.par-header .header__cta .btn{display:none} }
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
        <div class="par-trust"><span><i class="ri-shield-check-line"></i> Candidatura sem compromisso</span><span><i class="ri-file-list-3-line"></i> Condições formalizadas em contrato</span><span><i class="ri-customer-service-2-line"></i> Acompanhamento da equipe</span></div>
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

  <section class="money-strip"><div class="container money-strip__grid">
    <div class="money-strip__lead"><small>Ganhos e autonomia</small><strong><?= $ehAfiliado ? 'Ganhe 15%' : 'Ganhe até 50%' ?></strong></div>
    <div class="money-card"><i class="ri-percent-line"></i><strong><?= $ehAfiliado ? '15% de comissão' : 'Repasse de até 50%' ?></strong><span><?= $ehAfiliado ? 'Receba 15% de comissão nas matrículas elegíveis, conforme as condições estabelecidas no contrato.' : 'O percentual varia conforme a modalidade do curso e as condições estabelecidas no contrato.' ?></span></div>
    <div class="money-card"><i class="ri-flashlight-line"></i><strong>Crédito automático</strong><span><?= $ehAfiliado ? 'Quando o pagamento elegível é confirmado, sua comissão é calculada e creditada automaticamente.' : 'Quando o pagamento elegível é confirmado, o repasse da unidade é calculado e creditado automaticamente.' ?></span></div>
    <div class="money-card"><i class="ri-bank-card-line"></i><strong>PIX a qualquer hora</strong><span>Com saldo disponível e dados validados, solicite pelo painel a transferência para sua chave PIX, de dia ou de noite.</span></div>
  </div></section>

  <section class="par-section conviction">
    <div class="container conviction__grid">
      <div class="conviction__copy"><span class="flex-label"><i class="ri-lightbulb-flash-line"></i> <?= $ehAfiliado ? 'Uma oportunidade real' : 'Um modelo mais atual' ?></span><h2><?= $ehAfiliado ? 'Não basta indicar. É preciso conseguir acompanhar.' : 'Presença física sem começar maior do que precisa.' ?></h2><p><?= $ehAfiliado ? 'Uma boa parceria transforma sua capacidade de comunicação em um processo organizado, rastreável e transparente — do primeiro contato ao acompanhamento no painel.' : 'A Unidade Flex nasce para unir a confiança do atendimento presencial a uma estrutura de implantação racional, apoiada por tecnologia e processos.' ?></p>
        <div class="compare"><h3><?= $ehAfiliado ? 'O que muda para você' : 'Por que o formato Flex?' ?></h3><div class="compare__grid"><div class="compare__col"><strong><?= $ehAfiliado ? 'Indicação sem estrutura' : 'Modelo engessado' ?></strong><span><i class="ri-close-line"></i><?= $ehAfiliado ? 'Origem difícil de acompanhar' : 'Estrutura inicial desproporcional' ?></span><span><i class="ri-close-line"></i><?= $ehAfiliado ? 'Informações espalhadas' : 'Processos pouco adaptáveis' ?></span><span><i class="ri-close-line"></i><?= $ehAfiliado ? 'Pouca visibilidade do resultado' : 'Crescimento sem etapas claras' ?></span></div><div class="compare__col good"><strong><?= $ehAfiliado ? 'Programa de Afiliados' : 'Unidade Flex' ?></strong><span><i class="ri-check-line"></i><?= $ehAfiliado ? 'Link próprio de indicação' : 'Espaço físico dimensionado' ?></span><span><i class="ri-check-line"></i><?= $ehAfiliado ? 'Painel centralizado' : 'Gestão integrada ao AVASET' ?></span><span><i class="ri-check-line"></i><?= $ehAfiliado ? 'Regras e critérios definidos' : 'Implantação acompanhada' ?></span></div></div></div>
      </div>
      <div class="conviction__list">
        <?php $argumentos = $ehAfiliado ? [
          ['ri-radar-line','Transforme alcance em conexão','Você não precisa ter milhões de seguidores. Uma rede construída com confiança pode aproximar a pessoa certa da formação que ela procura.'],
          ['ri-route-line','Cada indicação com um caminho claro','Seu link identifica a origem, permite receber matrículas online e ajuda a manter o processo organizado, sem controles improvisados.'],
          ['ri-scales-3-line','Transparência desde o início','Critérios, percentuais e responsabilidades são apresentados antes da formalização da parceria.'],
          ['ri-time-line','Atue no seu ritmo','Organize sua divulgação de acordo com sua rotina e com os canais nos quais você já tem presença.'],
        ] : [
          ['ri-store-3-line','Uma referência local','Crie um ponto físico onde o aluno encontra orientação, acolhimento e confiança para iniciar sua jornada.'],
          ['ri-expand-left-right-line','Estrutura que pode evoluir','Comece com os requisitos definidos para a operação e amplie de forma planejada conforme a realidade local.'],
          ['ri-computer-line','Seu link trabalha com você','Divulgue seu link exclusivo de campanha e receba matrículas online já vinculadas à sua Unidade Flex.'],
          ['ri-map-pin-user-line','Impacto perto de casa','Ajude a ampliar o acesso à formação e desenvolva uma atuação educacional conectada à sua comunidade.'],
        ]; foreach ($argumentos as [$icone,$tit,$txt]): ?><article class="conviction__item"><i class="<?= $icone ?>"></i><div><h3><?= e($tit) ?></h3><p><?= e($txt) ?></p></div></article><?php endforeach; ?>
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
    <article class="aff-tool"><div class="aff-tool__icon"><i class="ri-pie-chart-line"></i></div><div><h3>15% de comissão</h3><p>Acompanhe pelo painel os valores apurados nas matrículas elegíveis vinculadas à sua conta.</p></div></article>
    <article class="aff-tool"><div class="aff-tool__icon"><i class="ri-secure-payment-line"></i></div><div><h3>Saque instantâneo via PIX</h3><p>Consulte suas movimentações e transfira o saldo disponível pelo painel a qualquer hora do dia ou da noite.</p></div></article>
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
          ['ri-percent-line','15% de comissão','Receba comissão nas matrículas elegíveis identificadas pelo seu link.'],
          ['ri-bank-card-line','Seu dinheiro, no seu tempo','Receba créditos automáticos e solicite a transferência via PIX quando quiser.'],
        ] : [
          ['ri-book-open-line','Portfólio educacional','Apresente as modalidades e formações disponibilizadas pela instituição.'],
          ['ri-links-line','Link exclusivo de campanha','Divulgue seus cursos e receba matrículas online diretamente pelo link da sua unidade.'],
          ['ri-dashboard-3-line','Gestão e repasse no AVASET','Acompanhe matrículas, créditos automáticos e solicite transferências pelo painel.'],
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

  <?php if ($depoimentosUnidade): ?>
  <section class="par-section unit-stories">
    <div class="container">
      <div class="par-head"><small><?= $ehAfiliado ? 'Uma rede construída por pessoas' : 'Histórias de quem já começou' ?></small><h2><?= $ehAfiliado ? 'Conheça quem já cresce com a nossa rede' : 'Gestores que transformaram suas operações' ?></h2><p><?= $ehAfiliado ? 'Experiências de gestores de unidades parceiras que ajudam a mostrar a estrutura, a confiança e o potencial da rede ' . e($marca) . '.' : 'Experiências de parceiros da ' . e($marca) . ' que encontraram novas possibilidades para crescer por meio da educação.' ?></p></div>
      <div class="unit-stories__grid">
        <?php foreach ($depoimentosUnidade as $dep): ?>
        <article class="unit-story">
          <i class="ri-double-quotes-r unit-story__quote"></i>
          <div class="unit-story__stars" aria-label="Depoimento de parceiro"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i></div>
          <blockquote>“<?= e($dep['relato']) ?>”</blockquote>
          <div class="unit-story__person"><div class="unit-story__avatar"><?= e(mb_strtoupper(mb_substr($dep['nome'], 0, 1))) ?></div><div><strong><?= e($dep['nome']) ?></strong><?php if ($dep['empresa'] !== ''): ?><span><?= e($dep['empresa']) ?></span><?php endif; ?></div></div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($ehAfiliado): ?>
  <section class="par-section"><div class="container"><div class="par-head"><small>Dúvidas frequentes</small><h2>Entenda o programa de afiliados</h2></div><div class="flex-faq">
    <details><summary>Preciso pagar para enviar minha candidatura?</summary><p>O envio da candidatura não cria cobrança nem garante aprovação. As condições da parceria são apresentadas pela equipe antes da formalização.</p></details>
    <details><summary>Como minhas indicações são identificadas?</summary><p>Depois da aprovação, você recebe um acesso e um link próprio. As matrículas realizadas a partir dessa identificação ficam relacionadas à sua conta conforme as regras do programa.</p></details>
    <details><summary>Qual é a comissão do afiliado?</summary><p>O afiliado recebe 15% de comissão nas matrículas elegíveis vinculadas à sua conta, conforme os critérios formalizados no contrato da parceria.</p></details>
    <details><summary>Quando a comissão entra na minha conta?</summary><p>Quando um pagamento elegível da matrícula é confirmado, o sistema calcula o percentual e credita a comissão automaticamente na sua conta virtual.</p></details>
    <details><summary>Posso transferir o dinheiro a qualquer hora?</summary><p>Sim. Com saldo disponível, chave PIX cadastrada e validações de segurança concluídas, você pode solicitar a transferência pelo painel a qualquer hora do dia ou da noite.</p></details>
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
      <details><summary>Como recebo matrículas pela internet?</summary><p>A Unidade Flex recebe um link exclusivo de campanha. O gestor pode divulgá-lo em redes sociais, anúncios, WhatsApp e outros canais; as matrículas online feitas por esse caminho ficam vinculadas à unidade.</p></details>
      <details><summary>Como funcionam os repasses?</summary><p>O repasse pode chegar a 50%, conforme a modalidade e o contrato. Após a confirmação de um pagamento elegível, o sistema calcula e credita automaticamente o valor na conta virtual da unidade.</p></details>
      <details><summary>Quando posso transferir o saldo?</summary><p>Com saldo disponível e as validações concluídas, o gestor pode solicitar pelo painel uma transferência instantânea via PIX a qualquer hora do dia ou da noite.</p></details>
      <details><summary>O envio da candidatura garante a aprovação?</summary><p>Não. A candidatura inicia o processo de análise. A abertura depende da aprovação, do alinhamento das condições e da formalização contratual.</p></details>
    </div></div>
  </section>
  <?php endif; ?>

  <section class="final-cta"><div class="container"><div class="final-cta__box"><div><h2><?= $ehAfiliado ? 'Uma indicação pode mudar uma trajetória.' : 'Sua cidade pode estar pronta para uma Unidade Flex.' ?></h2><p><?= $ehAfiliado ? 'Dê o primeiro passo para construir uma parceria organizada, transparente e conectada a novas oportunidades.' : 'Apresente seu perfil e descubra se existe disponibilidade para desenvolver essa oportunidade na sua região.' ?></p></div><a href="#candidatura" class="btn"><?= $ehAfiliado ? 'Quero fazer parte' : 'Quero avaliar minha cidade' ?> <i class="ri-arrow-right-line"></i></a></div></div></section>

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
      </form>
    </div>
  </section>
</main>

<footer class="footer"><div class="container">
  <div class="footer__grid">
    <div class="footer__brand"><img src="<?= e($logoNegativa) ?>" alt="<?= e($marca) ?>"><p>Educação, tecnologia e atendimento próximo para criar novas oportunidades de aprendizagem.</p><div class="footer__social"><?php if (config('instagram')): ?><a href="<?= e(config('instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="ri-instagram-line"></i></a><?php endif; ?><?php if (config('facebook')): ?><a href="<?= e(config('facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="ri-facebook-fill"></i></a><?php endif; ?><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="ri-whatsapp-line"></i></a><?php if (config('youtube')): ?><a href="<?= e(config('youtube')) ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="ri-youtube-fill"></i></a><?php endif; ?></div></div>
    <div><h5>Modalidades</h5><ul><li><a href="index.php#cursos">Supletivo EJA</a></li><li><a href="index.php#cursos">Cursos técnicos</a></li><li><a href="index.php#cursos">Cursos livres</a></li></ul></div>
    <div><h5>Institucional</h5><ul><li><a href="index.php#categorias">Sobre nós</a></li><li><a href="unidades.php">Unidades</a></li><li><a href="afiliados.php">Programa de afiliados</a></li><li><a href="seja-uma-unidade.php">Abra sua Unidade Flex</a></li><li><a href="index.php#diferenciais">Diferenciais</a></li></ul></div>
    <div><h5>Atendimento</h5><ul><li><a href="index.php#contato">Central do aluno</a></li><li><a href="index.php#contato">Fale conosco</a></li><li><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a></li></ul></div>
  </div>
  <div class="footer__bottom"><span>© <?= $ano ?> <?= e($marca) ?> · Todos os direitos reservados.</span><span><a href="index.php">Voltar ao site</a></span></div>
</div></footer>

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
