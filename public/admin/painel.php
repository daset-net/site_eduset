<?php
// admin/painel.php — configurações gerais do site (site_configuracoes).

require __DIR__ . '/_auth.php';
require __DIR__ . '/_dados.php';
exigirLogin();

$aviso = '';
$tipo  = '';

// id da chave que guarda a imagem do hero (para o uploader e para pular no grid de texto)
function idConfig(string $chave): ?int {
  foreach (buscarColecao(COL_CONFIG, ['fields' => 'id,chave']) ?? [] as $r) {
    if (($r['chave'] ?? '') === $chave) return (int) $r['id'];
  }
  return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrfValido($_POST['csrf'] ?? null)) {
    $aviso = 'Sessão inválida. Recarregue a página e tente de novo.';
    $tipo  = 'erro';
  } elseif (($_POST['acao'] ?? '') === 'hero_imagem') {
    // Envio da imagem principal do topo da home.
    $idHero = idConfig('hero_imagem');
    [$ok, $r] = enviarImagem($_FILES['imagem'] ?? []);
    if ($ok && $idHero) {
      [$ok2, $msg2] = salvarItem(COL_CONFIG, $idHero, ['valor' => $r, 'valor_extendido' => '']);
      $aviso = $ok2 ? 'Imagem do topo atualizada! Já está no ar.' : $msg2;
      $tipo  = $ok2 ? 'ok' : 'erro';
    } else {
      $aviso = $ok ? 'Chave hero_imagem não encontrada.' : $r;
      $tipo  = 'erro';
    }
    limparCache();
  } elseif (($_POST['acao'] ?? '') === 'remover_hero') {
    $idHero = idConfig('hero_imagem');
    if ($idHero) salvarItem(COL_CONFIG, $idHero, ['valor' => '', 'valor_extendido' => '']);
    limparCache();
    $aviso = 'Imagem do topo removida. Voltou a mostrar a logo.';
    $tipo  = 'ok';
  } else {
    $valores = $_POST['valor'] ?? [];
    $erros = [];
    $n = 0;

    if (isset($_POST['valor_hero_formato'])) {
      [$ok, $msg] = salvarConfig('hero_formato', $_POST['valor_hero_formato'], 'Formato da máscara da imagem do topo (retangular ou circular)');
      if ($ok) $n++; else $erros[] = $msg;
    }

    foreach ($valores as $id => $valor) {
      $valor = trim((string) $valor);
      // Textos longos vão para valor_extendido, que tem precedência na leitura.
      $campos = mb_strlen($valor) > 200
        ? ['valor' => '', 'valor_extendido' => $valor]
        : ['valor' => $valor, 'valor_extendido' => ''];

      [$ok, $msg] = salvarItem(COL_CONFIG, (int) $id, $campos);
      if ($ok) { $n++; } else { $erros[] = $msg; }
    }

    limparCache();
    if ($erros) {
      $aviso = 'Algumas alterações não foram salvas: ' . implode(' ', array_unique($erros));
      $tipo  = 'erro';
    } else {
      $aviso = "Pronto! $n configurações salvas. O site já está mostrando as mudanças.";
      $tipo  = 'ok';
    }
  }
}

$configs = configuracoesDoPainel();

// Separa configurações especiais do hero
$heroValor = '';
$heroFormato = 'retangular';
foreach ($configs as $c) {
  if (($c['chave'] ?? '') === 'hero_imagem') { $heroValor = (string) ($c['valor'] ?? ''); }
  if (($c['chave'] ?? '') === 'hero_formato') { $heroFormato = (string) ($c['valor'] ?? 'retangular'); }
}
$heroUrl = preg_match('/^[0-9a-f-]{36}$/i', $heroValor) ? '../api/imagem.php?id=' . $heroValor . '&w=600' : '';

$titulo  = 'Configurações do site';
$abaAtiva = 'painel';
require __DIR__ . '/_topo.php';
?>

<?php if ($aviso): ?>
  <div class="aviso aviso--<?= e($tipo) ?>">
    <i class="ri-<?= $tipo === 'ok' ? 'check' : 'error-warning' ?>-line"></i> <?= e($aviso) ?>
  </div>
<?php endif; ?>

<!-- Imagem principal do topo -->
<section class="hero-editor">
  <div class="hero-editor__previa <?= $heroUrl === '' ? 'hero-editor__previa--vazio' : '' ?>">
    <?php if ($heroUrl !== ''): ?>
      <img src="<?= e($heroUrl) ?>" alt="Imagem do topo">
    <?php else: ?>
      <span><i class="ri-image-line"></i> Sem imagem — o topo mostra a logo</span>
    <?php endif; ?>
  </div>
  <div class="hero-editor__acoes">
    <h2>Imagem do topo (hero)</h2>
    <?php if ($heroFormato === 'retangular'): ?>
      <p>Aparece à direita no topo da home. Atualmente configurada com <strong>Cantos Arredondados (estilo card)</strong>. Você pode enviar uma arte digital (JPG/PNG) ou foto e ela vai ganhar bordas suaves e sombra elegante para se destacar. Sem imagem, mostra a logo.</p>
    <?php else: ?>
      <p>Aparece à direita no topo da home. Atualmente configurada como <strong>Recorte Transparente</strong>. O ideal é enviar um PNG com fundo transparente (um recorte de pessoa ou objeto) para ele flutuar sobre o azul do topo. Sem imagem, mostra a logo.</p>
    <?php endif; ?>
    <div class="hero-editor__botoes">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <input type="hidden" name="acao" value="hero_imagem">
        <label class="btn btn-primary">
          <i class="ri-upload-2-line"></i> <?= $heroUrl ? 'Trocar imagem' : 'Enviar imagem' ?>
          <input type="file" name="imagem" accept="image/*" onchange="this.form.submit()" hidden>
        </label>
      </form>
      <?php if ($heroUrl !== ''): ?>
        <form method="post" onsubmit="return confirm('Remover a imagem do topo?')">
          <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
          <input type="hidden" name="acao" value="remover_hero">
          <button type="submit" class="link-acao"><i class="ri-delete-bin-line"></i> Remover</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<form method="post" class="painel-form">
  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">

  <p class="painel-intro">
    Estes campos aparecem no site na hora em que você salva. Deixe em branco os links
    de rede social que você não usa — o ícone some do rodapé sozinho.
  </p>

  <div class="campos">
    <div class="campo">
      <label>Formato da Imagem do Topo (Hero)</label>
      <div class="radio-group" style="display: flex; gap: 2rem; margin-top: 0.5rem;">
        <label style="font-weight: normal; cursor: pointer;">
          <input type="radio" name="valor_hero_formato" value="retangular" <?= $heroFormato === 'retangular' ? 'checked' : '' ?>>
          Retangular (Cantos arredondados e esfumaçados)
        </label>
        <label style="font-weight: normal; cursor: pointer;">
          <input type="radio" name="valor_hero_formato" value="transparente" <?= $heroFormato === 'transparente' ? 'checked' : '' ?>>
          Recorte Transparente (Sem máscara)
        </label>
      </div>
      <small>Escolha o estilo visual para integrar a imagem ao fundo azul do site.</small>
    </div>

    <?php foreach ($configs as $c):
      if (in_array($c['chave'] ?? '', ['hero_imagem', 'hero_formato'])) continue; // tem uploader e controle próprios
      $valor = trim((string) ($c['valor_extendido'] ?? '')) !== ''
        ? $c['valor_extendido'] : ($c['valor'] ?? '');
      $longo = mb_strlen((string) $valor) > 80 || in_array($c['chave'], ['hero_subtitulo', 'seo_descricao'], true);
      $tabela = str_starts_with((string) $c['chave'], 'api_');
    ?>
      <div class="campo <?= $tabela ? 'campo--tecnico' : '' ?>">
        <label for="c<?= (int) $c['id'] ?>">
          <?= e(ucfirst(str_replace('_', ' ', $c['chave']))) ?>
          <?php if (!empty($c['descricao'])): ?>
            <small><?= e($c['descricao']) ?></small>
          <?php endif; ?>
        </label>
        <?php if ($longo): ?>
          <textarea id="c<?= (int) $c['id'] ?>" name="valor[<?= (int) $c['id'] ?>]" rows="3"><?= e((string) $valor) ?></textarea>
        <?php else: ?>
          <input id="c<?= (int) $c['id'] ?>" type="text" name="valor[<?= (int) $c['id'] ?>]" value="<?= e((string) $valor) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="barra-salvar">
    <button type="submit" class="btn btn-primary">Salvar alterações <i class="ri-save-line"></i></button>
  </div>
</form>

<?php require __DIR__ . '/_rodape.php'; ?>
