<?php
// api/avisos.php — balões de prova social ("Fulano de tal se matriculou em…").
//
// Lê a coleção de inscrições especiais no Directus (nome da tabela na chave
// site_alunos_inscricoes_especiais da site_configuracoes) e devolve as linhas
// prontas para o balão, junto com o texto e os tempos configurados no painel.
//
// Só entram inscrições cujo id_curso está ATIVO no catálogo — o balão nunca
// anuncia um curso que o visitante não consegue comprar.
//
// O texto é um modelo com marcadores, escrito na site_configuracoes:
//   {nome} {primeiro_nome} {curso} {cidade} {estado} {quando}
// *entre asteriscos* vira negrito.

require __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

const AVISOS_LIMITE = 60; // quantas inscrições entram no rodízio

/** Iniciais para o avatar quando o curso não tem capa nem emoji. */
function avisoIniciais(string $nome): string {
  $partes = preg_split('/\s+/u', trim($nome)) ?: [];
  $partes = array_values(array_filter($partes, fn($p) => mb_strlen($p, 'UTF-8') > 2));
  if (!$partes) return '🎓';
  $ini = mb_substr($partes[0], 0, 1, 'UTF-8');
  if (count($partes) > 1) $ini .= mb_substr(end($partes), 0, 1, 'UTF-8');
  return mb_strtoupper($ini, 'UTF-8');
}

/** Quantas linhas da coleção casam com o filtro (para sortear a janela). */
function avisosTotal(string $colecao, array $filtro): int {
  $r = buscarColecao($colecao, ['filter' => $filtro, 'aggregate' => ['count' => 'id']]);
  return (int) ($r[0]['count']['id'] ?? $r[0]['count'] ?? 0);
}

/** Inscrições prontas para o balão, com cache em disco. */
function avisosInscricoes(): array {
  $cache = caminhoCache('avisos');
  if (is_readable($cache) && (time() - filemtime($cache) < CACHE_TTL)) {
    $itens = json_decode((string) file_get_contents($cache), true);
    if (is_array($itens)) return $itens;
  }

  // Catálogo do site já vem sem os cursos desativados no AVASET: quem está aqui
  // está à venda. O id_curso da inscrição é a chave para casar os dois.
  [$cursos] = catalogo();
  $porId = [];
  foreach ($cursos as $c) $porId[strtoupper($c['id'])] = $c;
  if (!$porId) return [];

  $colecao = config('site_alunos_inscricoes_especiais', 'site_alunos_inscricoes_especiais');
  $filtro  = ['id_curso' => ['_in' => array_keys($porId)]];

  // A tabela tem milhares de linhas: em vez de mostrar sempre as mesmas, sorteia
  // uma janela diferente a cada vez que o cache vence.
  $total  = avisosTotal($colecao, $filtro);
  $offset = $total > AVISOS_LIMITE ? random_int(0, $total - AVISOS_LIMITE) : 0;

  $linhas = buscarColecao($colecao, [
    'fields' => 'nome,curso,id_curso,cidade,estado,visto_por_ultimo',
    'filter' => $filtro,
    'limit'  => AVISOS_LIMITE,
    'offset' => $offset,
  ]);

  if ($linhas === null) {
    // Directus fora do ar: serve a última lista conhecida, mesmo vencida.
    $itens = is_readable($cache) ? json_decode((string) file_get_contents($cache), true) : [];
    return is_array($itens) ? $itens : [];
  }

  $itens = [];
  foreach ($linhas as $l) {
    $nome = trim((string) ($l['nome'] ?? ''));
    $id   = strtoupper(trim((string) ($l['id_curso'] ?? '')));
    if ($nome === '' || !isset($porId[$id])) continue;

    $c = $porId[$id];
    // O nome escrito na inscrição costuma ser mais específico (combos de EJA +
    // técnico); sem ele, cai no nome do catálogo.
    $curso = trim((string) ($l['curso'] ?? '')) !== '' ? trim((string) $l['curso']) : $c['nome'];

    $itens[] = [
      'nome'         => $nome,
      'primeiroNome' => preg_split('/\s+/u', $nome)[0] ?? $nome,
      'curso'        => $curso,
      'cidade'       => trim((string) ($l['cidade'] ?? '')),
      'estado'       => trim((string) ($l['estado'] ?? '')),
      // Tempo como está escrito na coluna visto_por_ultimo ("20 minutos atrás").
      'quando'       => trim((string) ($l['visto_por_ultimo'] ?? '')),
      'iniciais'     => avisoIniciais($nome),
      'imagem'       => $c['imagem'],
      'emoji'        => $c['emoji'],
      'link'         => 'curso.php?id=' . rawurlencode($c['slug'] !== '' ? $c['slug'] : $c['id']),
    ];
  }

  @file_put_contents($cache, json_encode($itens, JSON_UNESCAPED_UNICODE));
  return $itens;
}

$ativo = strtolower(config('aviso_ativo', 'sim'));
$ativo = !in_array($ativo, ['nao', 'não', 'no', '0', 'off', 'false'], true);

$itens = $ativo ? avisosInscricoes() : [];

echo json_encode([
  'ok'    => $itens !== [],
  'ativo' => $ativo && $itens !== [],
  'config' => [
    'texto'     => config('aviso_texto', '*{nome}*, de {cidade} - {estado}, se matriculou no curso *{curso}*'),
    'rodape'    => config('aviso_rodape', '{quando} · matrícula confirmada'),
    'posicao'   => strtolower(config('aviso_posicao', 'esquerda')) === 'direita' ? 'direita' : 'esquerda',
    'primeiro'  => max(1, (int) config('aviso_primeiro_segundos', '8')),
    'intervalo' => max(3, (int) config('aviso_intervalo_segundos', '25')),
    'duracao'   => max(2, (int) config('aviso_duracao_segundos', '7')),
  ],
  'itens' => $itens,
], JSON_UNESCAPED_UNICODE);
