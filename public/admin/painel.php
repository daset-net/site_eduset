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

// Separa a imagem do hero: ela tem uploader próprio, não vai no grid de texto.
$heroValor = '';
foreach ($configs as $c) {
  if (($c['chave'] ?? '') === 'hero_imagem') { $heroValor = (string) ($c['valor'] ?? ''); }
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
    <p>Aparece à direita no topo da home. O ideal é um <strong>PNG com fundo
       transparente</strong> (um recorte, como a pessoa no notebook): ela flutua
       sobre o azul do topo e se integra sozinha, sem cortes. Sem imagem, o topo
       mostra a logo da EDUSET.</p>
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
    <?php foreach ($configs as $c):
      if (($c['chave'] ?? '') === 'hero_imagem') continue; // tem uploader próprio acima
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
