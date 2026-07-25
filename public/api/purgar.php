<?php
// api/purgar.php — invalida o cache do catálogo/configurações sob demanda.
//
// Serve para o AVASET (GESET → Catálogo de cursos) avisar o site assim que um
// curso é ligado/desligado, em vez de esperar o TTL de 10 minutos.
//
// Configuração (EasyPanel → Environment):
//   TOKEN_PURGA_SITE = <segredo compartilhado com o AVASET>
//
// Uso:  POST /api/purgar.php   com header  X-Token: <segredo>
//       (também aceita ?token=<segredo>)

require __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $status, array $corpo): void {
  http_response_code($status);
  echo json_encode($corpo, JSON_UNESCAPED_UNICODE);
  exit;
}

$esperado = conexao('TOKEN_PURGA_SITE');
if ($esperado === '') {
  responder(503, ['ok' => false, 'erro' => 'Purga não configurada neste ambiente']);
}

$recebido = $_SERVER['HTTP_X_TOKEN'] ?? ($_GET['token'] ?? '');
if (!is_string($recebido) || !hash_equals($esperado, $recebido)) {
  responder(403, ['ok' => false, 'erro' => 'Token inválido']);
}

limparCache();
responder(200, ['ok' => true, 'purgado' => true]);
