<?php
// admin/_topo.php — cabeçalho comum das telas do painel.
// Espera $titulo e $abaAtiva definidos por quem inclui.
if (!function_exists('e')) {
  function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}
$abaAtiva = $abaAtiva ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($titulo ?? 'Painel') ?> · EDUSET</title>
  <link rel="icon" href="../assets/img/favicon.ico" sizes="any">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <link rel="stylesheet" href="<?= versao('assets/css/style.css', '../') ?>">
  <link rel="stylesheet" href="<?= versao('admin/admin.css', '../') ?>">
</head>
<body class="admin">

<header class="admin-topo">
  <div class="admin-topo__inner">
    <a href="painel.php" class="admin-marca">
      <img src="../assets/img/eduset.png" alt="EDUSET">
      <span>Painel do site</span>
    </a>

    <nav class="admin-abas">
      <a href="painel.php" class="<?= $abaAtiva === 'painel' ? 'ativa' : '' ?>">
        <i class="ri-tune-line"></i> Configurações
      </a>
      <a href="cursos.php" class="<?= $abaAtiva === 'cursos' ? 'ativa' : '' ?>">
        <i class="ri-book-open-line"></i> Cursos e capas
      </a>
      <a href="campanhas.php" class="<?= $abaAtiva === 'campanhas' ? 'ativa' : '' ?>">
        <i class="ri-price-tag-3-line"></i> Campanhas
      </a>
    </nav>

    <div class="admin-usuario">
      <a href="../index.php" target="_blank" rel="noopener" title="Abrir o site"><i class="ri-external-link-line"></i></a>
      <span><?= e(gestorNome()) ?></span>
      <a href="sair.php" class="admin-sair" title="Sair"><i class="ri-logout-box-r-line"></i></a>
    </div>
  </div>
</header>

<main class="admin-conteudo">
  <h1 class="admin-titulo"><?= e($titulo ?? '') ?></h1>
