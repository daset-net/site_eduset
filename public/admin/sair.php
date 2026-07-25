<?php
// admin/sair.php — encerra a sessão do painel.
require __DIR__ . '/_auth.php';
encerrarSessao();
header('Location: index.php');
exit;
