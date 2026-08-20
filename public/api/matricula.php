<?php
// api/matricula.php — matrícula online do site direto no AVASET.
//
// O navegador manda só os dados do aluno. Curso, plano e valores são lidos aqui
// do ava_catalogo_curso (planoVigente), então adulterar o formulário não muda o
// que é gravado. Daqui sai um POST autenticado para o endpoint do AVASET:
//
//   POST {AVASET_MATRICULA_URL}   header X-Api-Key: {TOKEN_MATRICULA_EXTERNA}
//   → api/matricula_externa.php do avaset_unico, que grava o aluno no Directus
//     (financeiro=aguardando, acesso=BLOQUEADO, contrato=pendente).
//
// Configuração (EasyPanel → Environment):
//   TOKEN_MATRICULA_EXTERNA = <mesmo api_token_matricula_externa do AVASET>
//   AVASET_MATRICULA_URL    = https://ead.eduset.com.br/api/matricula_externa.php (padrão)

require __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

const MATRICULA_URL_PADRAO = 'https://ead.eduset.com.br/api/matricula_externa.php';
const LIMITE_POR_IP        = 5;    // matrículas
const JANELA_LIMITE        = 900;  // segundos (15 min)

function fim(int $status, array $corpo): void {
  http_response_code($status);
  echo json_encode($corpo, JSON_UNESCAPED_UNICODE);
  exit;
}

function so_digitos($v): string { return preg_replace('/\D/', '', (string) $v); }

/** Valida CPF pelos dígitos verificadores. */
function cpfValido(string $cpf): bool {
  if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
  for ($t = 9; $t < 11; $t++) {
    $soma = 0;
    for ($i = 0; $i < $t; $i++) $soma += (int) $cpf[$i] * (($t + 1) - $i);
    $digito = ((10 * $soma) % 11) % 10;
    if ((int) $cpf[$t] !== $digito) return false;
  }
  return true;
}

/** Converte DD/MM/AAAA (ou AAAA-MM-DD) num DateTime real; null se a data não existe. */
function dataNascimento(string $bruto): ?DateTime {
  $bruto = trim($bruto);
  foreach (['d/m/Y', 'Y-m-d'] as $formato) {
    $d = DateTime::createFromFormat($formato . '|', $bruto);
    if ($d && $d->format($formato) === $bruto) return $d;
  }
  return null;
}

/** Trava simples de repetição por IP — o endpoint é público e grava aluno. */
function excedeuLimite(): bool {
  $dir = __DIR__ . '/../../data';
  if (!is_dir($dir)) @mkdir($dir, 0775, true);
  $arquivo = $dir . '/matriculas_ip.json';

  $ip     = $_SERVER['REMOTE_ADDR'] ?? '';
  $agora  = time();
  $mapa   = json_decode((string) @file_get_contents($arquivo), true);
  if (!is_array($mapa)) $mapa = [];

  foreach ($mapa as $chave => $marcas) {
    $mapa[$chave] = array_values(array_filter($marcas, fn($t) => $agora - $t < JANELA_LIMITE));
    if (!$mapa[$chave]) unset($mapa[$chave]);
  }
  if (count($mapa[$ip] ?? []) >= LIMITE_POR_IP) return true;

  $mapa[$ip][] = $agora;
  @file_put_contents($arquivo, json_encode($mapa));
  return false;
}

// ---------------------------------------------------------------- entrada
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  fim(405, ['ok' => false, 'mensagem' => 'Método não permitido.']);
}

$token = conexao('TOKEN_MATRICULA_EXTERNA');
if ($token === '') {
  fim(503, ['ok' => false, 'mensagem' => 'Matrícula online indisponível no momento. Fale com um consultor pelo WhatsApp.']);
}

$dados = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($dados)) $dados = $_POST;

// Campo-armadilha: só robô preenche.
if (trim((string) ($dados['site'] ?? '')) !== '') {
  fim(422, ['ok' => false, 'mensagem' => 'Não foi possível concluir a matrícula.']);
}

// ---------------------------------------------------------------- curso e plano
$idCurso = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($dados['id_curso'] ?? '')));
$plano   = planoVigente($idCurso);
if (!$plano) {
  fim(422, ['ok' => false, 'mensagem' => 'Este curso não está disponível para matrícula online agora.']);
}

// ---------------------------------------------------------------- validação
$nome        = trim((string) ($dados['nome'] ?? ''));
$cpf         = so_digitos($dados['cpf'] ?? '');
$nascimento  = trim((string) ($dados['nascimento'] ?? ''));
$email       = trim((string) ($dados['email'] ?? ''));
$celular     = so_digitos($dados['celular'] ?? '');
$sexo        = strtoupper(substr(trim((string) ($dados['sexo'] ?? '')), 0, 1));
$cep         = so_digitos($dados['cep'] ?? '');
$endereco    = trim((string) ($dados['endereco'] ?? ''));
$numero      = trim((string) ($dados['numero'] ?? ''));
$complemento = trim((string) ($dados['complemento'] ?? ''));
$bairro      = trim((string) ($dados['bairro'] ?? ''));
$cidade      = trim((string) ($dados['cidade'] ?? ''));
$estado      = strtoupper(substr(trim((string) ($dados['estado'] ?? '')), 0, 2));

$nomeResp = trim((string) ($dados['nome_responsavel'] ?? ''));
$cpfResp  = so_digitos($dados['cpf_responsavel'] ?? '');

$erros = [];
if (mb_strlen($nome) < 5 || mb_strpos(trim($nome), ' ') === false) $erros[] = 'Informe seu nome completo.';
if (!cpfValido($cpf))                                    $erros[] = 'CPF inválido.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))          $erros[] = 'Informe um e-mail válido.';
if (strlen($celular) < 10 || strlen($celular) > 11)      $erros[] = 'Informe um WhatsApp válido com DDD.';
if (!in_array($sexo, ['M', 'F'], true))                  $erros[] = 'Selecione o sexo.';
if (strlen($cep) !== 8)                                  $erros[] = 'Informe um CEP válido.';
if ($endereco === '' || $numero === '' || $bairro === '' || $cidade === '') $erros[] = 'Complete o endereço.';
if (strlen($estado) !== 2)                               $erros[] = 'Informe o estado (UF).';

$nasc = dataNascimento($nascimento);
if (!$nasc) {
  $erros[] = 'Informe a data de nascimento no formato DD/MM/AAAA.';
} else {
  $idade = $nasc->diff(new DateTime('today'))->y;
  if ($idade < 14 || $idade > 100) $erros[] = 'Verifique a data de nascimento.';
  // Menor de idade só matricula com responsável financeiro identificado.
  if ($idade < 18 && (mb_strlen($nomeResp) < 5 || !cpfValido($cpfResp))) {
    $erros[] = 'Para menores de 18 anos, informe nome e CPF do responsável.';
  }
}
if ($cpfResp !== '' && !cpfValido($cpfResp)) $erros[] = 'CPF do responsável inválido.';

if ($erros) {
  fim(422, ['ok' => false, 'mensagem' => implode(' ', $erros)]);
}

if (excedeuLimite()) {
  fim(429, ['ok' => false, 'mensagem' => 'Muitas tentativas deste dispositivo. Aguarde alguns minutos ou fale com um consultor.']);
}

// ---------------------------------------------------------------- payload
$afiliado = trim((string) ($dados['afiliado_email'] ?? ''));
if (!filter_var($afiliado, FILTER_VALIDATE_EMAIL)) $afiliado = '';

// Polo do link de divulgação. O navegador manda, mas o cookie é do servidor;
// vale mais o que o PHP já leu do link/cookie desta visita. Quem valida se o
// polo existe e está ativo é o AVASET — aqui só conferimos o formato.
$polo = poloSlug();
if ($polo === '') {
  $polo = strtolower(trim((string) ($dados['polo'] ?? '')));
  if (!preg_match('/^[a-z0-9._-]{2,80}$/', $polo)) $polo = '';
}

$idUnico = trim((string) ($plano['codigo_unico_especial'] ?? ''));
if ($idUnico === '') $idUnico = (string) ($plano['id_unico'] ?? '');

$payload = [
  // aluno
  'nome'        => $nome,
  'cpf'         => $cpf,
  'nascimento'  => $nasc->format('d/m/Y'),
  'sexo'        => $sexo,
  'email'       => $email,
  'celular'     => $celular,
  'cep'         => $cep,
  'endereco'    => $endereco,
  'numero'      => $numero,
  'complemento' => $complemento,
  'bairro'      => $bairro,
  'cidade'      => $cidade,
  'estado'      => $estado,

  // curso e plano — sempre do catálogo, nunca do navegador
  'curso_nome'           => (string) ($plano['curso'] ?? ''),
  'curso_categoria'      => (string) ($plano['categoria'] ?? ''),
  'id_curso'             => (string) ($plano['id_curso'] ?? ''),
  'id_unico'             => $idUnico,
  'parcelamento'         => 'boleto',
  'parcela_quantidade'   => $plano['qtd_parcela'] ?? '',
  'desconto'             => $plano['desconto'] ?? '',
  'valor_parcela'        => $plano['valor_parcela'] ?? '',
  'valor_total'          => $plano['valor_total'] ?? '',
  'valor_parcela_normal' => $plano['valor_parcela_normal'] ?? '',
  'valor_total_normal'   => $plano['valor_total_normal'] ?? '',

  // responsável financeiro (vazio = o próprio aluno)
  'nome_responsavel' => $nomeResp,
  'cpf_responsavel'  => $cpfResp,

  // origem da venda
  'gestor_nome' => 'Site EDUSET',
];
if ($afiliado !== '') $payload['afiliado_email'] = $afiliado;
if ($polo !== '')     $payload['polo'] = $polo;

// ---------------------------------------------------------------- envio
$url = conexao('AVASET_MATRICULA_URL', MATRICULA_URL_PADRAO);

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_POST           => true,
  CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CONNECTTIMEOUT => 5,
  CURLOPT_TIMEOUT        => 20,
  CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-Api-Key: ' . $token],
]);
$corpo  = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$resposta = json_decode((string) $corpo, true);

if ($status !== 200 || !is_array($resposta) || empty($resposta['success'])) {
  $motivo = is_array($resposta) ? (string) ($resposta['error'] ?? '') : '';
  error_log('[matricula] falha status=' . $status . ' motivo=' . $motivo);

  // Erro de token/configuração é problema nosso: o visitante recebe um recado
  // genérico. Só repassa a mensagem do AVASET quando ela é sobre os dados.
  $nossoProblema = in_array($status, [401, 403, 503], true) || $motivo === '';
  fim(502, [
    'ok' => false,
    'mensagem' => $nossoProblema
      ? 'Não conseguimos concluir sua matrícula agora. Tente novamente em instantes ou fale com um consultor pelo WhatsApp.'
      : $motivo,
  ]);
}

// Credenciais do AVASET: usuário é o CPF e a senha inicial é a data de nascimento.
fim(200, [
  'ok'        => true,
  'matricula' => (string) ($resposta['id_geral'] ?? ''),
  'curso'     => $payload['curso_nome'],
  'usuario'   => $cpf,
  'senha'     => so_digitos($payload['nascimento']),
  'portal'    => config('url_ead', 'https://ead.eduset.com.br'),
  'mensagem'  => 'Matrícula registrada com sucesso!',
]);
