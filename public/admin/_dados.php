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
