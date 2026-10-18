<?php
// Metadados da página CT014. Os demais cursos mantêm o comportamento existente.
function seoGuiaTurismo(array $curso, string $descricao): string {
  $url = urlAbsoluta('/curso.php?id=' . $curso['slug']);
  $cursoId = $url . '#curso';
  $dados = [
    '@context' => 'https://schema.org',
    '@graph' => [
      [
        '@type' => 'Course', '@id' => $cursoId,
        'name' => $curso['nome'], 'courseCode' => 'CT014',
        'description' => $descricao, 'url' => $url,
        'inLanguage' => 'pt-BR', 'timeRequired' => 'PT1000H',
        'provider' => ['@type' => 'Organization', 'name' => SITE_NOME, 'url' => urlAbsoluta('/')],
        'hasCourseInstance' => ['@type' => 'CourseInstance', 'courseMode' => 'online', 'courseWorkload' => 'PT1000H'],
      ],
      [
        '@type' => 'WebPage', '@id' => $url . '#pagina', 'url' => $url,
        'name' => $curso['seoTitulo'], 'description' => $descricao,
        'inLanguage' => 'pt-BR', 'mainEntity' => ['@id' => $cursoId],
      ],
      [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => urlAbsoluta('/')],
          ['@type' => 'ListItem', 'position' => 2, 'name' => $curso['nome'], 'item' => $url],
        ],
      ],
    ],
  ];
  if ($curso['imagem'] !== '') $dados['@graph'][0]['image'] = urlAbsoluta($curso['imagem'] . '&w=1200');
  return '<script type="application/ld+json">' . json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>';
}

function gradeGuiaTurismo(array $materias): array {
  $horas = [
    "Fundamentos do Turismo e Hospitalidade" => 40,
    "Comunicação e Oral" => 40,
    "Geografia Turística do Brasil e Cartografia" => 50,
    "História do Brasil e Patrimônio Cultural" => 50,
    "Legislação Turística e Ética Profissional do Guia" => 40,
    "Direito do Consumidor e Relações de Consumo" => 20,
    "Técnicas de Guiamento Regional" => 50,
    "Planejamento e Elaboração de Roteiros Turísticos" => 40,
    "Operação de Viagens e Guiamento de Excursão Nacional" => 50,
    "Transportes, Agenciamento e Serviços de Hospedagem" => 40,
    "Gestão Ambiental" => 40,
    "Turismo de Natureza e Interpretação Ambiental" => 40,
    "Acessibilidade, Inclusão e Atendimento ao Turista" => 40,
    "Primeiros Socorros" => 40,
    "Inglês Aplicado ao Guiamento Turístico" => 40,
    "Espanhol Aplicado ao Guiamento Turístico" => 40,
    "Empreendedorismo" => 40,
    "Marketing Turístico e Tecnologias Digitais para Guias" => 40,
    "Manifestações Culturais, Gastronomia e Turismo de Base Comunitária" => 40,
    "Segurança e Gestão de Riscos no Turismo" => 60,
    "Eventos, Recreação e Condução de Grupos" => 60,
    "Práticas de Guiamento e Projeto Integrador" => 100,
  ];
  foreach ($materias as &$materia) {
    if (isset($horas[$materia['nome']])) $materia['horas'] = $horas[$materia['nome']];
  }
  unset($materia);
  return $materias;
}
