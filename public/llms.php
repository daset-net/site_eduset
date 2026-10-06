<?php
// llms.php — servido como /llms.txt (ver .htaccess).
//
// Resumo do site para assistentes de IA (ChatGPT, Claude, Gemini, Perplexity),
// no formato do llmstxt.org: quem é a escola, o que ela oferece e onde estão as
// páginas. Montado na hora com os dados do Directus — descrição, contatos e o
// catálogo que o próprio site exibe —, então não fica desatualizado.
require __DIR__ . '/api/_catalogo.php';

/** Texto limpo numa linha só (o Markdown do llms.txt não aceita quebra no meio de item). */
function linha(string $s): string { return trim(preg_replace('/\s+/', ' ', strip_tags($s))); }

[$cursos] = catalogo();

// Cursos agrupados pela modalidade, na ordem em que o catálogo do site mostra.
$porModalidade = [];
$vistos = [];
foreach ($cursos as $c) {
  $chave = $c['slug'] !== '' ? $c['slug'] : $c['id'];
  if ($chave === '' || isset($vistos[$chave])) continue;
  $vistos[$chave] = true;
  $porModalidade[$c['categoriaLabel'] ?: 'Outros cursos'][] = $c + ['chave' => $chave];
}

$unidades = unidadesListadas();
$estados  = array_values(array_unique(array_filter(array_map(fn($u) => $u['uf'] ?? '', $unidades), fn($uf) => $uf !== '' && $uf !== '--')));
sort($estados);

$descricao = linha(config('seo_descricao', ''));
$missao    = linha(config('institucional_missao', ''));
$horario   = linha(config('horario_atendimento', ''));
$whats     = preg_replace('/\D/', '', config('whatsapp', ''));
$email     = linha(config('email_contato', ''));
$telefone  = linha(config('telefone_exibicao', ''));
$razao     = linha(config('empresa_razao_social', ''));
$cnpj      = linha(config('empresa_cnpj', ''));
$endereco  = linha(config('empresa_endereco', ''));
$ead       = linha(config('url_ead', ''));

$out = [];
$out[] = '# ' . SITE_NOME;
$out[] = '';
if ($descricao !== '') $out[] = '> ' . $descricao;
$out[] = '';
$out[] = SITE_NOME . ' é uma instituição de ensino a distância (EAD) com cursos 100% online: '
       . 'Supletivo EJA (Ensino Fundamental e Médio para jovens e adultos), Cursos Técnicos, '
       . 'Técnico por Competência e Cursos Profissionalizantes, com certificação reconhecida e validade nacional. '
       . 'O aluno estuda no próprio ritmo pela plataforma online e conta com unidades parceiras para atendimento'
       . ($estados ? ' em ' . count($estados) . ' estados (' . implode(', ', $estados) . ')' : '') . '. '
       . 'A matrícula é feita pelo site, na página de cada curso.';
if ($missao !== '') { $out[] = ''; $out[] = 'Missão: ' . $missao; }
$out[] = '';

$out[] = '## Páginas principais';
$out[] = '';
$out[] = '- [Página inicial](' . urlAbsoluta('/') . '): modalidades, catálogo de cursos e contato';
$out[] = '- [Cursos Profissionalizantes](' . urlAbsoluta('/profissionalizantes') . '): cursos de formação inicial e continuada (FIC)';
$out[] = '- [Unidades](' . urlAbsoluta('/unidades.php') . '): cidades com unidade parceira para atendimento e matrícula';
$out[] = '- [Seja uma unidade](' . urlAbsoluta('/seja-uma-unidade.php') . '): como abrir uma unidade parceira';
$out[] = '- [Programa de afiliados](' . urlAbsoluta('/afiliados.php') . '): indique cursos e receba comissão';
if ($ead !== '') $out[] = '- [Ambiente do aluno](' . $ead . '): plataforma de estudos para quem já é aluno';
$out[] = '';

foreach ($porModalidade as $modalidade => $lista) {
  $out[] = '## ' . linha($modalidade);
  $out[] = '';
  foreach ($lista as $c) {
    $extra = array_filter([rtrim(linha($c['descricao'] ?? ''), '.'), linha($c['duracao'] ?? '') !== '' ? 'Duração: ' . linha($c['duracao']) : '']);
    $out[] = '- [' . linha($c['nome']) . '](' . urlAbsoluta('/curso.php?id=' . $c['chave']) . ')'
           . ($extra ? ': ' . implode('. ', $extra) : '');
  }
  $out[] = '';
}

$out[] = '## Contato';
$out[] = '';
if ($whats !== '') $out[] = '- WhatsApp: https://wa.me/' . $whats;
if ($telefone !== '') $out[] = '- Telefone: ' . $telefone;
if ($email !== '') $out[] = '- E-mail: ' . $email;
if ($horario !== '') $out[] = '- Atendimento: ' . $horario;
if ($razao !== '') $out[] = '- Razão social: ' . $razao . ($cnpj !== '' ? ' (CNPJ ' . $cnpj . ')' : '');
if ($endereco !== '') $out[] = '- Endereço: ' . $endereco;
$out[] = '';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');
echo implode("\n", $out);
