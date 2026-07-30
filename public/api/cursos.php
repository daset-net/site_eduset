<?php
// api/cursos.php — catálogo em JSON para a vitrine da home.
// Os dados vêm do Directus; a lógica fica em _catalogo.php.
//
// Sem parâmetro, devolve o catálogo inteiro — é o que a home consome.
// Com ?q=, devolve só os cursos que casam com a busca, num formato enxuto:
// é o que o atendimento (ChatSET) consulta quando o cliente pergunta valor,
// duração ou condição de um curso. O preço é o mesmo que a página anuncia
// naquele momento (a oferta do ciclo, sem linha de bolsa), então robô e site
// nunca falam preços diferentes.
//
//   /api/cursos.php?q=tecnico informatica
//   /api/cursos.php?q=enfermagem&limite=3

require __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: public, max-age=300');

const BUSCA_LIMITE_PADRAO = 4;
const BUSCA_LIMITE_MAXIMO = 10;

/** Palavras da busca, sem acento e sem o ruído que todo curso tem no nome. */
function palavrasBusca(string $termo): array {
  $ruido = ['curso', 'cursos', 'tecnico', 'tecnica', 'tecnologia', 'profissional',
            'online', 'ead', 'em', 'de', 'do', 'da', 'das', 'dos', 'e', 'o', 'a',
            'quero', 'queria', 'ser', 'fazer', 'area', 'trabalhar', 'estudar', 'virar'];

  $limpo = preg_replace('/[^a-z0-9\s]/', ' ', semAcento($termo));
  $todas = array_values(array_filter(preg_split('/\s+/', trim($limpo)) ?: [], fn($p) => $p !== ''));

  // "curso técnico" sozinho não identifica nada, mas também não pode zerar a
  // busca: sem palavra específica, as genéricas voltam a valer.
  $especificas = array_values(array_diff($todas, $ruido));
  return $especificas !== [] ? $especificas : $todas;
}

/**
 * A palavra buscada aparece no texto?
 *
 * A comparação é palavra a palavra, nunca pedaço solto: procurar "ser" dentro
 * de "Conserto de Celulares" achava curso de eletricista em oficina de celular.
 * Palavra de 5 letras ou mais também vale pelo começo, para "eletrica" chegar em
 * "eletricista" e "estetic" em "estética".
 */
function palavraCasa(string $palavra, string $texto): bool {
  foreach (preg_split('/[^a-z0-9]+/', $texto) ?: [] as $alvo) {
    if ($alvo === '') continue;
    if ($alvo === $palavra) return true;
    if (strlen($palavra) >= 5 && strncmp($alvo, $palavra, strlen($palavra)) === 0) return true;
    if (strlen($alvo) >= 5 && strncmp($palavra, $alvo, strlen($alvo)) === 0) return true;
  }
  return false;
}

/**
 * Quanto o curso combina com as palavras buscadas. Nome vale mais que
 * categoria: quem pergunta "informática" quer o curso, não a modalidade.
 */
function pontuarCurso(array $curso, array $palavras): int {
  $nome = semAcento($curso['nome'] . ' ' . $curso['slug'] . ' ' . $curso['id']);
  $resto = semAcento($curso['categoriaLabel'] . ' ' . $curso['descricao']);

  $pontos = 0;
  foreach ($palavras as $p) {
    if (strlen($p) < 3) continue;
    if (palavraCasa($p, $nome)) $pontos += 10;
    elseif (palavraCasa($p, $resto)) $pontos += 2;
  }
  return $pontos;
}

/**
 * Domínio para montar o link. Vem do Host da requisição, mas só se for um
 * domínio de verdade: o link vai para o cliente no WhatsApp, e um Host forjado
 * (ou o 127.0.0.1 do teste local) mandaria a pessoa para o lugar errado.
 */
function dominioDoSite(): string {
  $host = preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
  return preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $host) ? $host : 'eduset.com.br';
}

/** O curso como o atendimento precisa dele: preço, prazo e o link para mandar. */
function cursoParaAtendimento(array $c): array {
  return [
    'curso'         => $c['nome'],
    'modalidade'    => $c['categoriaLabel'],
    'formato'       => $c['modalidade'],
    'duracao'       => $c['duracao'],
    'parcelas'      => $c['parcelas'],
    'valor_parcela' => 'R$ ' . $c['preco'],
    'valor_de'      => 'R$ ' . $c['precoDe'],
    'desconto'      => $c['desconto'] . '%',
    'valor_total'   => 'R$ ' . $c['valorTotal'],
    'oferta_ate'    => $c['ofertaFim'],
    'link'          => 'https://' . dominioDoSite()
                       . '/curso.php?id=' . rawurlencode($c['slug'] !== '' ? $c['slug'] : $c['id']),
  ];
}

/**
 * Áreas de interesse, para responder quem procura um curso que a escola não
 * tem. Cada área junta o que o cliente pode pedir e o que existe no catálogo —
 * quem pergunta de enfermagem está atrás de saúde, e saúde bucal e farmácia são
 * o que temos para oferecer.
 *
 * A sugestão sai sempre do catálogo real: o atendimento nunca inventa curso.
 */
const AREAS = [
  'saúde' => ['enfermagem', 'saude', 'bucal', 'odonto', 'dentista', 'farmacia', 'farmaceutico',
              'radiologia', 'estetica', 'cuidador', 'idoso', 'veterinaria', 'nutricao',
              'fisioterapia', 'psicologia', 'laboratorio', 'analises', 'socorrista', 'samu',
              'obstetricia', 'podologia', 'massagem'],
  'beleza' => ['estetica', 'cabeleireiro', 'manicure', 'maquiagem', 'barbeiro', 'depilacao',
               'sobrancelha', 'unha', 'salao'],
  'administração e negócios' => ['administracao', 'administrativo', 'contabil', 'contabilidade',
              'financeiro', 'rh', 'recursos', 'humanos', 'logistica', 'vendas', 'comercio',
              'secretariado', 'gestao', 'empreendedorismo', 'marketing'],
  'tecnologia' => ['informatica', 'computador', 'programacao', 'programador', 'ti', 'redes',
              'design', 'grafico', 'sistemas', 'software', 'hardware', 'digital', 'dados'],
  'indústria e construção' => ['eletrotecnica', 'eletromecanica', 'eletrica', 'eletricista',
              'mecanica', 'edificacoes', 'construcao', 'civil', 'automacao', 'soldador',
              'solda', 'refrigeracao', 'petroleo', 'industrial'],
  'segurança e meio ambiente' => ['seguranca', 'trabalho', 'ambiente', 'ambiental', 'bombeiro',
              'vigilante', 'sustentabilidade'],
  'educação básica' => ['eja', 'supletivo', 'fundamental', 'medio', 'ensino', 'escola',
              'terminar', 'concluir', 'diploma'],
];

/** A área que a busca sugere, ou '' quando nenhuma palavra combina. */
function areaDaBusca(array $palavras): string {
  foreach (AREAS as $area => $termos) {
    foreach ($palavras as $p) {
      if (strlen($p) < 4) continue;
      foreach ($termos as $t) {
        // "enfermagem" casa com "enfermagem"; "enfermeiro" casa pelo começo
        if (palavraCasa($p, $t)) return $area;
      }
    }
  }
  return '';
}

/** Cursos do catálogo que pertencem à área — o que existe para oferecer. */
function cursosDaArea(array $cursos, string $area, int $limite): array {
  $termos = AREAS[$area] ?? [];
  if (!$termos) return [];

  $lista = [];
  foreach ($cursos as $c) {
    $nome = semAcento($c['nome']);
    foreach ($termos as $t) {
      if (palavraCasa($t, $nome)) {
        $lista[] = cursoParaAtendimento($c);
        break;
      }
    }
    if (count($lista) >= $limite) break;
  }
  return $lista;
}

[$cursos, $origem] = catalogo();

$termo = trim((string) ($_GET['q'] ?? ''));

// Catálogo inteiro: o formato que a home sempre consumiu, intocado.
if ($termo === '') {
  echo json_encode(
    ['ok' => $cursos !== [], 'origem' => $origem, 'total' => count($cursos), 'cursos' => $cursos],
    JSON_UNESCAPED_UNICODE
  );
  exit;
}

$limite = (int) ($_GET['limite'] ?? BUSCA_LIMITE_PADRAO);
$limite = max(1, min(BUSCA_LIMITE_MAXIMO, $limite ?: BUSCA_LIMITE_PADRAO));

$palavras = palavrasBusca($termo);

$achados = [];
foreach ($cursos as $c) {
  $pontos = pontuarCurso($c, $palavras);
  if ($pontos > 0) $achados[] = ['pontos' => $pontos, 'curso' => $c];
}

// Mais aderente primeiro; empate mantém a ordem do catálogo (EJA, técnico, livre)
usort($achados, fn($a, $b) => $b['pontos'] <=> $a['pontos']);

$lista = [];
foreach (array_slice($achados, 0, $limite) as $a) {
  $lista[] = cursoParaAtendimento($a['curso']);
}

// Curso que a escola não tem: em vez de devolver o vazio, diz o que existe na
// mesma área. Quem procurou enfermagem recebe saúde bucal e farmácia.
if ($lista === []) {
  $area      = areaDaBusca($palavras);
  $sugestoes = $area !== '' ? cursosDaArea($cursos, $area, $limite) : [];

  echo json_encode([
    'ok'        => false,
    'origem'    => $origem,
    'busca'     => $termo,
    'total'     => 0,
    'cursos'    => [],
    'mensagem'  => $sugestoes !== []
      ? 'A escola não tem esse curso no catálogo. O que existe na área de ' . $area . ' está em "sugestoes".'
      : 'A escola não tem esse curso no catálogo e não há curso parecido para oferecer.',
    'area'      => $area,
    'sugestoes' => $sugestoes,
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode([
  'ok'     => true,
  'origem' => $origem,
  'busca'  => $termo,
  'total'  => count($lista),
  'cursos' => $lista,
], JSON_UNESCAPED_UNICODE);
