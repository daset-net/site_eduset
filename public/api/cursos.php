<?php
// api/cursos.php — catálogo em JSON para a vitrine da home.
// Os dados vêm do Directus; a lógica fica em _catalogo.php.

require __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

[$cursos, $origem] = catalogo();

echo json_encode(
  ['ok' => $cursos !== [], 'origem' => $origem, 'total' => count($cursos), 'cursos' => $cursos],
  JSON_UNESCAPED_UNICODE
);
