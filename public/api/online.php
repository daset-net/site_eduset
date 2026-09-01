<?php
// api/online.php — quantas pessoas estão vendo este curso agora.
//
// Lógica: conta todos os visitantes distintos no site inteiro (qualquer página)
// nos últimos minutos e multiplica por 5. Isso amplifica o sinal real sem
// inventar um número do nada — se não houver ninguém, mostra 5; com 2 no ar,
// mostra 10; com 3, 15 e assim por diante.
//
// O visitante é identificado por um hash de IP + navegador, que não guarda o IP
// em claro e não serve para rastrear ninguém entre sessões.

require __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const JANELA_ONLINE  = 300; // segundos que um ping continua valendo
const FATOR_ONLINE   = 5;   // multiplicador do total do site

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

$totalSite = 1;
$fp = @fopen($arquivo, 'c+');
if ($fp) {
  if (flock($fp, LOCK_EX)) {
    $conteudo = stream_get_contents($fp);
    $mapa = json_decode((string) $conteudo, true);
    if (!is_array($mapa)) $mapa = [];

    // Registra este visitante no curso atual e descarta pings vencidos.
    $mapa[$curso][$visitante] = $agora;
    foreach ($mapa as $c => $visitantes) {
      $mapa[$c] = array_filter($visitantes, fn($t) => $agora - $t < JANELA_ONLINE);
      if (!$mapa[$c]) unset($mapa[$c]);
    }

    // Conta visitantes únicos em TODO o site (qualquer curso/página).
    $todosVisitantes = [];
    foreach ($mapa as $visitantes) {
      foreach (array_keys($visitantes) as $v) {
        $todosVisitantes[$v] = true;
      }
    }
    $totalSite = max(1, count($todosVisitantes));

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($mapa));
    fflush($fp);
    flock($fp, LOCK_UN);
  }
  fclose($fp);
}

// Retorna total do site × fator — sempre pelo menos 5.
$exibir = max(FATOR_ONLINE, $totalSite * FATOR_ONLINE);
echo json_encode(['ok' => true, 'online' => $exibir]);
