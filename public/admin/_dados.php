<?php
// admin/_dados.php — operações de escrita no Directus, usadas só pelo painel.
// Ficam separadas da leitura pública (api/_catalogo.php) de propósito.

require_once __DIR__ . '/../api/_catalogo.php';

/** PATCH num item. Devolve [ok, mensagem]. */
function salvarItem(string $colecao, $id, array $campos): array {
  $base  = directusBase();
  $token = conexao('DIRECTUS_TOKEN');
  if ($base === '' || $token === '') return [false, 'Configuração do Directus ausente.'];

  $ch = curl_init($base . '/items/' . $colecao . '/' . rawurlencode((string) $id));
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'PATCH',
    CURLOPT_POSTFIELDS     => json_encode($campos, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer ' . $token,
      'Content-Type: application/json; charset=utf-8',
    ],
  ]);
  $corpo  = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($status >= 200 && $status < 300) return [true, ''];

  $erro = json_decode((string) $corpo, true)['errors'][0]['message'] ?? 'Falha ao salvar.';
  return [false, $erro];
}

/** POST de um item novo. Devolve [ok, mensagem]. */
function criarItem(string $colecao, array $campos): array {
  $base  = directusBase();
  $token = conexao('DIRECTUS_TOKEN');
  if ($base === '' || $token === '') return [false, 'Configuração do Directus ausente.'];

  $ch = curl_init($base . '/items/' . $colecao);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($campos, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer ' . $token,
      'Content-Type: application/json; charset=utf-8',
    ],
  ]);
  $corpo  = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($status >= 200 && $status < 300) return [true, ''];

  $erro = json_decode((string) $corpo, true)['errors'][0]['message'] ?? 'Falha ao criar.';
  return [false, $erro];
}

/**
 * Grava uma chave da site_configuracoes, criando a linha se ela ainda não
 * existir — assim uma chave nova (como a das campanhas) entra sozinha em cada
 * escola, sem ninguém precisar mexer no Directus tenant por tenant.
 *
 * Texto longo vai para valor_extendido, que é o campo que a leitura prioriza.
 */
function salvarConfig(string $chave, string $valor, string $descricao = ''): array {
  $campos = mb_strlen($valor) > 200
    ? ['valor' => '', 'valor_extendido' => $valor]
    : ['valor' => $valor, 'valor_extendido' => ''];

  foreach (buscarColecao(COL_CONFIG, ['fields' => 'id,chave']) ?? [] as $r) {
    if (($r['chave'] ?? '') === $chave) return salvarItem(COL_CONFIG, (int) $r['id'], $campos);
  }

  $campos['chave'] = $chave;
  if ($descricao !== '') $campos['descricao'] = $descricao;
  return criarItem(COL_CONFIG, $campos);
}

// ------------------------------------------------------- campanhas de desconto
const CHAVE_CAMPANHAS = 'oferta_campanhas';

/**
 * Estado das campanhas lido direto do Directus, sem passar pelo cache da leitura
 * pública: logo depois de salvar, o painel tem de mostrar o que acabou de gravar.
 *
 * Linhas com data inválida são descartadas na leitura — o painel nunca deve
 * exibir uma campanha que o site não conseguiria aplicar.
 */
function campanhasAtuais(): array {
  $vazio = ['permanente' => 0, 'programadas' => []];

  foreach (buscarColecao(COL_CONFIG, ['fields' => 'chave,valor,valor_extendido']) ?? [] as $r) {
    if (($r['chave'] ?? '') !== CHAVE_CAMPANHAS) continue;

    $bruto = trim((string) ($r['valor_extendido'] ?? '')) !== ''
      ? $r['valor_extendido']
      : ($r['valor'] ?? '');
    $dados = json_decode((string) $bruto, true);
    if (!is_array($dados)) return $vazio;

    $programadas = [];
    foreach ($dados['programadas'] ?? [] as $c) {
      if (janelaCampanha((array) $c)) $programadas[] = $c;
    }

    return [
      'permanente'  => (int) ($dados['permanente'] ?? 0),
      'programadas' => ordenarPorInicio($programadas),
    ];
  }

  return $vazio;
}

function ordenarPorInicio(array $programadas): array {
  usort($programadas, fn($a, $b) => strcmp((string) ($a['inicio'] ?? ''), (string) ($b['inicio'] ?? '')));
  return array_values($programadas);
}

function gravarCampanhas(array $estado): array {
  return salvarConfig(
    CHAVE_CAMPANHAS,
    json_encode($estado, JSON_UNESCAPED_UNICODE),
    'Campanha de desconto do site: permanente ou programada por datas (painel > Campanhas).'
  );
}

/**
 * Campanha já programada que cruza com a nova, se houver.
 *
 * Dois períodos sobrepostos deixariam dois descontos válidos no mesmo dia, e a
 * vitrine escolheria o primeiro da lista — resultado que ninguém adivinha
 * olhando a tela. Melhor recusar na hora de salvar.
 */
function conflita(array $nova, array $existentes): ?array {
  $janelaNova = janelaCampanha($nova);
  if (!$janelaNova) return null;

  foreach ($existentes as $c) {
    $janela = janelaCampanha($c);
    if (!$janela) continue;
    if ($janelaNova[0] <= $janela[1] && $janela[0] <= $janelaNova[1]) return $c;
  }
  return null;
}

/**
 * Faixas de desconto que a escola pode anunciar, da maior para a menor.
 *
 * Bolsa fica fora: é concessão da escola caso a caso, a matrícula externa
 * recusa 60% ou mais, e anunciar isso seria prometer o que não se entrega
 * (mesma régua de ehBolsa, usada na vitrine).
 */
function faixasDeDesconto(): array {
  $linhas = buscarColecao(COL_PRECOS, ['fields' => 'ingresso,desconto,ativo']) ?? [];

  $faixas = [];
  foreach ($linhas as $l) {
    if (($l['ativo'] ?? true) === false) continue;
    if (ehBolsa($l)) continue;
    $d = (int) ($l['desconto'] ?? 0);
    if ($d > 0) $faixas[$d] = true;
  }

  $faixas = array_keys($faixas);
  rsort($faixas);
  return $faixas;
}

/**
 * Envia um arquivo enviado pelo formulário para o Directus.
 * @return array{0: bool, 1: string} [ok, uuid ou mensagem de erro]
 */
function enviarImagem(array $arquivo): array {
  if (($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
    return [false, 'Nenhum arquivo enviado.'];
  }
  if ($arquivo['error'] !== UPLOAD_ERR_OK) {
    return [false, 'Falha no envio (o arquivo pode ser maior que o limite do servidor).'];
  }
  if ($arquivo['size'] > 8 * 1024 * 1024) {
    return [false, 'A imagem passa de 8 MB. Reduza antes de enviar.'];
  }

  // Confia no conteúdo, não na extensão.
  $tipo = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
  $permitidos = [
    'image/jpeg' => 'jpg', 'image/png' => 'png',
    'image/webp' => 'webp', 'image/gif' => 'gif',
  ];
  if (!isset($permitidos[$tipo])) {
    return [false, 'Formato não aceito. Use JPG, PNG, WEBP ou GIF.'];
  }

  $base  = directusBase();
  $token = conexao('DIRECTUS_TOKEN');
  if ($base === '' || $token === '') return [false, 'Configuração do Directus ausente.'];

  $nome = preg_replace('/[^A-Za-z0-9._-]/', '_', (string) ($arquivo['name'] ?? 'capa'));
  $nome = pathinfo($nome, PATHINFO_FILENAME) . '.' . $permitidos[$tipo];

  // Se DIRECTUS_STORAGE estiver definido (ex.: "site"), o Directus grava o
  // arquivo nessa localização — usada para separar as imagens do site numa
  // pasta própria no R2. Vazio = localização padrão do Directus (nada muda).
  // O campo "storage" precisa vir ANTES do "file" no multipart.
  $post = [];
  $storage = conexao('DIRECTUS_STORAGE');
  if ($storage !== '') $post['storage'] = $storage;
  $post['file'] = new CURLFile($arquivo['tmp_name'], $tipo, $nome);

  $ch = curl_init($base . '/files');
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => 60,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token],
    CURLOPT_POSTFIELDS     => $post,
  ]);
  $corpo  = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($status < 200 || $status >= 300) {
    $erro = json_decode((string) $corpo, true)['errors'][0]['message'] ?? 'Falha ao enviar a imagem.';
    return [false, $erro];
  }

  $uuid = json_decode((string) $corpo, true)['data']['id'] ?? '';
  return $uuid !== '' ? [true, $uuid] : [false, 'O Directus não devolveu o arquivo.'];
}

/** Cursos do painel: junta a linha editorial com o nome que vem do catálogo de preços. */
function cursosDoPainel(): array {
  $editorial = buscarColecao(COL_CURSOS, ['fields' => '*', 'sort' => 'ordem']) ?? [];
  $precos    = buscarColecao(COL_PRECOS, ['fields' => 'id_curso,curso,categoria']) ?? [];

  $nomes = [];
  foreach ($precos as $p) {
    if (!empty($p['id_curso']) && !isset($nomes[$p['id_curso']])) {
      $nomes[$p['id_curso']] = ['curso' => $p['curso'] ?? '', 'categoria' => $p['categoria'] ?? ''];
    }
  }

  foreach ($editorial as &$e) {
    $id = $e['id_curso'] ?? '';
    $e['_nome_catalogo'] = $nomes[$id]['curso'] ?? '(não está no catálogo de preços)';
    $e['_categoria']     = $nomes[$id]['categoria'] ?? '';
    $e['_no_catalogo']   = isset($nomes[$id]);
  }
  unset($e);

  return $editorial;
}

/** Configurações do painel, na ordem em que ficam melhor de editar. */
function configuracoesDoPainel(): array {
  $linhas = buscarColecao(COL_CONFIG, ['fields' => 'id,chave,valor,valor_extendido,descricao']) ?? [];

  $ordem = [
    'whatsapp', 'telefone_exibicao', 'email_contato', 'horario_atendimento',
    'instagram', 'facebook', 'youtube',
    'hero_badge', 'hero_titulo', 'hero_subtitulo',
    'stat_alunos', 'stat_cursos', 'stat_satisfacao',
    'seo_titulo', 'seo_descricao',
  ];
  $peso = array_flip($ordem);

  usort($linhas, function ($a, $b) use ($peso) {
    $pa = $peso[$a['chave']] ?? 999;
    $pb = $peso[$b['chave']] ?? 999;
    return $pa <=> $pb ?: strcmp($a['chave'], $b['chave']);
  });

  return $linhas;
}
