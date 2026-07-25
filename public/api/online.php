<?php
// api/online.php — quantas pessoas estão com a página deste curso aberta agora.
//
// Número real: cada visitante manda um ping periódico e o servidor conta os
// pings distintos dos últimos minutos. Sem ping, sem contagem — se não houver
// ninguém no curso, a resposta é 1 (o próprio visitante) e a página esconde o
// aviso.
//
// O visitante é identificado por um hash de IP + navegador, que não guarda o IP
// em claro e não serve para rastrear ninguém entre páginas.

require __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const JANELA_ONLINE = 300; // segundos que um ping continua valendo

$curso = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_GET['curso'] ?? ''));
if ($curso === '') {
  echo json_encode(['ok' => false, 'online' => 0]);
  exit;
}

$dir = __DIR__ . '/../../data';
if (!is_dir($dir)) @mkdir($dir, 0775, true);
$arquivo = $dir . '/online.json';

// Identificador efêmero do visitante — não guarda IP nem User-Agent em claro.
$visitante = substr(hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 16);
$agora = time();

$online = 1;
$fp = @fopen($arquivo, 'c+');
if ($fp) {
  if (flock($fp, LOCK_EX)) {
    $conteudo = stream_get_contents($fp);
    $mapa = json_decode((string) $conteudo, true);
    if (!is_array($mapa)) $mapa = [];

    // Registra este visitante e descarta os pings vencidos de todos os cursos.
    $mapa[$curso][$visitante] = $agora;
    foreach ($mapa as $c => $visitantes) {
      $mapa[$c] = array_filter($visitantes, fn($t) => $agora - $t < JANELA_ONLINE);
      if (!$mapa[$c]) unset($mapa[$c]);
    }
    $online = count($mapa[$curso] ?? []);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($mapa));
    fflush($fp);
    flock($fp, LOCK_UN);
  }
  fclose($fp);
}

echo json_encode(['ok' => true, 'online' => max(1, $online)]);
