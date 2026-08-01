<?php
// api/_catalogo.php — leitura dos dados do site no Directus.
//
// Três coleções, com papéis bem separados:
//   ava_catalogo_curso    → PREÇO (fonte única; não é duplicado em lugar nenhum)
//   site_catalogo_cursos  → camada editorial do site (imagem, textos, destaque)
//   site_configuracoes    → configurações gerais (contato, textos da home, SEO)
//
// Configuração (EasyPanel → Environment):
//   API_DIRECTUS_CONFIGURACOES   = https://cloud.daset.net
//   TOKEN_DIRECTUS_CONFIGURACOES = <token estático do Directus>
// (também aceita DIRECTUS_URL / DIRECTUS_TOKEN)

const COL_PRECOS  = 'ava_catalogo_curso';
const COL_CURSOS  = 'site_catalogo_cursos';
const COL_CONFIG  = 'site_configuracoes';
const COL_PACOTE     = 'ava_pacote_curso';    // grade curricular (uma linha por matéria)
const COL_EXERCICIOS = 'ava_pacote_materia';  // atividades da matéria (uma linha por questão)
const COL_PROVAS     = 'ava_pacote_prova';    // prova final (uma linha por questão)
const COL_ANEXOS     = 'ava_pacote_anexo';    // apostila e jornada em PDF
const COL_UNIDADES   = 'tabela_unidades';     // polos, para o link de divulgação
const COL_DEPOIMENTOS = 'site_alunos_depoimentos'; // depoimentos de alunos, por curso

const CACHE_TTL    = 600; // segundos
const HTTP_TIMEOUT = 8;

// As três cores que revezam nos cartões: duas do azul da logo (uma escura, uma
// média) e uma do verde do capelo, na mesma proporção em que as duas aparecem
// na marca.
const COR_MAR   = 'linear-gradient(140deg,#00112b,#004aad)';
const COR_AZUL  = 'linear-gradient(140deg,#004aad,#1263d2)';
const COR_VERDE = 'linear-gradient(140deg,#044928,#0f7a44)';

// Emoji por palavra-chave, usado só quando o curso não tem emoji nem capa definidos.
$EMOJIS = [
  'enfermagem' => '🩺', 'saúde bucal' => '🦷', 'estética' => '💅',
  'segurança' => '🦺', 'eletrot' => '⚡', 'eletromec' => '⚙️',
  'meio ambiente' => '🌱', 'edificações' => '🏗️',
  'administra' => '💼', 'contábil' => '🧾', 'informática' => '💻',
  'design' => '🎨', 'fundamental e médio' => '📚', '3º ano' => '📝',
  'médio' => '🎓',
];

$CATEGORIAS = [
  'EJA'          => ['eja', 'Supletivo EJA'],
  'TECNICO'      => ['tecnico', 'Curso Técnico'],
  'INFORMATICA'  => ['livre', 'Curso Livre'],
  'PROFISSIONAL' => ['livre', 'Curso Livre'],
];

// ---------------------------------------------------------------- config
function conexao(string $chave, string $padrao = ''): string {
  // Nomes equivalentes: o padrão dos outros sistemas EDUSET (usado no EasyPanel)
  // e os nomes curtos deste projeto. Tenta ambos, em variável de ambiente e arquivo.
  $mapa = [
    'DIRECTUS_URL'   => 'API_DIRECTUS_CONFIGURACOES',
    'DIRECTUS_TOKEN' => 'TOKEN_DIRECTUS_CONFIGURACOES',
  ];
  $nomes = array_unique([$chave, $mapa[$chave] ?? $chave]);

  // Sob LiteSpeed/LSPHP a variável pode chegar por getenv, $_ENV ou $_SERVER.
  foreach ($nomes as $nome) {
    foreach ([getenv($nome), $_ENV[$nome] ?? false, $_SERVER[$nome] ?? false] as $valor) {
      if ($valor !== false && $valor !== '') return (string) $valor;
    }
  }

  foreach ($nomes as $nome) {
    $valor = arquivosConexao()[$nome] ?? '';
    if ($valor !== '') return $valor;
  }
  return $padrao;
}

/** Lê as credenciais de arquivos (dev local e .env gerado pelo entrypoint), com cache. */
function arquivosConexao(): array {
  static $dados = null;
  if ($dados !== null) return $dados;

  $dados = [];
  foreach ([
    __DIR__ . '/../../conexao/conexao_directus_avaset_unico_eduset.txt',        // dev local
    __DIR__ . '/../../conexao_eduset/conexao_directus_avaset_unico_eduset.txt', // dev local (nome antigo da pasta)
    __DIR__ . '/../../.env',                                                   // gravado pelo entrypoint
    __DIR__ . '/../.env',
    __DIR__ . '/.env',
    getcwd() . '/.env',
    '/.env',
    '/app/.env',
  ] as $caminho) {
    foreach (@file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $linha) {
      $linha = trim($linha);
      if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) continue;
      [$k, $v] = explode('=', $linha, 2);
      $k = trim($k);
      if (!isset($dados[$k])) $dados[$k] = trim($v, " \t\"'");
    }
  }
  return $dados;
}

// ---------------------------------------------------------------- Directus
function directusBase(): string {
  return rtrim(conexao('DIRECTUS_URL'), '/');
}

/** GET numa coleção do Directus. Devolve as linhas ou null se falhar. */
function buscarColecao(string $colecao, array $params = []): ?array {
  $base  = directusBase();
  $token = conexao('DIRECTUS_TOKEN');
  if ($base === '' || $token === '') return null;

  $url = $base . '/items/' . $colecao . '?' . http_build_query($params + ['limit' => -1]);

  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => HTTP_TIMEOUT,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
  ]);
  $corpo  = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($corpo === false || $status !== 200) return null;
  $json = json_decode($corpo, true);
  return isset($json['data']) && is_array($json['data']) ? $json['data'] : null;
}

// ---------------------------------------------------------------- helpers
function caminhoCache(string $nome = 'catalogo'): string {
  return rtrim(sys_get_temp_dir(), '/\\') . '/eduset_' . $nome . '.json';
}

/** Invalida o cache para que uma edição no Directus apareça no site na hora. */
function limparCache(): void {
  foreach (['catalogo', 'config', 'avisos', 'materias', 'unidades', CACHE_UNIDADES] as $nome) {
    @unlink(caminhoCache($nome));
  }
}

function nomeCurso(string $bruto): string {
  $nome = preg_replace('/^(Tecnologia|Profissional|Suporte)\s+/u', '', trim($bruto));
  return preg_replace('/\s+/u', ' ', $nome);
}

function emojiDe(string $nome, array $emojis): string {
  $alvo = mb_strtolower($nome, 'UTF-8');
  foreach ($emojis as $chave => $emoji) {
    if (mb_strpos($alvo, $chave, 0, 'UTF-8') !== false) return $emoji;
  }
  return '📘';
}

function moeda($valor): string {
  return number_format((float) $valor, 2, ',', '.');
}

/** Quebra um campo de texto "um item por linha" numa lista limpa. */
function linhas(?string $texto): array {
  if ($texto === null || trim($texto) === '') return [];
  $itens = preg_split('/\R/u', $texto);
  return array_values(array_filter(array_map('trim', $itens), fn($i) => $i !== ''));
}

// ---------------------------------------------------------------- configurações do site
/** Valor de uma chave da site_configuracoes. */
function config(string $chave, string $padrao = ''): string {
  static $mapa = null;
  if ($mapa === null) {
    $mapa = [];
    $cache = caminhoCache('config');

    $linhas = null;
    if (is_readable($cache) && (time() - filemtime($cache) < CACHE_TTL)) {
      $linhas = json_decode((string) file_get_contents($cache), true);
    }
    if (!is_array($linhas)) {
      $linhas = buscarColecao(COL_CONFIG, ['fields' => 'chave,valor,valor_extendido']);
      if ($linhas !== null) {
        @file_put_contents($cache, json_encode($linhas, JSON_UNESCAPED_UNICODE));
      } elseif (is_readable($cache)) {
        $linhas = json_decode((string) file_get_contents($cache), true) ?: [];
      } else {
        $linhas = [];
      }
    }
    foreach ($linhas as $l) {
      // valor_extendido tem precedência: é onde ficam os textos longos.
      $v = trim((string) ($l['valor_extendido'] ?? '')) !== ''
        ? $l['valor_extendido']
        : ($l['valor'] ?? '');
      if (isset($l['chave'])) $mapa[$l['chave']] = (string) $v;
    }
  }
  return ($mapa[$chave] ?? '') !== '' ? $mapa[$chave] : $padrao;
}

// ---------------------------------------------------------------- polo (unidade)
// Link de divulgação de um polo: eduset.com.br/?polo=centro.aracaju.se
// O código é o e-mail da unidade sem o domínio. Guardado em cookie por 30 dias,
// ele viaja na matrícula e trava o aluno naquela unidade; sem ele, o AVASET
// registra a venda na unidade EAD do estado do aluno.
const POLO_COOKIE = 'eduset_polo';
const POLO_DIAS   = 30;

/** O código do polo desta visita — do link, ou do cookie deixado por ele. */
function poloSlug(): string {
  static $slug = null;
  if ($slug !== null) return $slug;

  $valido = fn(string $v): bool => (bool) preg_match('/^[a-z0-9._-]{2,80}$/', $v);

  $doLink = strtolower(trim((string) ($_GET['polo'] ?? '')));
  if ($valido($doLink)) {
    if (!headers_sent()) {
      setcookie(POLO_COOKIE, $doLink, [
        'expires'  => time() + POLO_DIAS * 86400,
        'path'     => '/',
        'samesite' => 'Lax',
      ]);
    }
    return $slug = $doLink;
  }

  $doCookie = strtolower(trim((string) ($_COOKIE[POLO_COOKIE] ?? '')));
  return $slug = $valido($doCookie) ? $doCookie : '';
}

/**
 * Dados públicos do polo do link (nome/cidade/estado), só para o selo da página.
 * Devolve null se não houver link, se a unidade não existir ou estiver inativa —
 * e nesse caso a página não mostra selo nenhum, mas o código continua viajando
 * na matrícula: quem decide de verdade é o AVASET.
 */
function poloUnidade(): ?array {
  static $unidade = false;
  if ($unidade !== false) return $unidade;

  $slug = poloSlug();
  if ($slug === '') return $unidade = null;

  $cache = caminhoCache('polo_' . $slug);
  if (is_readable($cache) && (time() - filemtime($cache) < CACHE_TTL)) {
    $guardado = json_decode((string) file_get_contents($cache), true);
    if (is_array($guardado)) return $unidade = ($guardado['nome'] ?? '') !== '' ? $guardado : null;
  }

  $linhas = buscarColecao(COL_UNIDADES, [
    'fields' => 'unidade_nome,unidade_email,cidade,estado,situacao',
    'filter' => json_encode([
      'unidade_email' => ['_starts_with' => $slug . '@'],
      'situacao'      => ['_eq' => 'ativo'],
    ]),
    'limit'  => 1,
  ]);

  // Falha de rede/permissão: não grava cache para tentar de novo na próxima.
  if ($linhas === null) return $unidade = null;

  $achado = $linhas[0] ?? null;
  $dados  = [
    'nome'   => (string) ($achado['unidade_nome'] ?? ''),
    'cidade' => (string) ($achado['cidade'] ?? ''),
    'estado' => (string) ($achado['estado'] ?? ''),
  ];
  @file_put_contents($cache, json_encode($dados, JSON_UNESCAPED_UNICODE));

  return $unidade = $dados['nome'] !== '' ? $dados : null;
}

// ---------------------------------------------------------------- unidades
// A página de unidades (unidades.php e unidade.php) lê a mesma tabela_unidades
// do link de divulgação. Só entra polo físico: a unidade EAD de cada estado é o
// destino padrão da venda do site, não é lugar para o aluno procurar, e o
// e-mail dela carrega o segmento "ead" — é por aí que as duas se distinguem.
//
// A tabela é operacional (tem senha, chave de API, CNPJ, repasse). O site lê só
// nome, cidade e estado: endereço e telefone da unidade NÃO são publicados, e
// nem chegam a ser consultados. Todo contato do site é pelo canal da escola.

const UNIDADE_CAMPOS = 'unidade_nome,unidade_email,cidade,estado,situacao';

// Versão do cache: o formato da unidade publicada mudou quando a EAD entrou na
// lista, e o arquivo antigo não deve ser reaproveitado.
const CACHE_UNIDADES = 'unidades_v2';

const UF_NOMES = [
  'AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas',
  'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal',
  'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão',
  'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais',
  'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco',
  'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte',
  'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima',
  'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe',
  'TO' => 'Tocantins',
];

/** Texto sem acento e em minúsculas, para comparar cadastro escrito de vários jeitos. */
function semAcento(string $texto): string {
  return strtr(mb_strtolower(trim($texto), 'UTF-8'), [
    'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','ê'=>'e','è'=>'e',
    'í'=>'i','ì'=>'i','î'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ò'=>'o','ö'=>'o',
    'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c',
  ]);
}

/** Sigla do estado. O cadastro grava ora "BA", ora "Bahia", ora nada. */
function siglaEstado(string $bruto): string {
  $texto = trim($bruto);
  if ($texto === '') return '';

  if (preg_match('/^[A-Za-z]{2}$/', $texto)) {
    $uf = strtoupper($texto);
    return isset(UF_NOMES[$uf]) ? $uf : '';
  }

  static $porNome = null;
  if ($porNome === null) {
    $porNome = [];
    foreach (UF_NOMES as $uf => $nome) $porNome[semAcento($nome)] = $uf;
  }
  return $porNome[semAcento($texto)] ?? '';
}

/** Nome do estado por extenso, para o título da página. */
function nomeEstado(string $uf): string {
  return UF_NOMES[strtoupper(trim($uf))] ?? $uf;
}

/**
 * "na Bahia", "em São Paulo", "no Ceará" — a preposição muda com o estado e
 * texto de site com "no Bahia" cheira a formulário mal preenchido.
 */
function estadoComPreposicao(string $uf): string {
  $nome = nomeEstado($uf);
  if ($nome === '' || $nome === $uf) return $nome;

  $preposicao = [
    'BA' => 'na', 'PB' => 'na',
    'AL' => 'em', 'GO' => 'em', 'MG' => 'em', 'PE' => 'em', 'RO' => 'em',
    'RR' => 'em', 'SC' => 'em', 'SP' => 'em', 'SE' => 'em',
  ][strtoupper($uf)] ?? 'no';

  return $preposicao . ' ' . $nome;
}

/**
 * Cadastro em caixa alta ("CAMACAN") vira nome próprio na página.
 * Quem já foi digitado direito passa intocado.
 */
function nomeProprio(string $texto): string {
  $texto = trim($texto);
  if ($texto === '' || $texto !== mb_strtoupper($texto, 'UTF-8')) return $texto;

  $titulo = mb_convert_case(mb_strtolower($texto, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
  return preg_replace_callback(
    '/\b(De|Do|Da|Dos|Das|E)\b/u',
    fn($m) => mb_strtolower($m[1], 'UTF-8'),
    $titulo
  );
}

/** Código da unidade no link de divulgação: o e-mail sem o domínio. */
function codigoUnidade(string $email): string {
  $codigo = strtolower(trim(explode('@', $email)[0] ?? ''));
  return preg_match('/^[a-z0-9._-]{2,80}$/', $codigo) ? $codigo : '';
}

/**
 * A unidade EAD do estado — a que atende a distância, sem endereço de balcão.
 *
 * Não existe coluna de flag: é a mesma regra do GESET. O e-mail gerado na
 * criação carrega o segmento "ead" (ead.cidade.uf@ ou cidade.uf.ead@, conforme
 * a época do cadastro) e o nome sempre termina em EAD — e o nome é o que se
 * mantém correto quando a unidade é editada, porque o e-mail nunca é regerado.
 *
 * O site não separa mais as duas na tela: toda unidade ativa é só "unidade".
 * A regra sobrou para tirar o "EAD" do fim do nome da cidade e da referência.
 */
function ehUnidadeEad(string $email, string $nome = ''): bool {
  $local = strtolower(explode('@', $email)[0] ?? '');
  if (in_array('ead', explode('.', $local), true)) return true;
  return (bool) preg_match('/\bEAD$/i', trim($nome));
}

/**
 * Tira o "EAD" que fecha o cadastro da unidade a distância ("Curitiba EAD",
 * "SP - Santos - EAD"). O cartão já diz que o atendimento é a distância; repetir
 * a sigla no nome da cidade só polui.
 */
function semSufixoEad(string $texto): string {
  return trim(preg_replace('/[\s·\-–]*\bEAD\b\s*$/iu', '', trim($texto)));
}

/**
 * Uma linha da tabela_unidades no formato que a página publica: onde a unidade
 * fica, e só.
 *
 * Cadastro antigo deixou cidade e estado em branco, mas o nome da unidade segue
 * o padrão "Estado - Cidade - Referência" — quando o campo falta, o nome
 * completa. O que sobra depois da cidade (bairro, distrito, shopping) vira a
 * referência que diferencia dois polos da mesma cidade.
 */
function unidadePublica(array $linha): array {
  $email  = strtolower(trim((string) ($linha['unidade_email'] ?? '')));
  $nome   = preg_replace('/\s+/u', ' ', trim((string) ($linha['unidade_nome'] ?? '')));
  $cidade = nomeProprio((string) ($linha['cidade'] ?? ''));
  $uf     = siglaEstado((string) ($linha['estado'] ?? ''));
  $ead    = ehUnidadeEad($email, $nome);

  $partes = array_values(array_filter(array_map('trim', explode(' - ', $nome)), fn($p) => $p !== ''));
  $ufNome = count($partes) > 1 ? siglaEstado($partes[0]) : '';
  $refs   = [];
  if ($ufNome !== '') {
    if ($uf === '')     $uf = $ufNome;
    if ($cidade === '') $cidade = nomeProprio($partes[1] ?? '');
    $refs = array_slice($partes, 2);
  }

  // "EAD" é o tipo da unidade, não bairro nem cidade: sai da referência e do
  // nome da cidade, e volta como rótulo próprio no cartão.
  if ($ead) {
    $refs   = array_values(array_filter($refs, fn($r) => semSufixoEad($r) !== ''));
    $cidade = semSufixoEad($cidade);
  }

  return [
    'codigo'     => codigoUnidade($email),
    'nome'       => $nome,
    'cidade'     => $cidade,
    'uf'         => $uf,
    'estado'     => $uf !== '' ? nomeEstado($uf) : '',
    'referencia' => implode(' · ', $refs),
    'ead'        => $ead,
  ];
}

/** Todas as unidades ativas no formato público, com cache em disco. */
function unidadesCadastradas(): array {
  static $lista = null;
  if ($lista !== null) return $lista;

  $cache = caminhoCache(CACHE_UNIDADES);
  if (is_readable($cache) && (time() - filemtime($cache) < CACHE_TTL)) {
    $guardado = json_decode((string) file_get_contents($cache), true);
    if (is_array($guardado)) return $lista = $guardado;
  }

  $linhas = buscarColecao(COL_UNIDADES, [
    'fields' => UNIDADE_CAMPOS,
    'filter' => json_encode(['situacao' => ['_eq' => 'ativo']]),
    'sort'   => 'unidade_nome',
  ]);

  // Directus fora do ar: serve a última lista conhecida, mesmo vencida.
  if ($linhas === null) {
    $guardado = is_readable($cache) ? json_decode((string) file_get_contents($cache), true) : null;
    return $lista = is_array($guardado) ? $guardado : [];
  }

  $lista = [];
  foreach ($linhas as $l) {
    $u = unidadePublica($l);
    if ($u['codigo'] !== '' && $u['nome'] !== '') $lista[] = $u;
  }

  @file_put_contents($cache, json_encode($lista, JSON_UNESCAPED_UNICODE));
  return $lista;
}

/** A ordem em que a página lista: estado, cidade, nome. */
function ordenarUnidades(array $lista): array {
  usort($lista, function ($a, $b) {
    return [$a['uf'], semAcento($a['cidade']), semAcento($a['nome'])]
       <=> [$b['uf'], semAcento($b['cidade']), semAcento($b['nome'])];
  });
  return $lista;
}

/** Toda unidade ativa, na ordem da página. */
function unidadesListadas(): array {
  return ordenarUnidades(unidadesCadastradas());
}

/** Unidades agrupadas por estado: ['CE' => [...], 'SP' => [...]]. */
function unidadesPorEstado(): array {
  $mapa = [];
  foreach (unidadesListadas() as $u) {
    $mapa[$u['uf'] !== '' ? $u['uf'] : '--'][] = $u;
  }
  return $mapa;
}

/** Uma unidade pelo código do link de divulgação (ex.: centro.aracaju.se). */
function unidadePorCodigo(string $codigo): ?array {
  $codigo = strtolower(trim($codigo));
  foreach (unidadesCadastradas() as $u) {
    if ($u['codigo'] === $codigo) return $u;
  }
  return null;
}

/** Siglas dos estados atendidos, por polo ou a distância. */
function estadosAtendidos(): array {
  $ufs = [];
  foreach (unidadesCadastradas() as $u) {
    if ($u['uf'] !== '' && !in_array($u['uf'], $ufs, true)) $ufs[] = $u['uf'];
  }
  sort($ufs);
  return $ufs;
}

/** Quantas cidades diferentes a lista cobre. */
function contarCidades(array $lista): int {
  $cidades = [];
  foreach ($lista as $u) {
    if ($u['cidade'] === '') continue;
    $chave = semAcento($u['cidade']) . '/' . $u['uf'];
    if (!in_array($chave, $cidades, true)) $cidades[] = $chave;
  }
  return count($cidades);
}

/**
 * URL de um arquivo estático com a data da última alteração no fim
 * (`assets/css/style.css?v=1753654321`).
 *
 * O LiteSpeed guarda a resposta de CSS e JS por sete dias e manda o navegador
 * guardar pelo mesmo tempo. Sem isso, uma correção de estilo só chega ao
 * visitante uma semana depois — foi o que aconteceu quando a lista de unidades
 * ganhou estilo novo e o site continuou servindo o CSS da véspera.
 *
 * $arquivo é o caminho a partir do docroot; $prefixo é o que a página precisa
 * pôr na frente para chegar lá ('../' nas telas do painel).
 */
function versao(string $arquivo, string $prefixo = ''): string {
  $mtime = @filemtime(__DIR__ . '/../' . $arquivo);
  return $prefixo . $arquivo . ($mtime ? '?v=' . $mtime : '');
}

/** URL da imagem de capa (servida pelo proxy, para não expor o token). */
function urlImagem(?string $uuid): string {
  if (!$uuid || !preg_match('/^[0-9a-f-]{36}$/i', $uuid)) return '';
  return 'api/imagem.php?id=' . $uuid;
}

// ---------------------------------------------------------------- oferta do ciclo
/**
 * Linha de BOLSA do catálogo — não pode ser anunciada nem vendida pelo site.
 *
 * Bolsa é concessão da escola, decidida caso a caso no GESET, e o próprio
 * endpoint de matrícula externa do AVASET recusa desconto de 60% ou mais vindo
 * de fora. Anunciar esse preço seria prometer o que a matrícula não entrega,
 * então ele é descartado antes de qualquer cálculo: nem vitrine, nem contador,
 * nem formulário.
 */
function ehBolsa(array $linha): bool {
  if (strtolower(trim((string) ($linha['ingresso'] ?? ''))) === 'bolsa') return true;
  return (float) ($linha['desconto'] ?? 0) >= 60;
}

/**
 * Ciclo da campanha: o desconto anunciado vale por um período fechado e muda
 * quando o período vira. O contador na página aponta para esse fim — quando ele
 * zera, o preço realmente muda, então não há prazo de mentira.
 *
 * @return array{indice:int, fim:int} índice do ciclo e quando ele termina (epoch)
 */
function cicloOferta(): array {
  $dias = max(1, (int) config('oferta_ciclo_dias', '7'));
  $tz   = new DateTimeZone('America/Fortaleza');

  // Marco fixo numa segunda-feira: os ciclos sempre começam na segunda.
  $marco   = (new DateTime('2026-01-05 00:00:00', $tz))->getTimestamp();
  $periodo = $dias * 86400;
  $indice  = (int) floor(((new DateTime('now', $tz))->getTimestamp() - $marco) / $periodo);

  return ['indice' => $indice, 'fim' => $marco + ($indice + 1) * $periodo];
}

/** Fuso em que a escola pensa as datas de campanha. */
const TZ_CAMPANHA = 'America/Fortaleza';

/**
 * Campanha de desconto definida pela escola no painel do site (aba Campanhas),
 * que MANDA sobre a rotação automática.
 *
 * Guardada na chave `oferta_campanhas` da site_configuracoes, em JSON:
 *
 *   {"permanente": 50,
 *    "programadas": [{"nome":"Semana do Cliente","desconto":50,
 *                     "inicio":"2026-09-01","fim":"2026-09-10"}]}
 *
 * Só uma coisa vale por vez, e é isso que o painel garante ao salvar: com um
 * desconto permanente ligado não existe programação, e com programação não
 * existe permanente. Datas são inclusivas, do primeiro instante do dia de
 * início ao último do dia de fim.
 *
 * Sem campanha nenhuma, devolve null e o site volta a girar os descontos
 * sozinho (ver ofertaDoCiclo) — que é o comportamento de sempre.
 *
 * @return array{desconto:int, fim:?int, permanente:bool}|null  fim em epoch
 */
function campanhaVigente(): ?array {
  static $achada = false;
  if ($achada !== false) return $achada;

  $dados = json_decode(trim(config('oferta_campanhas', '')), true);
  return $achada = is_array($dados) ? campanhaDe($dados) : null;
}

/**
 * A mesma decisão, a partir de um estado já em mãos — é o que o painel usa para
 * mostrar o que está no ar logo depois de salvar, sem esbarrar no cache da
 * leitura pública. Uma implementação só, para painel e site nunca discordarem.
 */
function campanhaDe(array $dados): ?array {
  // Permanente ganha de tudo: não tem prazo, então o contador nem aparece.
  $permanente = (int) ($dados['permanente'] ?? 0);
  if ($permanente > 0) {
    return ['desconto' => $permanente, 'fim' => null, 'permanente' => true];
  }

  $tz    = new DateTimeZone(TZ_CAMPANHA);
  $agora = (new DateTime('now', $tz))->getTimestamp();

  foreach ($dados['programadas'] ?? [] as $c) {
    $janela = janelaCampanha($c);
    if (!$janela) continue;

    if ($agora >= $janela[0] && $agora <= $janela[1]) {
      return [
        'desconto'   => (int) $c['desconto'],
        'fim'        => $janela[1],
        'permanente' => false,
      ];
    }
  }

  return null;
}

/**
 * Início e fim de uma campanha programada, em epoch. Datas são inclusivas: do
 * primeiro instante do dia de início ao último do dia de fim.
 *
 * @return array{0:int, 1:int}|null null quando a linha está incompleta ou inválida
 */
function janelaCampanha(array $c): ?array {
  $desconto = (int) ($c['desconto'] ?? 0);
  $inicio   = trim((string) ($c['inicio'] ?? ''));
  $fim      = trim((string) ($c['fim'] ?? ''));
  if ($desconto <= 0 || $inicio === '' || $fim === '') return null;

  $tz  = new DateTimeZone(TZ_CAMPANHA);
  $de  = DateTime::createFromFormat('Y-m-d H:i:s', $inicio . ' 00:00:00', $tz);
  $ate = DateTime::createFromFormat('Y-m-d H:i:s', $fim . ' 23:59:59', $tz);
  if (!$de || !$ate || $ate->getTimestamp() < $de->getTimestamp()) return null;

  return [$de->getTimestamp(), $ate->getTimestamp()];
}

/**
 * Versão do curso mais próxima do desconto que a campanha pediu.
 *
 * Não todo curso tem todas as faixas. Com a campanha em 50% e um curso que só
 * tem 30% e 40%, anunciamos 40%: o maior degrau que existe sem passar do
 * pedido. Se o curso só tiver faixas ACIMA do pedido, vai a menor delas — é a
 * mais perto do que a escola quis, e continua sendo uma linha real do catálogo.
 *
 * @param array $ativas versões não-bolsa, já ordenadas do maior desconto ao menor
 */
/**
 * Quando a condição anunciada termina, para o contador da página do curso.
 *
 * Campanha permanente não tem fim: devolve vazio, e o contador se esconde
 * sozinho (curso.js some com o bloco quando a data não é válida). Prazo só
 * aparece quando existe prazo de verdade — o site não inventa urgência.
 */
function fimDaOferta(): string {
  $campanha = campanhaVigente();
  if ($campanha) {
    return $campanha['permanente'] ? '' : date('c', $campanha['fim']);
  }
  return date('c', cicloOferta()['fim']);
}

function versaoParaCampanha(array $ativas, int $alvo): ?array {
  if (!$ativas) return null;

  foreach ($ativas as $v) {
    if ((int) ($v['desconto'] ?? 0) === $alvo) return $v;
  }
  foreach ($ativas as $v) {
    if ((float) ($v['desconto'] ?? 0) < $alvo) return $v;
  }

  return $ativas[count($ativas) - 1];
}

/**
 * Versão do curso que está em oferta neste ciclo.
 *
 * O catálogo tem a mesma matrícula em vários descontos (30/40/50/60%). Em vez de
 * anunciar sempre o mesmo, o site gira a escada: um ciclo em 60%, outro em 50%,
 * outro em 40%. Todos os cursos giram juntos — é uma campanha só, mais fácil de
 * comunicar e de o aluno entender.
 *
 * Uma campanha definida no painel (campanhaVigente) tem prioridade sobre essa
 * rotação: enquanto ela vale, o site anuncia o desconto que a escola escolheu.
 *
 * Configurações (site_configuracoes):
 *   oferta_modo       = rotativo (padrão) | fixo — fixo trava no maior desconto
 *   oferta_niveis     = quantos degraus entram na rotação (padrão 3: 60/50/40)
 *   oferta_ciclo_dias = duração do ciclo em dias (padrão 7)
 *   oferta_offset     = desloca a rotação, para escolher em que degrau ela começa
 */
function ofertaDoCiclo(array $versoes): ?array {
  $ativas = [];
  foreach ($versoes as $v) {
    if (($v['ativo'] ?? true) === false) continue;
    if ((float) ($v['valor_parcela'] ?? 0) <= 0) continue;
    if (ehBolsa($v)) continue;   // bolsa não é oferta de site
    $ativas[] = $v;
  }
  if (!$ativas) return null;

  // Do maior desconto para o menor; empate desempata pela parcela mais barata.
  usort($ativas, function ($a, $b) {
    $da = (float) ($a['desconto'] ?? 0);
    $db = (float) ($b['desconto'] ?? 0);
    if ($da !== $db) return $db <=> $da;
    return (float) $a['valor_parcela'] <=> (float) $b['valor_parcela'];
  });

  // Campanha da escola manda: enquanto ela vale, não há rotação.
  $campanha = campanhaVigente();
  if ($campanha) return versaoParaCampanha($ativas, $campanha['desconto']);

  if (strtolower(config('oferta_modo', 'rotativo')) === 'fixo') return $ativas[0];

  $niveis = max(1, (int) config('oferta_niveis', '3'));
  $escada = array_slice($ativas, 0, min($niveis, count($ativas)));

  $passo = cicloOferta()['indice'] + (int) config('oferta_offset', '0');
  return $escada[(($passo % count($escada)) + count($escada)) % count($escada)];
}

// ---------------------------------------------------------------- montagem
/**
 * Junta preço (ava_catalogo_curso) com a camada editorial (site_catalogo_cursos).
 * O preço nunca é lido da tabela do site — ela não guarda valores.
 */
function montarCatalogo(array $precos, array $editorial, array $ctx): array {
  extract($ctx); // $EMOJIS, $CATEGORIAS

  // Categoria do curso → [slug, rótulo]. Categoria desconhecida cai em "Curso Livre",
  // para que nenhum curso do catálogo fique de fora do site.
  $categoriaDe = function (?string $cat) use ($CATEGORIAS): array {
    return $CATEGORIAS[strtoupper((string) $cat)] ?? ['livre', 'Curso Livre'];
  };

  $site = [];
  foreach ($editorial as $e) {
    if (!empty($e['id_curso'])) $site[$e['id_curso']] = $e;
  }

  // Uma linha por curso: a versão em oferta neste ciclo (ver ofertaDoCiclo).
  // Regra: todos os cursos do catálogo aparecem no site, MENOS os desativados no
  // AVASET (ava_catalogo_curso.ativo=false). A ficha em site_catalogo_cursos é
  // opcional — só enriquece (capa/textos) e pode ocultar localmente pelo painel.
  $versoes = [];
  foreach ($precos as $l) {
    $id = $l['id_curso'] ?? '';
    if ($id === '') continue;
    if (($l['ativo'] ?? true) === false) continue;   // curso desativado no AVASET

    $s = $site[$id] ?? null;
    if ($s && !($s['ativo'] ?? true)) continue;      // oculto pelo painel do site

    $versoes[$id][] = $l;
  }

  $melhores = [];
  foreach ($versoes as $id => $lista) {
    $escolhida = ofertaDoCiclo($lista);
    if ($escolhida) $melhores[$id] = $escolhida;
  }

  $peso = ['eja' => 1, 'tecnico' => 2, 'livre' => 3];
  uasort($melhores, function ($a, $b) use ($categoriaDe, $peso, $site) {
    $ca = $peso[$categoriaDe($a['categoria'] ?? '')[0]];
    $cb = $peso[$categoriaDe($b['categoria'] ?? '')[0]];
    if ($ca !== $cb) return $ca <=> $cb;
    $oa = (int) ($site[$a['id_curso']]['ordem'] ?? 999);
    $ob = (int) ($site[$b['id_curso']]['ordem'] ?? 999);
    return $oa <=> $ob ?: strcmp((string) $a['id_curso'], (string) $b['id_curso']);
  });

  $cores  = [COR_MAR, COR_AZUL, COR_VERDE];
  $cursos = [];
  $i = 0;

  foreach ($melhores as $id => $l) {
    [$slug, $rotulo] = $categoriaDe($l['categoria'] ?? '');
    $s        = $site[$id] ?? [];
    $parcelas = (int) ($l['qtd_parcela'] ?? 0);
    $nome     = trim($s['nome_exibicao'] ?? '') !== '' ? $s['nome_exibicao'] : nomeCurso($l['curso'] ?? '');

    $cursos[] = [
      'id'             => $id,
      'categoria'      => $slug,
      'categoriaLabel' => $rotulo,
      'nome'           => $nome,
      'slug'           => $s['slug'] ?? '',
      'emoji'          => trim($s['emoji'] ?? '') !== '' ? $s['emoji'] : emojiDe($nome, $EMOJIS),
      'imagem'         => urlImagem($s['imagem_capa'] ?? null),
      'descricao'      => trim($s['descricao_card'] ?? '') !== ''
                            ? $s['descricao_card']
                            : 'Curso com certificação reconhecida e material 100% online.',
      'duracao'        => trim($s['duracao'] ?? '') !== ''
                            ? $s['duracao']
                            : ($parcelas > 0 ? $parcelas . ' meses' : 'Flexível'),
      'modalidade'     => trim($s['modalidade'] ?? '') !== ''
                            ? $s['modalidade']
                            : ($slug === 'tecnico' ? 'EAD com polo de apoio' : 'EAD'),

      // preço — sempre do ava_catalogo_curso
      'preco'          => moeda($l['valor_parcela']),
      'precoDe'        => moeda($l['valor_parcela_normal'] ?? 0),
      'parcelas'       => $parcelas,
      'desconto'       => (int) ($l['desconto'] ?? 0),
      'valorTotal'     => moeda($l['valor_total'] ?? 0),
      'codigo'         => $l['codigo_unico_especial'] ?? $id,

      // Código da instituição parceira que certifica (SISTEC para técnico, INEP
      // para EJA). A ficha do site manda, porque é ela que a secretaria edita;
      // o catálogo do AVA é a reserva para o curso que ainda não tem ficha.
      // Curso livre não tem parceira e fica com string vazia.
      'codigoMec'      => trim((string) ($s['codigo_mec_parceiro'] ?? '')) !== ''
                            ? trim((string) $s['codigo_mec_parceiro'])
                            : trim((string) ($l['codigo_mec_parceiro'] ?? '')),

      // Onde o cliente confere esse registro. Vem da ficha, e não de uma regra
      // no código, porque quem sabe qual órgão vale em cada curso é a secretaria
      // — no conjugado ela aponta um só, o do SISTEC.
      'linkMec'        => trim((string) ($s['link_consulta_mec'] ?? '')),
      'economia'       => moeda(max(0, (float) ($l['valor_parcela_normal'] ?? 0) - (float) ($l['valor_parcela'] ?? 0))),
      'ofertaFim'      => fimDaOferta(),

      // conteúdo da página de conversão
      'chamada'        => $s['chamada']  ?? '',
      'promessa'       => $s['promessa'] ?? '',
      'mercado'        => $s['mercado']  ?? '',
      'aprender'       => linhas($s['aprender'] ?? null),
      'publico'        => linhas($s['publico']  ?? null),
      'saidas'         => linhas($s['saidas']   ?? null),
      'seoTitulo'      => $s['seo_titulo']    ?? '',
      'seoDescricao'   => $s['seo_descricao'] ?? '',
      'cargaMinima'    => (int) ($s['carga_horaria_minima'] ?? 0),

      'cor'            => $cores[$i++ % 3],
    ];
  }

  return $cursos;
}

/**
 * Catálogo pronto para exibição, com cache em disco.
 * @return array{0: array, 1: string} lista de cursos e origem dos dados
 */
function catalogo(): array {
  global $EMOJIS, $CATEGORIAS;
  $ctx = compact('EMOJIS', 'CATEGORIAS');

  $cache = caminhoCache();

  if (is_readable($cache) && (time() - filemtime($cache) < CACHE_TTL)) {
    $cursos = json_decode((string) file_get_contents($cache), true);
    if (is_array($cursos) && $cursos !== []) return [$cursos, 'cache'];
  }

  $precos    = buscarColecao(COL_PRECOS, ['fields' =>
    'id_curso,categoria,curso,ingresso,desconto,qtd_parcela,valor_parcela,'
    . 'valor_parcela_normal,valor_total,codigo_unico_especial,codigo_mec_parceiro,ativo']);
  $editorial = buscarColecao(COL_CURSOS, ['fields' => '*']);

  if ($precos !== null) {
    $cursos = montarCatalogo($precos, $editorial ?? [], $ctx);
    if ($cursos !== []) {
      @file_put_contents($cache, json_encode($cursos, JSON_UNESCAPED_UNICODE));
      return [$cursos, 'directus'];
    }
  }

  // Directus fora do ar: serve o último catálogo conhecido, mesmo vencido.
  if (is_readable($cache)) {
    $cursos = json_decode((string) file_get_contents($cache), true);
    if (is_array($cursos) && $cursos !== []) return [$cursos, 'cache-expirado'];
  }

  return [[], 'indisponivel'];
}

/**
 * Linha bruta do ava_catalogo_curso que a matrícula deve usar: a mesma versão
 * que o site está anunciando neste ciclo (ver ofertaDoCiclo).
 *
 * Vai direto ao Directus (sem cache) e devolve os valores originais, não os
 * formatados. É a fonte do preço na hora de matricular: nada de financeiro
 * chega pelo navegador, então adulterar o formulário não muda o que é gravado.
 * Devolve null se o curso não existe ou está desativado no AVASET.
 */
function planoVigente(string $idCurso): ?array {
  if ($idCurso === '') return null;

  $linhas = buscarColecao(COL_PRECOS, [
    'filter' => ['id_curso' => ['_eq' => $idCurso]],
    'fields' => 'id_curso,curso,categoria,id_unico,ingresso,desconto,qtd_parcela,'
              . 'valor_parcela,valor_parcela_normal,valor_total,valor_total_normal,ativo',
  ]);
  if (!$linhas) return null;

  return ofertaDoCiclo($linhas);
}

// ---------------------------------------------------------------- depoimentos
/**
 * Depoimentos de alunos (site_alunos_depoimentos), sempre amarrados ao curso.
 *
 * A coleção guarda o depoimento junto do id_curso, então o site nunca precisa
 * inventar elogio: a página do curso mostra quem fez aquele curso, e a home
 * mostra um de cada modalidade. Onde a coleção ainda não existe, as funções
 * devolvem lista vazia e a página cai no texto fixo dela.
 */

/** Nota em estrelas — o campo vem como texto livre ("5 estrelas"). */
function estrelasDepoimento($valor): int {
  return preg_match('/\d+/', (string) $valor, $m) ? max(1, min(5, (int) $m[0])) : 5;
}

/** Iniciais para o avatar redondo: "Ana Costa" => "AC". */
function iniciaisNome(string $nome): string {
  $partes = preg_split('/\s+/u', trim($nome)) ?: [];
  $partes = array_values(array_filter($partes, fn($p) => mb_strlen($p, 'UTF-8') > 2));
  if (!$partes) return '★';
  $primeira = mb_substr($partes[0], 0, 1, 'UTF-8');
  $ultima   = count($partes) > 1 ? mb_substr(end($partes), 0, 1, 'UTF-8') : '';
  return mb_strtoupper($primeira . $ultima, 'UTF-8');
}

/**
 * Nome como o site publica: primeiro nome inteiro e o sobrenome abreviado.
 * O depoimento é de aluno real, então a página não expõe o nome completo.
 */
function nomePublico(string $nome): string {
  $partes = preg_split('/\s+/u', trim($nome)) ?: [];
  $partes = array_values(array_filter($partes, fn($p) => $p !== ''));
  if (!$partes) return 'Aluno';
  if (count($partes) === 1) return $partes[0];
  return $partes[0] . ' ' . mb_strtoupper(mb_substr(end($partes), 0, 1, 'UTF-8'), 'UTF-8') . '.';
}

/** Uma linha da coleção no formato que a página exibe. */
function normalizarDepoimentos(array $linhas): array {
  $lista = [];
  foreach ($linhas as $l) {
    $texto = trim((string) ($l['depoimento'] ?? ''));
    $nome  = trim((string) ($l['nome'] ?? ''));
    if ($texto === '' || $nome === '') continue;
    $lista[] = [
      'nome'     => nomePublico($nome),
      'iniciais' => iniciaisNome($nome),
      'texto'    => $texto,
      'curso'    => trim((string) ($l['curso'] ?? '')),
      'idCurso'  => strtoupper(trim((string) ($l['id_curso'] ?? ''))),
      'estrelas' => estrelasDepoimento($l['satisfacao'] ?? ''),
    ];
  }
  return $lista;
}

/** Lê um cache de depoimentos; devolve null quando não existe ou está vencido. */
function cacheDepoimentos(string $arquivo, bool $aceitaVencido = false): ?array {
  if (!is_readable($arquivo)) return null;
  if (!$aceitaVencido && (time() - filemtime($arquivo) >= CACHE_TTL)) return null;
  $dados = json_decode((string) file_get_contents($arquivo), true);
  return is_array($dados) ? $dados : null;
}

/** Todos os depoimentos cadastrados de um curso (até 40), com cache em disco. */
function poolDepoimentosCurso(string $idCurso): array {
  $cache = caminhoCache('depo_' . preg_replace('/[^A-Za-z0-9]/', '', $idCurso));

  $pool = cacheDepoimentos($cache);
  if ($pool !== null) return $pool;

  $linhas = buscarColecao(COL_DEPOIMENTOS, [
    'fields' => 'nome,depoimento,curso,id_curso,satisfacao',
    'filter' => json_encode(['id_curso' => ['_eq' => $idCurso]]),
    'limit'  => 40,
  ]);

  // Coleção ausente ou fora do ar: usa o que já foi guardado, mesmo vencido.
  if ($linhas === null) return cacheDepoimentos($cache, true) ?? [];

  $pool = normalizarDepoimentos($linhas);
  @file_put_contents($cache, json_encode($pool, JSON_UNESCAPED_UNICODE));
  return $pool;
}

/**
 * Depoimentos deste curso, sorteados a cada visita.
 * O conjunto vem do cache; o sorteio acontece a cada carregamento da página,
 * então quem abre o site duas vezes não lê o mesmo depoimento.
 */
function depoimentosDoCurso(string $idCurso, int $limite = 1): array {
  if ($idCurso === '') return [];

  $pool = poolDepoimentosCurso($idCurso);
  if (!$pool) return [];

  shuffle($pool);
  return array_slice($pool, 0, max(1, $limite));
}

/**
 * Conjunto da vitrine da home, separado por modalidade e guardado em cache.
 *
 * Em vez de ler as 3 mil linhas, pega uma janela aleatória da coleção e fica
 * com o que for de curso que o site exibe. A janela muda quando o cache vence;
 * quem sorteia a cada visita é depoimentosDestaque().
 */
function poolDepoimentosHome(array $cursos): array {
  $cache = caminhoCache('depo_home');

  $pool = cacheDepoimentos($cache);
  if ($pool !== null) return $pool;

  $modalidadePorCurso = [];
  foreach ($cursos as $c) {
    $modalidadePorCurso[strtoupper((string) $c['id'])] = ['slug' => $c['categoria'], 'nome' => $c['nome']];
  }

  $ultimo = buscarColecao(COL_DEPOIMENTOS, ['fields' => 'id', 'sort' => '-id', 'limit' => 1]);
  if ($ultimo === null) return cacheDepoimentos($cache, true) ?? [];

  $maior  = (int) ($ultimo[0]['id'] ?? 0);
  $janela = 150;
  $inicio = $maior > $janela ? random_int(1, $maior - $janela) : 1;

  $linhas = buscarColecao(COL_DEPOIMENTOS, [
    'fields' => 'nome,depoimento,curso,id_curso,satisfacao',
    'filter' => json_encode(['id' => ['_gte' => $inicio]]),
    'sort'   => 'id',
    'limit'  => $janela,
  ]);
  if ($linhas === null) return cacheDepoimentos($cache, true) ?? [];

  $pool = ['eja' => [], 'tecnico' => [], 'livre' => []];
  foreach (normalizarDepoimentos($linhas) as $d) {
    $curso = $modalidadePorCurso[$d['idCurso']] ?? null;
    if (!$curso || !isset($pool[$curso['slug']])) continue;   // curso que o site não exibe
    if ($d['curso'] === '') $d['curso'] = $curso['nome'];
    $pool[$curso['slug']][] = $d;
  }

  @file_put_contents($cache, json_encode($pool, JSON_UNESCAPED_UNICODE));
  return $pool;
}

/**
 * Um depoimento por modalidade para a vitrine da home, sorteado a cada visita.
 * Modalidade sem depoimento na janela atual simplesmente não entra.
 */
function depoimentosDestaque(array $cursos): array {
  $pool  = poolDepoimentosHome($cursos);
  $saida = [];

  foreach (['eja', 'tecnico', 'livre'] as $slug) {
    $daModalidade = $pool[$slug] ?? [];
    if ($daModalidade) $saida[] = $daModalidade[array_rand($daModalidade)];
  }
  return $saida;
}

// ---------------------------------------------------------------- grade curricular
/** Nome reduzido ao essencial, para comparar catálogo e pacote sem tropeçar em acento. */
function chaveNome(string $nome): array {
  $n = mb_strtolower($nome, 'UTF-8');
  $n = strtr($n, [
    'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','ê'=>'e','è'=>'e',
    'í'=>'i','ì'=>'i','î'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ò'=>'o','ö'=>'o',
    'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c',
  ]);
  $n = preg_replace('/[^a-z0-9\s]/', ' ', $n);

  // Palavras que não distinguem um curso do outro.
  $ruido = ['curso','cursos','tecnico','tecnica','tecnologia','profissional',
            'online','ead','em','de','do','da','das','dos','e','o','a'];
  $palavras = array_diff(preg_split('/\s+/', trim($n)) ?: [], $ruido);
  sort($palavras);
  return array_values(array_filter($palavras, fn($p) => $p !== ''));
}

/**
 * O mesmo id_curso aponta para cursos diferentes nas duas tabelas em alguns
 * casos (CT008 é Meio Ambiente no catálogo e Estética no pacote). Antes de
 * mostrar a grade, confere se os nomes falam do mesmo curso — na dúvida, o site
 * prefere não mostrar matéria nenhuma a mostrar a do curso errado.
 */
function mesmoCurso(string $a, string $b): bool {
  $pa = chaveNome($a);
  $pb = chaveNome($b);
  if ($pa === [] || count($pa) !== count($pb)) return false;
  if ($pa === $pb) return true;

  // As duas tabelas escrevem o mesmo curso de jeitos um pouco diferentes
  // ("Design Gráfico" e "Designer Gráfico"): aceita quando uma palavra é o
  // começo da outra. Troca de letra no meio não vale — "eletromecanica" e
  // "eletrotecnica" são cursos diferentes.
  foreach ($pa as $i => $palavra) {
    if ($palavra === $pb[$i]) continue;
    $curta = strlen($palavra) < strlen($pb[$i]) ? $palavra : $pb[$i];
    $longa = strlen($palavra) < strlen($pb[$i]) ? $pb[$i] : $palavra;
    if (strlen($curta) < 5 || strncmp($curta, $longa, strlen($curta)) !== 0) return false;
  }
  return true;
}

/** Pacotes antigos gravaram a matéria toda em minúsculas ("telemarketing"). */
function nomeMateria(string $nome): string {
  if ($nome !== mb_strtolower($nome, 'UTF-8')) return $nome; // já veio escrito direito

  $titulo = mb_convert_case($nome, MB_CASE_TITLE, 'UTF-8');
  return preg_replace_callback(
    '/\b(De|Do|Da|Dos|Das|E|Em|A|O|Para|Com)\b/u',
    fn($m) => mb_strtolower($m[1], 'UTF-8'),
    $titulo
  );
}

/** Quantas linhas cada matéria tem numa coleção (id_materia => total). */
function contagemPorMateria(string $colecao): array {
  $linhas = buscarColecao($colecao, ['aggregate' => ['count' => 'id'], 'groupBy' => 'id_materia']);
  $mapa = [];
  foreach ($linhas ?? [] as $l) {
    $id = (string) ($l['id_materia'] ?? '');
    if ($id === '') continue;
    $mapa[$id] = (int) ($l['count']['id'] ?? 0);
  }
  return $mapa;
}

/**
 * Carga horária aproximada de uma matéria, a partir do conteúdo cadastrado.
 *
 * A conta é a do painel (site_configuracoes): cada questão vale uma hora e traz
 * uma vídeo-aula junto (mais uma), e a matéria ainda soma apostila, jornada e
 * podcast. Matéria sem conteúdo cadastrado ainda cai no padrão, para a página
 * não anunciar "0h" num curso que existe.
 */
function horasDaMateria(int $questoes, bool $apostila, bool $jornada): int {
  if ($questoes <= 0) return max(0, (int) config('carga_horaria_padrao', '30'));

  $horas = $questoes * ((int) config('carga_hora_questao', '1') + (int) config('carga_hora_videoaula', '1'));
  if ($apostila) $horas += (int) config('carga_hora_apostila', '1');
  if ($jornada)  $horas += (int) config('carga_hora_jornada', '1');
  return max(0, $horas + (int) config('carga_hora_podcast', '10'));
}

/** Grade de todos os cursos (id_curso => [nome_curso, materias]), com cache. */
function gradesPorCurso(): array {
  static $mapa = null;
  if ($mapa !== null) return $mapa;

  $cache = caminhoCache('materias');
  if (is_readable($cache) && (time() - filemtime($cache) < CACHE_TTL)) {
    $mapa = json_decode((string) file_get_contents($cache), true);
    if (is_array($mapa)) return $mapa;
  }

  $linhas = buscarColecao(COL_PACOTE, [
    'fields' => 'id_curso,nome_curso,nome_materia,ordem_materia,dias_materia,'
              . 'id_materia,pdf_apostila,pdf_jornada',
    'sort'   => 'id_curso,ordem_materia',
  ]);

  if ($linhas === null) {
    $mapa = is_readable($cache) ? json_decode((string) file_get_contents($cache), true) : [];
    return $mapa = is_array($mapa) ? $mapa : [];
  }

  // Conteúdo de cada matéria, para estimar a carga horária.
  $exercicios = contagemPorMateria(COL_EXERCICIOS);
  $provas     = contagemPorMateria(COL_PROVAS);
  $anexos     = [];
  foreach (buscarColecao(COL_ANEXOS, ['fields' => 'id_materia,arquivo_apostila,arquivo_jornada']) ?? [] as $a) {
    $id = (string) ($a['id_materia'] ?? '');
    if ($id !== '') $anexos[$id] = $a;
  }

  $mapa = [];
  foreach ($linhas as $l) {
    $id      = strtoupper(trim((string) ($l['id_curso'] ?? '')));
    $materia = trim((string) ($l['nome_materia'] ?? ''));
    if ($id === '' || $materia === '') continue;

    if (!isset($mapa[$id])) $mapa[$id] = ['nome' => trim((string) ($l['nome_curso'] ?? '')), 'materias' => []];

    // A mesma matéria aparece repetida quando o pacote foi remontado.
    foreach ($mapa[$id]['materias'] as $m) {
      if (mb_strtolower($m['nome'], 'UTF-8') === mb_strtolower($materia, 'UTF-8')) continue 2;
    }

    $uuid     = (string) ($l['id_materia'] ?? '');
    $questoes = ($exercicios[$uuid] ?? 0) + ($provas[$uuid] ?? 0);
    $anexo    = $anexos[$uuid] ?? [];

    $mapa[$id]['materias'][] = [
      'nome'  => nomeMateria($materia),
      'dias'  => (int) ($l['dias_materia'] ?? 0),
      'horas' => horasDaMateria(
        $questoes,
        !empty($l['pdf_apostila']) || !empty($anexo['arquivo_apostila']),
        !empty($l['pdf_jornada'])  || !empty($anexo['arquivo_jornada'])
      ),
    ];
  }

  @file_put_contents($cache, json_encode($mapa, JSON_UNESCAPED_UNICODE));
  return $mapa;
}

/**
 * Matérias do curso, na ordem em que o aluno estuda.
 * Vazio quando o pacote não tem grade ou quando o id aponta para outro curso.
 */
function materiasDoCurso(array $curso): array {
  $grades = gradesPorCurso();

  // Caminho normal: mesmo id nas duas tabelas, confirmado pelo nome.
  $grade = $grades[strtoupper($curso['id'])] ?? null;
  if ($grade && mesmoCurso($curso['nome'], $grade['nome'])) {
    return respeitarCargaMinima($grade['materias'], $curso);
  }

  // Pacotes antigos guardam outro id (numérico, ou trocado entre cursos):
  // aí vale o nome, que é o que o aluno vê.
  foreach ($grades as $g) {
    if (mesmoCurso($curso['nome'], $g['nome'])) {
      return respeitarCargaMinima($g['materias'], $curso);
    }
  }
  return [];
}

/** Carga mínima do curso: a da ficha ou, sem ela, o padrão da modalidade. */
function cargaMinima(array $curso): int {
  if (!empty($curso['cargaMinima'])) return (int) $curso['cargaMinima'];

  $padrao = [
    'tecnico' => (int) config('carga_minima_tecnico', '1200'),
    'eja'     => (int) config('carga_minima_eja', '1200'),
    'livre'   => (int) config('carga_minima_livre', '0'),
  ];
  return $padrao[$curso['categoria']] ?? 0;
}

/**
 * O curso técnico tem carga horária mínima definida no Catálogo Nacional de
 * Cursos Técnicos (Administração 800h, a maioria 1200h), e é essa a carga que a
 * escola certifica. O site anuncia sempre **um pouco acima** do mínimo: as horas
 * contadas do conteúdo dão o peso relativo de cada matéria, e a lista é ajustada
 * proporcionalmente até o total cair nesse alvo — para cima quando o conteúdo
 * cadastrado é pouco, para baixo quando é muito.
 *
 * Assim a proporção entre as matérias continua sendo a do conteúdo real, o total
 * bate com a soma da lista e nenhum curso anuncia carga diferente da que
 * certifica. Curso sem mínimo definido (os livres) mostra a soma real.
 */
function respeitarCargaMinima(array $materias, array $curso): array {
  $minima = cargaMinima($curso);
  if ($minima <= 0 || !$materias) return $materias;

  $margem = max(0, (float) config('carga_margem_percentual', '5'));
  $alvo   = (int) (ceil($minima * (1 + $margem / 100) / 10) * 10);

  $soma = array_sum(array_column($materias, 'horas'));
  if ($soma <= 0 || $soma === $alvo) return $materias;

  $fator = $alvo / $soma;
  foreach ($materias as $i => $m) {
    $materias[$i]['horas'] = max(1, (int) round($m['horas'] * $fator));
  }

  // O arredondamento sobra ou falta algumas horas: acerta na maior matéria,
  // para a soma bater com o total anunciado.
  $resto = $alvo - array_sum(array_column($materias, 'horas'));
  if ($resto !== 0) {
    $maior = array_keys(array_column($materias, 'horas'), max(array_column($materias, 'horas')))[0];
    $materias[$maior]['horas'] = max(1, $materias[$maior]['horas'] + $resto);
  }
  return $materias;
}

/** Um curso do catálogo pelo id_curso (ex.: CT005) ou pelo slug. */
function cursoPorId(string $chave): ?array {
  [$cursos] = catalogo();
  foreach ($cursos as $c) {
    if ($c['id'] === $chave) return $c;
  }
  foreach ($cursos as $c) {
    if ($c['slug'] !== '' && strcasecmp($c['slug'], $chave) === 0) return $c;
  }
  return null;
}
