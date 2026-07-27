<?php
// unidades.php — onde a EDUSET atende.
// A lista sai da tabela_unidades do Directus e traz toda unidade ativa, sem
// distinguir polo físico de unidade a distância: para quem procura a escola na
// sua cidade, as duas são a mesma coisa.

require __DIR__ . '/api/_catalogo.php';

$unidades = unidadesListadas();
$porUf    = unidadesPorEstado();
$estados  = estadosAtendidos();
$ano      = date('Y');

$whatsapp = 'https://wa.me/' . config('whatsapp', '5500000000000') . '?text='
  . rawurlencode('Olá! Quero saber qual é a unidade EDUSET mais perto de mim.');

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

// Quantas cidades diferentes são atendidas — é o número que o visitante procura.
$cidades = contarCidades($unidades);

$ufsNaLista = array_values(array_filter(array_keys($porUf), fn($uf) => $uf !== '--'));
sort($ufsNaLista);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e(config('unidades_seo_descricao', 'Veja em que cidades a EDUSET tem unidade e matricule-se pela unidade da sua região.')) ?>">
  <meta name="theme-color" content="#044928">
  <title><?= e(config('unidades_seo_titulo', 'Unidades · EDUSET')) ?></title>

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
        <a href="index.php#cursos" class="btn btn-primary">Ver cursos <i class="ri-arrow-right-line"></i></a>
      </div>
    </div>
  </header>

  <!-- ===================== HERO ===================== -->
  <section class="unid-hero">
    <div class="container">
      <div class="unid-hero__inner">
        <nav class="crumbs">
          <a href="index.php">Início</a> <i class="ri-arrow-right-s-line"></i>
          <span>Unidades</span>
        </nav>

        <h1><?= e(config('unidades_titulo', 'A EDUSET perto de você')) ?></h1>
        <p class="lead">
          <?= e(config('unidades_subtitulo', 'O curso é 100% online, mas quem faz a matrícula, tira dúvida e acompanha o aluno é gente de carne e osso. Veja abaixo em que cidades a EDUSET já tem unidade.')) ?>
        </p>

        <?php if ($unidades): ?>
        <ul class="unid-hero__marcas">
          <li><i class="ri-community-line"></i> <?= count($unidades) ?> unidade<?= count($unidades) > 1 ? 's' : '' ?></li>
          <?php if ($cidades): ?><li><i class="ri-building-line"></i> <?= $cidades ?> cidade<?= $cidades > 1 ? 's' : '' ?></li><?php endif; ?>
          <?php if ($ufsNaLista): ?><li><i class="ri-flag-line"></i> <?= count($ufsNaLista) ?> estado<?= count($ufsNaLista) > 1 ? 's' : '' ?></li><?php endif; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php if ($unidades): ?>
  <!-- ===================== BUSCA + LISTA ===================== -->
  <section class="section" id="lista">
    <div class="container">

      <div class="unid-busca">
        <div class="unid-busca__campo">
          <i class="ri-search-line"></i>
          <input type="search" id="unid-filtro" placeholder="Busque pela cidade ou pelo estado" autocomplete="off">
        </div>
        <div class="uf-chips" id="unid-ufs">
          <button type="button" class="ativo" data-uf="todos">Todos</button>
          <?php foreach ($ufsNaLista as $uf): ?>
            <button type="button" data-uf="<?= e($uf) ?>" title="<?= e(nomeEstado($uf)) ?>"><?= e($uf) ?></button>
          <?php endforeach; ?>
        </div>
      </div>

      <p class="unid-vazio" id="unid-vazio" hidden>
        Nenhuma unidade encontrada com esse termo. Fale com a gente no WhatsApp que indicamos a mais próxima.
      </p>

      <?php foreach ($porUf as $uf => $lista): ?>
      <div class="unid-grupo" data-uf="<?= e($uf) ?>">
        <h2 class="unid-grupo__titulo">
          <?= e($uf === '--' ? 'Outras unidades' : nomeEstado($uf)) ?>
          <span class="conta"><?= count($lista) ?> unidade<?= count($lista) > 1 ? 's' : '' ?></span>
        </h2>

        <div class="unid-grid">
          <?php foreach ($lista as $u): ?>
          <a class="unid-card" href="unidade.php?id=<?= e($u['codigo']) ?>"
             data-uf="<?= e($u['uf']) ?>"
             data-busca="<?= e(semAcento($u['nome'] . ' ' . $u['cidade'] . ' ' . $u['uf'] . ' ' . $u['estado'] . ' ' . $u['referencia'])) ?>">
            <span class="unid-card__uf"><?= e($u['uf'] !== '' ? $u['uf'] : '·') ?></span>
            <h3><?= e($u['cidade'] !== '' ? $u['cidade'] : $u['nome']) ?></h3>
            <?php if ($u['referencia'] !== ''): ?>
              <p class="unid-card__ref"><?= e($u['referencia']) ?></p>
            <?php endif; ?>
            <span class="unid-card__ver">Ver unidade <i class="ri-arrow-right-line"></i></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

    </div>
  </section>

  <?php else: ?>
  <!-- ===================== SEM POLO FÍSICO ===================== -->
  <section class="section">
    <div class="container container--estreito">
      <div class="section-head">
        <span class="eyebrow">Atendimento</span>
        <h2>Atendimento <span class="gradient-text">100% a distância</span></h2>
        <p>A EDUSET atende hoje inteiramente pela internet: matrícula, tutoria e secretaria acontecem online, sem você precisar sair de casa.</p>
      </div>

      <?php if ($estados): ?>
      <div class="unid-ufs-lista">
        <p>Alunos matriculados nos estados:</p>
        <div class="uf-chips uf-chips--estatico">
          <?php foreach ($estados as $uf): ?>
            <span title="<?= e(nomeEstado($uf)) ?>"><?= e($uf) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="unid-cta">
        <a href="index.php#cursos" class="btn btn-primary">Ver os cursos <i class="ri-arrow-right-line"></i></a>
        <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn btn-outline"><i class="ri-whatsapp-line"></i> Falar com um consultor</a>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ===================== CHAMADA FINAL ===================== -->
  <section class="section section--soft">
    <div class="container">
      <div class="cta-band">
        <h2>Não achou a sua cidade?</h2>
        <p>Todos os cursos são 100% online: você estuda de qualquer lugar do Brasil e a matrícula pode ser feita agora, aqui mesmo pelo site.</p>
        <a href="index.php#cursos" class="btn btn-light">Ver cursos e matricular <i class="ri-arrow-right-line"></i></a>
      </div>
    </div>
  </section>

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
            <li><a href="index.php#contato">Central do aluno</a></li>
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
