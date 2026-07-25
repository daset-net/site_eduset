<?php
// api/imagem.php — serve as imagens de capa guardadas no Directus.
//
// O navegador não pode acessar /assets do Directus diretamente sem o token,
// e colocar o token na URL o exporia. Este proxy busca a imagem pelo servidor
// e devolve só os bytes, com cache no navegador.
//
// Uso: api/imagem.php?id=<uuid>&w=800

require __DIR__ . '/_catalogo.php';

$id = $_GET['id'] ?? '';
if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
  http_response_code(400);
  exit('id invalido');
}

// Largura opcional, limitada a valores razoáveis (evita virar redimensionador aberto).
$larguras = [400, 600, 800, 1200, 1600];
$w = isset($_GET['w']) ? (int) $_GET['w'] : 800;
if (!in_array($w, $larguras, true)) $w = 800;

$base  = directusBase();
$token = conexao('DIRECTUS_TOKEN');
if ($base === '' || $token === '') {
  http_response_code(503);
  exit('configuracao ausente');
}

$url = $base . '/assets/' . $id . '?' . http_build_query(['width' => $w, 'quality' => 80, 'fit' => 'cover']);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 15,
  CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
]);
$corpo  = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tipo   = curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'image/jpeg';
curl_close($ch);

if ($corpo === false || $status !== 200) {
  http_response_code(404);
  exit('imagem nao encontrada');
}

header('Content-Type: ' . $tipo);
header('Content-Length: ' . strlen($corpo));
header('Cache-Control: public, max-age=86400');   // 1 dia no navegador
echo $corpo;
