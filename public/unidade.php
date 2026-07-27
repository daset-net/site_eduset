<?php
// unidade.php — ficha de uma unidade da EDUSET, polo físico ou a distância.
// Uso: /unidade.php?id=centro.aracaju.se  (o código é o e-mail da unidade sem o
// domínio — o mesmo do link de divulgação ?polo=).
//
// A página diz onde a unidade fica (cidade e estado) e leva à matrícula por ela.
// Endereço e telefone da unidade não são publicados: o contato do site é sempre
// pelo canal da escola.

require __DIR__ . '/api/_catalogo.php';

$codigo  = strtolower(preg_replace('/[^A-Za-z0-9._-]/', '', $_GET['id'] ?? ''));
$unidade = $codigo !== '' ? unidadePorCodigo($codigo) : null;

if (!$unidade) {
  header('Location: unidades.php', true, 302);
  exit;
}

$ano = date('Y');

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$ead    = $unidade['ead'];
$local  = trim($unidade['cidade'] . ($unidade['uf'] !== '' ? ' · ' . $unidade['uf'] : ''), ' ·');
$titulo = $unidade['cidade'] !== '' ? $unidade['cidade'] : $unidade['nome'];
if ($unidade['referencia'] !== '') $titulo .= ' — ' . $unidade['referencia'];

$whatsapp = 'https://wa.me/' . config('whatsapp', '5500000000000') . '?text='
  . rawurlencode('Olá! Quero falar sobre a unidade ' . $unidade['nome'] . '.');

// Matricular por esta unidade: o link com ?polo= é o mesmo mecanismo da
// divulgação — grava o cookie da visita e a venda fica com o polo.
$linkMatricula = 'index.php?polo=' . rawurlencode($unidade['codigo']) . '#cursos';

// Outras unidades do mesmo estado, para quem abriu a cidade errada.
$vizinhas = array_slice(array_values(array_filter(
  unidadesListadas(),
  fn($u) => $u['uf'] === $unidade['uf'] && $u['codigo'] !== $unidade['codigo']
)), 0, 6);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e(($ead ? 'Atendimento EDUSET a distância em ' : 'Unidade EDUSET em ') . ($unidade['cidade'] !== '' ? $unidade['cidade'] : $unidade['nome']) . ($unidade['uf'] !== '' ? ' (' . $unidade['uf'] . ')' : '') . '. Cursos 100% online com matrícula por esta unidade.') ?>">
  <meta name="theme-color" content="#044928">
  <title><?= e($titulo . ' · Unidades EDUSET') ?></title>

  <meta property="og:title" content="<?= e('Unidade EDUSET · ' . $titulo) ?>">
  <meta property="og:description" content="<?= e('Cursos 100% online com matrícula pela unidade de ' . ($unidade['cidade'] !== '' ? $unidade['cidade'] : $unidade['nome']) . '.') ?>">
  <meta property="og:type" content="website">

  <link rel="icon" href="assets/img/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="apple-touch-icon" href="assets/img/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <link rel="stylesheet" href="<?= versao('assets/css/style.css') ?>">
</head>
<body class="page-unidades">

  <!-- ===================== HEADER ===================== -->
  <header class="header" id="header">
    <div class="container header__inner">
      <a href="index.php" class="brand">
        <img class="brand__neg" src="assets/img/eduset-negativo.png" alt="EDUSET">
        <img class="brand__cor" src="assets/img/eduset.png" alt="EDUSET">
      </a>
      <nav class="nav">
        <a href="index.php">Início</a>
        <a href="index.php#cursos">Cursos</a>
        <a href="unidades.php" class="ativo">Unidades</a>
        <a href="index.php#diferenciais">Diferenciais</a>
        <a href="index.php#contato">Contato</a>
      </nav>
      <div class="header__cta">
        <a href="<?= e($linkMatricula) ?>" class="btn btn-primary">Matricular nesta unidade <i class="ri-arrow-right-line"></i></a>
      </div>
    </div>
  </header>

  <!-- ===================== HERO ===================== -->
  <section class="unid-hero">
    <div class="container">
      <div class="unid-hero__inner">
        <nav class="crumbs">
          <a href="index.php">Início</a> <i class="ri-arrow-right-s-line"></i>
          <a href="unidades.php">Unidades</a> <i class="ri-arrow-right-s-line"></i>
          <span><?= e($unidade['cidade'] !== '' ? $unidade['cidade'] : $unidade['nome']) ?></span>
        </nav>

        <span class="curso-hero__badge"><span class="dot"></span> <?= $ead ? 'Atendimento a distância' : 'Polo credenciado' ?> · Matrículas abertas <?= $ano ?></span>
        <h1><?= e($titulo) ?></h1>
        <p class="unid-hero__nome"><?= e($unidade['nome']) ?></p>
        <p class="lead">
          Esta é a unidade que atende <?= e($unidade['cidade'] !== '' ? $unidade['cidade'] : 'a sua região') ?><?= $unidade['uf'] !== '' ? ' e região, ' . e(estadoComPreposicao($unidade['uf'])) : '' ?>.
          <?php if ($ead): ?>
            O atendimento é a distância, pelos canais da escola, e a matrícula feita por aqui fica registrada nesta unidade.
          <?php else: ?>
            Você estuda 100% online, no seu ritmo, e a matrícula feita por aqui fica registrada nesta unidade.
          <?php endif; ?>
        </p>

        <ul class="unid-hero__marcas">
          <?php if ($local !== ''): ?><li><i class="ri-map-pin-2-line"></i> <?= e($local) ?></li><?php endif; ?>
          <li><i class="<?= $ead ? 'ri-computer-line' : 'ri-store-2-line' ?>"></i> <?= $ead ? 'Atendimento a distância' : 'Polo na cidade' ?></li>
          <li><i class="ri-time-line"></i> <?= e(config('horario_atendimento', 'Segunda a sexta, das 8h às 18h')) ?></li>
        </ul>

        <div class="unid-hero__actions">
          <a href="<?= e($linkMatricula) ?>" class="btn btn-light">Ver cursos e matricular <i class="ri-arrow-right-line"></i></a>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== FICHA ===================== -->
  <section class="section">
    <div class="container unid-ficha">
      <div>
        <div class="ficha-box">
          <h2><i class="ri-community-line"></i> Sobre esta unidade</h2>

          <div class="ficha-linha">
            <div class="ic"><i class="ri-map-pin-line"></i></div>
            <div><strong>Cidade</strong><span><?= e($unidade['cidade'] !== '' ? $unidade['cidade'] : '—') ?></span></div>
          </div>
          <div class="ficha-linha">
            <div class="ic"><i class="ri-flag-line"></i></div>
            <div><strong>Estado</strong><span><?= e($unidade['estado'] !== '' ? $unidade['estado'] . ' · ' . $unidade['uf'] : '—') ?></span></div>
          </div>
          <?php if ($unidade['referencia'] !== ''): ?>
          <div class="ficha-linha">
            <div class="ic"><i class="ri-building-line"></i></div>
            <div><strong>Referência</strong><span><?= e($unidade['referencia']) ?></span></div>
          </div>
          <?php endif; ?>
          <div class="ficha-linha">
            <div class="ic"><i class="<?= $ead ? 'ri-computer-line' : 'ri-store-2-line' ?>"></i></div>
            <div><strong>Tipo de unidade</strong><span><?= $ead
              ? 'Unidade a distância — atende a cidade pelos canais da escola'
              : 'Polo com atendimento na própria cidade' ?></span></div>
          </div>
          <div class="ficha-linha">
            <div class="ic"><i class="ri-graduation-cap-line"></i></div>
            <div><strong>Como funciona</strong><span>Aulas, material e provas 100% online, com tutoria durante todo o curso</span></div>
          </div>

          <p class="ficha-nota">
            Todo o curso acontece pela plataforma: você não precisa se deslocar para estudar.
            Para tratar de matrícula, documentos ou dúvidas, fale com a escola pelos canais oficiais ao lado.
          </p>
        </div>
      </div>

      <aside>
        <div class="ficha-box ficha-box--matricula">
          <h2><i class="ri-graduation-cap-line"></i> Matrícula por esta unidade</h2>
          <p>Ao continuar por aqui, sua matrícula fica registrada nesta unidade — é ela que acompanha o seu curso do começo ao fim.</p>
          <ul class="lista-check">
            <li><i class="ri-check-line"></i> Todos os cursos do catálogo, 100% online</li>
            <li><i class="ri-check-line"></i> Sem taxa de matrícula</li>
            <li><i class="ri-check-line"></i> Acesso liberado após a confirmação do pagamento</li>
          </ul>
          <a href="<?= e($linkMatricula) ?>" class="btn btn-primary" style="width:100%;justify-content:center">
            Ver cursos desta unidade <i class="ri-arrow-right-line"></i>
          </a>
        </div>

        <div class="ficha-box ficha-box--contato">
          <h2><i class="ri-customer-service-2-line"></i> Fale com a escola</h2>

          <div class="ficha-linha">
            <div class="ic"><i class="ri-whatsapp-line"></i></div>
            <div><strong>WhatsApp</strong><span><?= e(config('telefone_exibicao', '(00) 00000-0000')) ?></span></div>
          </div>
          <div class="ficha-linha">
            <div class="ic"><i class="ri-mail-line"></i></div>
            <div><strong>E-mail</strong><span><?= e(config('email_contato', 'contato@eduset.com.br')) ?></span></div>
          </div>
          <div class="ficha-linha">
            <div class="ic"><i class="ri-time-line"></i></div>
            <div><strong>Atendimento</strong><span><?= e(config('horario_atendimento', 'Segunda a sexta, das 8h às 18h')) ?></span></div>
          </div>

          <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px">
            <i class="ri-whatsapp-line"></i> Chamar no WhatsApp
          </a>
        </div>
      </aside>
    </div>
  </section>

  <!-- ===================== VIZINHAS ===================== -->
  <?php if ($vizinhas): ?>
  <section class="section section--soft">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Por perto</span>
        <h2>Outras unidades <span class="gradient-text"><?= e(estadoComPreposicao($unidade['uf'])) ?></span></h2>
      </div>
      <div class="unid-grid">
        <?php foreach ($vizinhas as $v): ?>
        <a class="unid-card<?= $v['ead'] ? ' unid-card--ead' : '' ?>" href="unidade.php?id=<?= e($v['codigo']) ?>">
          <span class="unid-card__uf"><?= e($v['uf'] !== '' ? $v['uf'] : '·') ?></span>
          <h3><?= e($v['cidade'] !== '' ? $v['cidade'] : $v['nome']) ?></h3>
          <?php if ($v['referencia'] !== ''): ?><p class="unid-card__ref"><?= e($v['referencia']) ?></p><?php endif; ?>
          <span class="unid-card__tipo">
            <?php if ($v['ead']): ?>
              <i class="ri-computer-line"></i> Atendimento a distância
            <?php else: ?>
              <i class="ri-map-pin-2-line"></i> Polo na cidade
            <?php endif; ?>
          </span>
          <span class="unid-card__ver">Ver unidade <i class="ri-arrow-right-line"></i></span>
        </a>
        <?php endforeach; ?>
      </div>
      <p class="unid-todas"><a href="unidades.php">Ver todas as unidades <i class="ri-arrow-right-line"></i></a></p>
    </div>
  </section>
  <?php endif; ?>

  <!-- ===================== FOOTER ===================== -->
  <footer class="footer">
    <div class="container">
      <div class="footer__grid">
        <div class="footer__brand">
          <img src="assets/img/eduset-negativo.png" alt="EDUSET">
          <p>Educação que transforma vidas. Supletivo EJA, cursos técnicos e cursos livres com certificação reconhecida e 100% online.</p>
          <div class="footer__social">
            <?php if (config('instagram')): ?><a href="<?= e(config('instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="ri-instagram-line"></i></a><?php endif; ?>
            <?php if (config('facebook')): ?><a href="<?= e(config('facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="ri-facebook-fill"></i></a><?php endif; ?>
            <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="ri-whatsapp-line"></i></a>
            <?php if (config('youtube')): ?><a href="<?= e(config('youtube')) ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="ri-youtube-fill"></i></a><?php endif; ?>
          </div>
        </div>
        <div>
          <h5>Modalidades</h5>
          <ul>
            <li><a href="index.php#cursos">Supletivo EJA</a></li>
            <li><a href="index.php#cursos">Curso Técnico</a></li>
            <li><a href="index.php#cursos">Curso Livre</a></li>
          </ul>
        </div>
        <div>
          <h5>Institucional</h5>
          <ul>
            <li><a href="index.php#categorias">Sobre nós</a></li>
            <li><a href="unidades.php">Unidades</a></li>
            <li><a href="index.php#contato">Contato</a></li>
          </ul>
        </div>
        <div>
          <h5>Atendimento</h5>
          <ul>
            <li><a href="<?= e($linkMatricula) ?>">Matrícula</a></li>
            <li><a href="index.php#contato">Fale conosco</a></li>
            <li><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a></li>
          </ul>
        </div>
      </div>
      <div class="footer__bottom">
        <span>© <?= $ano ?> EDUSET · Todos os direitos reservados.</span>
        <span>Feito com <i class="ri-heart-fill" style="color:#ff5a5a"></i> para transformar vidas.</span>
      </div>
    </div>
  </footer>

  <a href="<?= e($whatsapp) ?>" class="whatsapp-float" target="_blank" rel="noopener" aria-label="WhatsApp">
    <i class="ri-whatsapp-line"></i>
  </a>

<script src="<?= versao('assets/js/unidades.js') ?>"></script>
<script src="<?= versao('assets/js/avisos.js') ?>"></script>
</body>
</html>
