<?php
// api/contato.php — recebe o formulário de contato/matrícula
//
// O lead vai para a coleção `site_leads` do Directus da escola, que é de onde
// as duas pontas que cuidam dele leem:
//
//   - o gestor, no GESET (Leads), marca "já falei com essa pessoa";
//   - o agente de IA, pelo ChatSET, chama no WhatsApp quem ninguém marcou.
//
// O CSV em /data continua sendo escrito, e por um motivo só: se o Directus
// estiver fora do ar na hora, o lead não pode sumir. Ele é rastro bruto, não é
// para ser lido — quem lê lead é o painel.
//
// ATENÇÃO: esse rastro só sobrevive ao deploy se houver um volume montado em
// /var/www/vhosts/localhost/data. O Dockerfile não declara VOLUME e o README
// trata o mount como opcional, então a pasta pode estar na camada gravável do
// contêiner — e ali ela é apagada a cada deploy, sem aviso. Antes de contar com
// este arquivo para recuperar alguma coisa, confira em EasyPanel → Mounts.
require_once __DIR__ . '/_catalogo.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'mensagem' => 'Método não permitido.'], JSON_UNESCAPED_UNICODE);
  exit;
}

const COL_LEADS = 'site_leads';

/**
 * O telefone só com dígitos, com o DDI na frente e com o nono dígito no lugar.
 *
 * É por este campo que o ChatSET acha a conversa no WhatsApp e, quando ela não
 * existe, é para ele que a mensagem é enviada. "(12) 99764-0917" e
 * "12997640917" são a mesma pessoa; comparar o que foi digitado erraria sempre,
 * porque a máscara do formulário não é a do WhatsApp.
 *
 * O nono dígito é acrescentado aqui, e não na hora de enviar, porque o celular
 * escrito com oito dígitos é um número que o WhatsApp não entrega: a mensagem
 * sairia do sistema e não chegaria a ninguém, sem erro nenhum aparecer.
 */
function telefoneDigitos(string $bruto): string {
  $d = preg_replace('/\D/', '', $bruto);
  if ($d === '') return '';
  // 10 (fixo) ou 11 (celular) dígitos é número brasileiro sem DDI
  if (strlen($d) === 10 || strlen($d) === 11) $d = '55' . $d;

  // Celular brasileiro escrito sem o nono dígito: 55 + DDD + 8 dígitos
  // começando em 6 a 9. Fixo (2 a 5) fica como está — ele não é celular
  // encurtado, é outro número.
  if (strpos($d, '55') === 0 && strlen($d) === 12 && $d[4] >= '6') {
    $d = substr($d, 0, 4) . '9' . substr($d, 4);
  }
  return $d;
}

/** Grava o lead no Directus. Devolve o id criado, ou null se não deu. */
function gravarLead(array $lead): ?int {
  $base  = directusBase();
  $token = conexao('DIRECTUS_TOKEN');
  if ($base === '' || $token === '') return null;

  $ch = curl_init($base . '/items/' . COL_LEADS);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_TIMEOUT        => HTTP_TIMEOUT,
    CURLOPT_HTTPHEADER     => [
      'Authorization: Bearer ' . $token,
      'Content-Type: application/json',
    ],
    // Sem JSON_UNESCAPED_UNICODE: o acento escapado (ç) chega inteiro
    // ao banco em qualquer configuração de charset do Directus.
    CURLOPT_POSTFIELDS     => json_encode($lead),
  ]);
  $corpo  = curl_exec($ch);
  $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($corpo === false || $status < 200 || $status >= 300) return null;
  $json = json_decode($corpo, true);
  return isset($json['data']['id']) ? (int) $json['data']['id'] : null;
}

// Lê o corpo (JSON ou formulário tradicional)
$raw = file_get_contents('php://input');
$dados = json_decode($raw, true);
if (!is_array($dados)) { $dados = $_POST; }

$nome      = trim($dados['nome']      ?? '');
$email     = trim($dados['email']     ?? '');
$telefone  = trim($dados['telefone']  ?? '');
$interesse = trim($dados['interesse'] ?? '');
$mensagem  = trim($dados['mensagem']  ?? '');
$tipoCandidatura = trim((string)($dados['tipo_candidatura'] ?? ''));
$campos = is_array($dados['campos'] ?? null) ? $dados['campos'] : [];

// Validação
$erros = [];
if (mb_strlen($nome) < 3)                           $erros[] = 'Informe seu nome completo.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $erros[] = 'Informe um e-mail válido.';
if (mb_strlen(preg_replace('/\D/', '', $telefone)) < 10) $erros[] = 'Informe um telefone válido.';
if ($interesse === '')                              $erros[] = 'Selecione uma modalidade.';
if ($tipoCandidatura === 'unidade') {
  $obrigatorios = [
    'cpf' => 'CPF', 'data_nascimento' => 'data de nascimento',
    'nome_empresa' => 'nome da empresa', 'cnpj' => 'CNPJ',
    'cep' => 'CEP', 'endereco' => 'endereço', 'bairro' => 'bairro',
    'cidade' => 'cidade', 'estado' => 'estado',
    'unidade_nome' => 'nome institucional do polo',
    'unidade_identificacao' => 'e-mail institucional do polo',
    'pix_tipo' => 'tipo de chave PIX', 'pix_chave' => 'chave PIX',
    'banco_codigo' => 'código do banco', 'banco_nome' => 'nome do banco',
    'espaco' => 'situação do espaço físico',
    'experiencia_educacional' => 'experiência educacional',
    'experiencia' => 'experiência e estrutura',
  ];
  foreach ($obrigatorios as $campo => $rotulo) {
    if (trim((string)($campos[$campo] ?? '')) === '') $erros[] = 'Informe ' . $rotulo . '.';
  }
  if (strlen(preg_replace('/\D/', '', (string)($campos['cpf'] ?? ''))) !== 11) $erros[] = 'Informe um CPF válido.';
  if (strlen(preg_replace('/\D/', '', (string)($campos['cnpj'] ?? ''))) !== 14) $erros[] = 'Informe um CNPJ válido.';
  if (empty($campos['consentimento'])) $erros[] = 'Confirme a autorização para tratamento dos dados.';
}

if ($erros) {
  http_response_code(422);
  echo json_encode(['ok' => false, 'mensagem' => implode(' ', $erros)], JSON_UNESCAPED_UNICODE);
  exit;
}

// Corta o que for absurdamente grande antes de sair da máquina: o campo é
// aberto na internet, e uma mensagem de 200 KB não é dúvida sobre curso.
$mensagem = mb_substr($mensagem, 0, 2000);
$nome     = mb_substr($nome, 0, 120);

$idLead = gravarLead([
  'situacao'         => 'novo',   // ninguém falou com essa pessoa ainda
  'nome'             => $nome,
  'email'            => $email,
  'telefone'         => $telefone,
  'telefone_digitos' => telefoneDigitos($telefone),
  'interesse'        => $interesse,
  'mensagem'         => $mensagem,
  'origem'           => 'site',
  'criado_em'        => date('c'),
  'ip'               => $_SERVER['REMOTE_ADDR'] ?? '',
]);

// Rede de segurança: o Directus fora do ar não pode custar um lead.
$linha = [
  'data'      => date('Y-m-d H:i:s'),
  'nome'      => $nome,
  'email'     => $email,
  'telefone'  => $telefone,
  'interesse' => $interesse,
  'mensagem'  => $mensagem,
  'ip'        => $_SERVER['REMOTE_ADDR'] ?? '',
  'directus'  => $idLead ?: 'FALHOU',
];

$dir = __DIR__ . '/../../data';
if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
$arquivo = $dir . '/leads.csv';

$novo = !file_exists($arquivo);
if ($fp = @fopen($arquivo, 'a')) {
  if ($novo) { fputcsv($fp, array_keys($linha)); }
  fputcsv($fp, $linha);
  fclose($fp);
}

// O lead ficou só no arquivo: ninguém vai vê-lo no painel nem receber a
// ligação do agente. É falha de operação, e precisa aparecer no log do
// container — para o visitante a resposta é a mesma, porque o problema não é
// dele e ele não tem o que fazer com essa informação.
if ($idLead === null) {
  error_log('contato.php: lead de ' . $email . ' não entrou no Directus; ficou só em data/leads.csv');
}

// (Opcional) Enviar e-mail — descomente e configure o destino em produção
// @mail('contato@eduset.com.br', 'Novo lead: '.$interesse,
//   "Nome: $nome\nE-mail: $email\nTelefone: $telefone\nMensagem: $mensagem",
//   "From: no-reply@eduset.com.br\r\n");

echo json_encode([
  'ok' => true,
  'mensagem' => "Obrigado, $nome! Recebemos seu contato e retornaremos em breve. 🎓"
], JSON_UNESCAPED_UNICODE);
