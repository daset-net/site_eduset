<?php
/** Verifica a identificação de uma futura Unidade Flex sem criar cadastro. */
require_once __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
  exit;
}

function unidadeTexto(string $valor): string {
  $valor = trim(preg_replace('/\s+/u', ' ', $valor));
  $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);
  return $ascii !== false ? $ascii : $valor;
}

function unidadeSlugPublico(string $valor): string {
  $valor = strtolower(unidadeTexto($valor));
  return preg_replace('/[^a-z0-9]/', '', $valor);
}

$estado = strtoupper(substr(unidadeTexto((string) ($_GET['estado'] ?? '')), 0, 2));
$cidadeOriginal = trim((string) ($_GET['cidade'] ?? ''));
$bairroOriginal = trim((string) ($_GET['bairro'] ?? ''));
$cidade = unidadeTexto($cidadeOriginal);
$bairro = unidadeTexto($bairroOriginal);

if (!preg_match('/^[A-Z]{2}$/', $estado) || $cidade === '' || $bairro === '') {
  http_response_code(422);
  echo json_encode(['ok' => false, 'mensagem' => 'Informe bairro, cidade e estado.'], JSON_UNESCAPED_UNICODE);
  exit;
}

$local = implode('.', array_filter([
  unidadeSlugPublico($bairro),
  unidadeSlugPublico($cidade),
  unidadeSlugPublico($estado),
]));
$nome = implode(' - ', array_filter([$estado, $cidade, $bairro]));

// O domínio é o mesmo já usado pelas unidades da escola. Se a tabela estiver
// vazia, usa a configuração editorial e, por último, o padrão do AVASET.
$dominio = trim((string) config('unidade_dominio_email', ''));
if ($dominio === '') {
  $amostra = buscarColecao('tabela_unidades', [
    'fields' => 'unidade_email',
    'filter' => ['unidade_email' => ['_contains' => '@']],
    'limit'  => 1,
  ]) ?? [];
  $emailAmostra = (string) ($amostra[0]['unidade_email'] ?? '');
  if (strpos($emailAmostra, '@') !== false) $dominio = substr(strrchr($emailAmostra, '@'), 1);
}
if ($dominio === '') $dominio = 'avaset.net';
$email = $local . '@' . strtolower($dominio);

$duplicadas = buscarColecao('tabela_unidades', [
  'fields' => 'id,unidade_nome,unidade_email',
  'filter' => ['unidade_email' => ['_eq' => $email]],
  'limit'  => 1,
]);
if ($duplicadas === null) {
  http_response_code(503);
  echo json_encode(['ok' => false, 'mensagem' => 'Não foi possível consultar as unidades agora.'], JSON_UNESCAPED_UNICODE);
  exit;
}
$existe = !empty($duplicadas);

$mensagem = '';
if ($existe) {
  $mensagem = 'Já existe a unidade ' . $nome . '.';
  $mensagem .= ' Este polo já está cadastrado. Informe outro bairro ou confirme os dados com a equipe.';
}

echo json_encode([
  'ok'       => true,
  'existe'   => $existe,
  'nome'     => $nome,
  'email'    => $email,
  'mensagem' => $mensagem,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
