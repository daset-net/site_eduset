<?php
// Metadados variáveis para o verso do certificado profissionalizante.
require __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=300');

$chave = preg_replace('/[^A-Za-z0-9-]/', '', (string) ($_GET['id'] ?? ''));
$curso = $chave !== '' ? (cursoPorId(strtoupper($chave)) ?? cursoPorId(strtolower($chave))) : null;
$metadados = $curso ? metadadosCertificadoProfissionalizante($curso) : null;

if (!$metadados) {
  http_response_code(404);
  echo json_encode(['ok' => false, 'mensagem' => 'Curso profissionalizante não encontrado.'], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(['ok' => true, 'certificado' => $metadados], JSON_UNESCAPED_UNICODE);
