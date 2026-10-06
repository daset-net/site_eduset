<?php
// sitemap.php — servido como /sitemap.xml (ver .htaccess).
//
// Gerado na hora a partir do catálogo e das unidades, então curso novo ou
// unidade nova entra no mapa sem ninguém lembrar de atualizar um arquivo. As
// URLs saem sempre absolutas e no mesmo formato da tag canonical de cada página.
require __DIR__ . '/api/_catalogo.php';

function x(string $s): string { return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }

$hoje = date('Y-m-d');
$urls = [
  ['/', 'daily', '1.0'],
  ['/profissionalizantes', 'weekly', '0.8'],
  ['/unidades.php', 'weekly', '0.6'],
  ['/seja-uma-unidade.php', 'monthly', '0.4'],
  ['/afiliados.php', 'monthly', '0.4'],
];

[$cursos] = catalogo();
$vistos = [];
foreach ($cursos as $c) {
  $chave = $c['slug'] !== '' ? $c['slug'] : $c['id'];
  if ($chave === '' || isset($vistos[$chave])) continue;
  $vistos[$chave] = true;
  $urls[] = ['/curso.php?id=' . $chave, 'weekly', '0.9'];
}

foreach (unidadesListadas() as $u) {
  if (($u['codigo'] ?? '') === '') continue;
  $urls[] = ['/unidade.php?id=' . $u['codigo'], 'monthly', '0.5'];
}

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as [$caminho, $freq, $prio]) {
  echo '  <url><loc>' . x(urlAbsoluta($caminho)) . '</loc><lastmod>' . $hoje
     . '</lastmod><changefreq>' . $freq . '</changefreq><priority>' . $prio . "</priority></url>\n";
}
echo "</urlset>\n";
