<?php
// api/contato.php — recebe o formulário de contato/matrícula
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
  exit;
}

// Lê o corpo (JSON ou formulário tradicional)
$raw = file_get_contents('php://input');
$dados = json_decode($raw, true);
if (!is_array($dados)) { $dados = $_POST; }

$nome      = trim($dados['nome']      ?? '');
$email     = trim($dados['email']     ?? '');
$telefone  = trim($dados['telefone']  ?? '');
$interesse = trim($dados['interesse'] ?? '');
$mensagem  = trim($dados['mensagem']  ?? '');

// Validação
$erros = [];
if (mb_strlen($nome) < 3)                           $erros[] = 'Informe seu nome completo.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $erros[] = 'Informe um e-mail válido.';
if (mb_strlen(preg_replace('/\D/', '', $telefone)) < 10) $erros[] = 'Informe um telefone válido.';
if ($interesse === '')                              $erros[] = 'Selecione uma modalidade.';

if ($erros) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'mensagem' => implode(' ', $erros)], JSON_UNESCAPED_UNICODE);
  exit;
}

// Registro do lead (arquivo em /data — persistente via volume no EasyPanel)
$linha = [
  'data'      => date('Y-m-d H:i:s'),
  'nome'      => $nome,
  'email'     => $email,
  'telefone'  => $telefone,
  'interesse' => $interesse,
  'mensagem'  => $mensagem,
  'ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
];

$dir = __DIR__ . '/../../data';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
$arquivo = $dir . '/leads.csv';

$novo = !file_exists($arquivo);
if ($fp = @fopen($arquivo, 'a')) {
  if ($novo) { fputcsv($fp, array_keys($linha)); }
  fputcsv($fp, $linha);
  fclose($fp);
}

// (Opcional) Enviar e-mail — descomente e configure o destino em produção
// @mail('contato@eduset.com.br', 'Novo lead: '.$interesse,
//   "Nome: $nome\nE-mail: $email\nTelefone: $telefone\nMensagem: $mensagem",
//   "From: no-reply@eduset.com.br\r\n");

echo json_encode([
  'ok' => true,
  'mensagem' => "Obrigado, $nome! Recebemos seu contato e retornaremos em breve. 🎓"
], JSON_UNESCAPED_UNICODE);
