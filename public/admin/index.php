<?php
// admin/index.php — entrada do painel do site.
// Mesmo usuário e senha do ead.eduset.com.br (tabela_gestores).

require __DIR__ . '/_auth.php';

if (logado()) {
  header('Location: painel.php');
  exit;
}

$erro = '';
if (($_GET['e'] ?? '') === 'sessao') $erro = 'Sua sessão expirou. Entre novamente.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrfValido($_POST['csrf'] ?? null)) {
    $erro = 'Sessão inválida. Recarregue a página e tente de novo.';
  } elseif (bloqueadoPorTentativas()) {
    $erro = 'Muitas tentativas. Aguarde alguns minutos antes de tentar novamente.';
  } else {
    $r = autenticar($_POST['login'] ?? '', $_POST['senha'] ?? '');
    registrarTentativa($r['ok']);
    if ($r['ok']) {
      abrirSessao($r['gestor']);
      header('Location: painel.php');
      exit;
    }
    $erro = $r['msg'];
  }
}

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Painel do site · EDUSET</title>
  <link rel="icon" href="../assets/img/favicon.ico" sizes="any">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <link rel="stylesheet" href="<?= versao('assets/css/style.css', '../') ?>">
  <link rel="stylesheet" href="<?= versao('admin/admin.css', '../') ?>">
</head>
<body class="admin-login">
  <form class="login-card" method="post" autocomplete="on">
    <img src="../assets/img/eduset.png" alt="EDUSET" class="login-logo">
    <h1>Painel do site</h1>
    <p class="login-ajuda">Use o mesmo usuário e senha do <strong>ead.eduset.com.br</strong>.</p>

    <?php if ($erro): ?>
      <div class="aviso aviso--erro"><i class="ri-error-warning-line"></i> <?= e($erro) ?></div>
    <?php endif; ?>

    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

    <label>Usuário
      <input type="text" name="login" required autofocus autocapitalize="none" spellcheck="false"
             placeholder="seu usuário ou e-mail">
    </label>

    <label>Senha
      <input type="password" name="senha" required placeholder="sua senha">
    </label>

    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
      Entrar <i class="ri-arrow-right-line"></i>
    </button>

    <a class="login-voltar" href="../index.php"><i class="ri-arrow-left-line"></i> Voltar ao site</a>
  </form>
</body>
</html>
