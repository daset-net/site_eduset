<?php
/**
 * admin/rastreamento.php — códigos de acompanhamento de anúncios.
 *
 * A escola cola o ID (ou o snippet inteiro, que dele se tira o ID) de cada
 * ferramenta, e o site passa a carregar o código em todas as páginas públicas.
 * O que monta os scripts é codigosRastreamento() em api/_catalogo.php; aqui fica
 * só a tela. Ferramenta fora da lista vai no campo livre, que entra como colado.
 */

require __DIR__ . '/_auth.php';
require __DIR__ . '/_dados.php';
exigirLogin();

$aviso = '';
$tipo  = 'ok';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrfValido($_POST['csrf'] ?? null)) {
    $aviso = 'Sessão inválida. Recarregue a página e tente de novo.';
    $tipo  = 'erro';
  } else {
    $erros = [];
    $invalidos = [];
    foreach (RASTREIO_FERRAMENTAS as $chave => [$nome]) {
      $bruto = trim((string) ($_POST[$chave] ?? ''));
      $id = rastreioId($chave, $bruto);
      if ($bruto !== '' && $id === '') { $invalidos[] = $nome; continue; }
      [$ok, $msg] = salvarConfig($chave, $id, 'ID de ' . $nome . ' (aba Rastreamento do painel)');
      if (!$ok) $erros[] = $msg;
    }
    // Conversão de matrícula do Google Ads: o rótulo "AW-.../xxxx" sai do snippet de evento colado.
    $convBruto = trim((string) ($_POST['rastreio_google_ads_conversao'] ?? ''));
    $conv = rastreioConversaoGoogle($convBruto);
    if ($convBruto !== '' && $conv === '') {
      $invalidos[] = 'Conversão de matrícula (Google Ads)';
    } else {
      [$ok, $msg] = salvarConfig('rastreio_google_ads_conversao', $conv, 'Rótulo de conversão de matrícula do Google Ads (aba Rastreamento do painel)');
      if (!$ok) $erros[] = $msg;
    }
    foreach (['rastreio_extra_head' => 'Código extra no <head>', 'rastreio_extra_body' => 'Código extra no início do <body>'] as $chave => $desc) {
      [$ok, $msg] = salvarConfig($chave, trim((string) ($_POST[$chave] ?? '')), $desc . ' (aba Rastreamento do painel)');
      if (!$ok) $erros[] = $msg;
    }
    limparCache();

    if ($erros) {
      $aviso = 'Algumas alterações não foram salvas: ' . implode(' ', array_unique($erros));
      $tipo  = 'erro';
    } elseif ($invalidos) {
      $aviso = 'Salvo, mas não reconheci o ID de: ' . implode(', ', $invalidos) . '. Confira o formato e salve de novo.';
      $tipo  = 'erro';
    } else {
      $aviso = 'Pronto! Os códigos já estão em todas as páginas do site.';
    }
  }
}

// Lê direto do Directus (e não do config(), que guarda a leitura desta mesma requisição).
$atual = [];
foreach (buscarColecao(COL_CONFIG, ['fields' => 'chave,valor,valor_extendido']) ?? [] as $c) {
  $chave = (string) ($c['chave'] ?? '');
  if (!str_starts_with($chave, 'rastreio_')) continue;
  $atual[$chave] = trim((string) ($c['valor_extendido'] ?? '')) !== '' ? $c['valor_extendido'] : (string) ($c['valor'] ?? '');
}
// Se o envio teve ID inválido, o campo volta com o que foi digitado, para corrigir.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($invalidos)) {
  foreach (RASTREIO_FERRAMENTAS as $chave => [$nome]) {
    if (in_array($nome, $invalidos, true)) $atual[$chave] = (string) ($_POST[$chave] ?? '');
  }
  if (in_array('Conversão de matrícula (Google Ads)', $invalidos, true)) $atual['rastreio_google_ads_conversao'] = (string) ($_POST['rastreio_google_ads_conversao'] ?? '');
}

$titulo   = 'Rastreamento';
$abaAtiva = 'rastreamento';
require __DIR__ . '/_topo.php';
?>

<?php if ($aviso): ?>
  <div class="aviso aviso--<?= e($tipo) ?>">
    <i class="ri-<?= $tipo === 'ok' ? 'check' : 'error-warning' ?>-line"></i> <?= e($aviso) ?>
  </div>
<?php endif; ?>

<form method="post" class="painel-form">
  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

  <p class="painel-intro">
    Códigos de acompanhamento das campanhas de anúncio. Cole o <strong>ID</strong> de cada ferramenta
    (ou o código inteiro que ela fornece — o painel tira o ID sozinho). Eles passam a valer em todas as
    páginas do site assim que você salvar. Deixe em branco o que a escola não usa.
  </p>

  <div class="campos">
    <?php foreach (RASTREIO_FERRAMENTAS as $chave => [$nome, , $exemplo]):
      $valor = $atual[$chave] ?? '';
      $ativo = rastreioId($chave, $valor) !== '';
    ?>
      <div class="campo">
        <label for="<?= e($chave) ?>">
          <?= e($nome) ?>
          <?php if ($ativo): ?><small style="color:#15803d"><i class="ri-checkbox-circle-fill"></i> ativo no site</small><?php endif; ?>
        </label>
        <input id="<?= e($chave) ?>" type="text" name="<?= e($chave) ?>" value="<?= e($valor) ?>" placeholder="Ex.: <?= e($exemplo) ?>" autocomplete="off" spellcheck="false">
      </div>
    <?php endforeach; ?>

    <?php $convAtual = $atual['rastreio_google_ads_conversao'] ?? ''; ?>
    <div class="campo">
      <label for="rastreio_google_ads_conversao">
        Conversão de matrícula (Google Ads)
        <?php if (rastreioConversaoGoogle($convAtual) !== ''): ?><small style="color:#15803d"><i class="ri-checkbox-circle-fill"></i> ativa no site</small><?php endif; ?>
        <small>Cole o "snippet de evento" da conversão (ou só o send_to, ex.: AW-123/AbC_xyz). Dispara quando a matrícula é confirmada no site, com o valor do curso. Meta, Pinterest e TikTok recebem o evento de cadastro sozinhos.</small>
      </label>
      <textarea id="rastreio_google_ads_conversao" name="rastreio_google_ads_conversao" rows="3" spellcheck="false" placeholder="AW-1234567890/AbCdEfGhIjK"><?= e($convAtual) ?></textarea>
    </div>

    <div class="campo">
      <label for="rastreio_extra_head">
        Outra ferramenta — código no &lt;head&gt;
        <small>Para o que não está na lista acima (Hotjar, Clarity, LinkedIn, Kwai…). Entra exatamente como colado: confira o código antes de salvar.</small>
      </label>
      <textarea id="rastreio_extra_head" name="rastreio_extra_head" rows="5" spellcheck="false"><?= e($atual['rastreio_extra_head'] ?? '') ?></textarea>
    </div>
    <div class="campo">
      <label for="rastreio_extra_body">
        Outra ferramenta — código no início do &lt;body&gt;
        <small>Só quando a ferramenta pede um trecho logo depois do &lt;body&gt; (normalmente um &lt;noscript&gt;).</small>
      </label>
      <textarea id="rastreio_extra_body" name="rastreio_extra_body" rows="3" spellcheck="false"><?= e($atual['rastreio_extra_body'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="barra-salvar">
    <button type="submit" class="btn btn-primary">Salvar códigos <i class="ri-save-line"></i></button>
  </div>
</form>

<?php require __DIR__ . '/_rodape.php'; ?>
