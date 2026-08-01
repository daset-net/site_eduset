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
 * Todas as palavras da busca, inclusive as genéricas. Elas não escolhem o
 * curso — "técnico" sozinho não diz nada —, mas desempatam: quem pediu
 * "técnico em informática" quer o técnico, não a "Informática Básica".
 */
function palavrasDesempate(string $termo): array {
  $limpo = preg_replace('/[^a-z0-9\s]/', ' ', semAcento($termo));
  return array_values(array_filter(
    preg_split('/\s+/', trim($limpo)) ?: [],
    fn($p) => strlen($p) >= 3
  ));
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

/**
 * O curso como o atendimento precisa dele: preço, prazo e o link para mandar.
 *
 * Com $comGrade, vai junto a grade curricular (ava_pacote_curso) e a carga
 * horária somada — a mesma que a página do curso publica. Só o resultado
 * principal leva a grade: repetir a lista de matérias de três cursos estoura o
 * espaço que a resposta tem no prompt.
 */
function cursoParaAtendimento(array $c, bool $comGrade = false): array {
  $dados = cursoResumo($c);
  if (!$comGrade) return $dados;

  $materias = materiasDoCurso($c);
  if ($materias) {
    $dados['carga_horaria'] = array_sum(array_column($materias, 'horas')) . ' horas';
    $dados['materias'] = array_map(
      fn($m) => $m['nome'] . ' (' . (int) $m['horas'] . 'h)',
      $materias
    );
  }
  return $dados;
}

function cursoResumo(array $c): array {
  // A chave do link é o slug quando existe; o id só como reserva, porque há
  // id repetido entre cursos diferentes no catálogo e o slug não tem isso.
  $link = 'https://' . dominioDoSite()
        . '/curso.php?id=' . rawurlencode($c['slug'] !== '' ? $c['slug'] : $c['id']);

  $dados = [
    // O código é a chave para pedir este mesmo curso de novo, sem depender de
    // acertar o nome. É o slug, e não o id_curso, porque há id repetido entre
    // cursos diferentes no catálogo — quem consulta por id repetido pode
    // receber o curso errado, que é o erro que este campo existe para evitar.
    'codigo'         => $c['slug'] !== '' ? $c['slug'] : $c['id'],
    'curso'          => $c['nome'],
    'modalidade'     => $c['categoriaLabel'],
    'formato'        => $c['modalidade'],
    'duracao'        => $c['duracao'],

    // Preço: cada campo diz no próprio nome de que ele é o valor.
    //
    // "valor_de" e "valor_total" não diziam, e o atendimento preencheu o
    // buraco sozinho: anunciou "12x de R$ 155,54 ou R$ 311,08 à vista" quando
    // R$ 311,08 é a parcela sem desconto, e à vista não existe aqui. Nome de
    // campo é o que o modelo lê para saber o que o número significa — e campo
    // de pagamento à vista não existir é o que impede a oferta de existir.
    'parcelamento'                  => $c['parcelas'] . 'x de R$ ' . $c['preco'],
    'parcelas'                      => $c['parcelas'],
    'valor_de_cada_parcela'         => 'R$ ' . $c['preco'],
    'valor_de_cada_parcela_sem_desconto' => 'R$ ' . $c['precoDe'],
    'desconto'                      => $c['desconto'] . '%',
    'valor_total_do_curso'          => 'R$ ' . $c['valorTotal'],
    'oferta_ate'     => $c['ofertaFim'],
    'link'           => $link,
    // Quem já decidiu se matricular não quer ler a página de novo: o ir=matricula
    // abre direto no formulário, com o curso escolhido. O link vai pronto daqui
    // porque quem monta endereço no meio da conversa erra — e o cliente é que
    // acaba clicando num link que não existe.
    'link_matricula' => $link . '&ir=matricula',
  ];

  // Registro da instituição parceira que certifica. Só entra quando existe:
  // curso livre não tem parceira, e mandar o campo vazio faria o atendimento
  // achar que a informação sumiu — em vez de entender que ali ela não se aplica.
  if (($c['codigoMec'] ?? '') !== '') {
    $dados['codigo_registro'] = $c['codigoMec'];

    // O código sozinho não resolve quem pede "o link do MEC": o atendimento
    // não tem de onde tirar endereço nenhum e é proibido de inventar link, então
    // ele só sabia dizer o número. A página de consulta vem daqui, pronta.
    $consulta = linksDeRegistro($c['codigoMec']);
    if ($consulta) $dados['link_registro'] = $consulta;
  }

  return $dados;
}

// Onde o cliente confere cada registro, por órgão. SISTEC certifica o técnico
// e INEP o EJA; o combinado traz os dois, e aí vão os dois links.
const CONSULTA_REGISTRO = [
  'SISTEC' => 'https://sistec.mec.gov.br/consultapublicaunidadeensino',
  'INEP'   => 'https://www.gov.br/inep/pt-br/acesso-a-informacao/dados-abertos/inep-data/catalogo-de-escolas',
];

/**
 * Página de consulta de cada código do registro: ['SISTEC-57209' => 'https://…'].
 *
 * O campo guarda o combinado separado por "_" (SISTEC-57209_INEP-2512979), e o
 * link é por órgão — o cliente que pergunta do EJA confere no INEP, e o do
 * técnico no SISTEC.
 */
function linksDeRegistro(string $codigo): array {
  $links = [];
  foreach (preg_split('/[_\s,;]+/', trim($codigo)) ?: [] as $parte) {
    if ($parte === '') continue;
    $orgao = strtoupper(explode('-', $parte)[0]);
    if (isset(CONSULTA_REGISTRO[$orgao])) $links[$parte] = CONSULTA_REGISTRO[$orgao];
  }
  return $links;
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

// Consulta exata pelo código. O atendimento usa isto quando a conversa já
// tratou de um curso: em vez de tentar acertar o nome outra vez ("e o preço
// dele?"), ele pede o curso que já foi identificado. Sempre com a grade, porque
// aqui é um curso só e a pergunta seguinte costuma ser sobre as matérias.
$codigo = trim((string) ($_GET['codigo'] ?? ''));
if ($codigo !== '') {
  $achado = cursoPorId($codigo);

  echo json_encode($achado
    ? ['ok' => true, 'origem' => $origem, 'busca' => $codigo, 'total' => 1,
       'cursos' => [cursoParaAtendimento($achado, true)]]
    : ['ok' => false, 'origem' => $origem, 'busca' => $codigo, 'total' => 0, 'cursos' => [],
       'mensagem' => 'Nenhum curso com esse código no catálogo. Busque pelo nome.'],
    JSON_UNESCAPED_UNICODE);
  exit;
}

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

// Casar só na descrição não basta: "reconhecido pelo MEC" aparece no texto de
// quase todo curso e devolvia um curso qualquer para quem nem perguntou de
// curso nenhum. Tem que bater no nome — que é o que vale 10 pontos.
$achados = [];
foreach ($cursos as $c) {
  $pontos = pontuarCurso($c, $palavras);
  if ($pontos >= 10) $achados[] = ['pontos' => $pontos, 'curso' => $c];
}

// Mais aderente primeiro. Empatou, valem as palavras genéricas que a busca
// tinha descartado; empatou de novo, ganha o nome mais direto — quem pediu
// "técnico em eletromecânica" quer o curso, e não o combo "EJA + Técnico em
// Eletromecânica", que casa com as mesmas palavras e traz outras por cima.
$desempate = palavrasDesempate($termo);

usort($achados, function ($a, $b) use ($desempate) {
  if ($a['pontos'] !== $b['pontos']) return $b['pontos'] <=> $a['pontos'];

  $amploA = pontuarCurso($a['curso'], $desempate);
  $amploB = pontuarCurso($b['curso'], $desempate);
  if ($amploA !== $amploB) return $amploB <=> $amploA;

  return count(preg_split('/\s+/', trim($a['curso']['nome'])))
     <=> count(preg_split('/\s+/', trim($b['curso']['nome'])));
});

// A grade curricular vai só no primeiro: ela é a lista inteira de matérias, e
// repetir isso para três cursos não caberia na resposta do atendimento
$lista = [];
foreach (array_slice($achados, 0, $limite) as $i => $a) {
  $lista[] = cursoParaAtendimento($a['curso'], $i === 0);
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
