<?php
// admin/cursos.php — lista dos cursos do site, com envio rápido da capa.

require __DIR__ . '/_auth.php';
require __DIR__ . '/_dados.php';
exigirLogin();

$aviso = '';
$tipo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrfValido($_POST['csrf'] ?? null)) {
    $aviso = 'Sessão inválida. Recarregue a página e tente de novo.';
    $tipo  = 'erro';
  } else {
    $id   = (int) ($_POST['id'] ?? 0);
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'capa' && $id > 0) {
      [$ok, $r] = enviarImagem($_FILES['capa'] ?? []);
      if ($ok) {
        [$ok2, $msg2] = salvarItem(COL_CURSOS, $id, ['imagem_capa' => $r]);
        $aviso = $ok2 ? 'Capa atualizada! Já está no ar.' : $msg2;
        $tipo  = $ok2 ? 'ok' : 'erro';
      } else {
        $aviso = $r;
        $tipo  = 'erro';
      }
    } elseif ($acao === 'remover_capa' && $id > 0) {
      [$ok, $msg] = salvarItem(COL_CURSOS, $id, ['imagem_capa' => null]);
      $aviso = $ok ? 'Capa removida. O curso volta a mostrar o ícone.' : $msg;
      $tipo  = $ok ? 'ok' : 'erro';
    } elseif ($acao === 'alternar' && $id > 0) {
      $campo = $_POST['campo'] === 'destaque' ? 'destaque' : 'ativo';
      $novo  = ($_POST['novo'] ?? '') === '1';
      [$ok, $msg] = salvarItem(COL_CURSOS, $id, [$campo => $novo]);
      $aviso = $ok ? 'Alteração salva.' : $msg;
      $tipo  = $ok ? 'ok' : 'erro';
    }

    limparCache();
  }
}

$cursos = cursosDoPainel();

// Agrupa por modalidade, na mesma ordem do site.
$grupos = ['EJA' => [], 'TECNICO' => [], 'OUTROS' => []];
foreach ($cursos as $c) {
  $cat = strtoupper((string) $c['_categoria']);
  $chave = $cat === 'EJA' ? 'EJA' : ($cat === 'TECNICO' ? 'TECNICO' : 'OUTROS');
  $grupos[$chave][] = $c;
}
$rotulos = ['EJA' => 'Supletivo EJA', 'TECNICO' => 'Cursos Técnicos', 'OUTROS' => 'Cursos Livres'];

$titulo   = 'Cursos e capas';
$abaAtiva = 'cursos';
require __DIR__ . '/_topo.php';
?>

<?php if ($aviso): ?>
  <div class="aviso aviso--<?= e($tipo) ?>">
    <i class="ri-<?= $tipo === 'ok' ? 'check' : 'error-warning' ?>-line"></i> <?= e($aviso) ?>
  </div>
<?php endif; ?>

<p class="painel-intro">
  A capa aparece no card da home e no topo da página do curso. Sem capa, o site
  usa o ícone. O <strong>preço vem do catálogo do AVASET</strong> e não se edita aqui.
  Para mudar textos, clique em <em>Editar textos</em>.
</p>

<?php foreach ($grupos as $chave => $lista): if (!$lista) continue; ?>
  <h2 class="grupo-titulo"><?= e($rotulos[$chave]) ?> <span><?= count($lista) ?></span></h2>

  <div class="cursos-grade">
    <?php foreach ($lista as $c):
      $capa = $c['imagem_capa'] ?? null;
      $nome = trim((string) ($c['nome_exibicao'] ?? '')) !== '' ? $c['nome_exibicao'] : $c['_nome_catalogo'];
      $livre = $chave === 'OUTROS';
    ?>
      <article class="curso-item <?= empty($c['ativo']) ? 'curso-item--off' : '' ?>">
        <div class="curso-item__capa">
          <?php if ($capa): ?>
            <img src="../api/imagem.php?id=<?= e($capa) ?>&w=400" alt="">
          <?php else: ?>
            <span class="sem-capa"><?= e($c['emoji'] ?? '📘') ?></span>
          <?php endif; ?>
        </div>

        <div class="curso-item__corpo">
          <span class="curso-item__id"><?= e($c['id_curso']) ?></span>
          <h3><?= e($nome) ?></h3>
          <?php if (!$c['_no_catalogo']): ?>
            <p class="curso-item__alerta"><i class="ri-alert-line"></i> Não está no catálogo de preços — não aparece no site.</p>
          <?php endif; ?>

          <form method="post" enctype="multipart/form-data" class="curso-item__envio">
            <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
            <input type="hidden" name="acao" value="capa">
            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <label class="botao-arquivo">
              <i class="ri-image-add-line"></i> <?= $capa ? 'Trocar capa' : 'Enviar capa' ?>
              <input type="file" name="capa" accept="image/*" onchange="this.form.submit()">
            </label>
          </form>

          <div class="curso-item__acoes">
            <?php if ($capa): ?>
              <form method="post" onsubmit="return confirm('Remover a capa deste curso?')">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="acao" value="remover_capa">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                <button type="submit" class="link-acao"><i class="ri-delete-bin-line"></i> Remover capa</button>
              </form>
            <?php endif; ?>

            <form method="post">
              <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
              <input type="hidden" name="acao" value="alternar">
              <input type="hidden" name="campo" value="ativo">
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <input type="hidden" name="novo" value="<?= empty($c['ativo']) ? '1' : '0' ?>">
              <button type="submit" class="link-acao">
                <i class="ri-<?= empty($c['ativo']) ? 'eye' : 'eye-off' ?>-line"></i>
                <?= empty($c['ativo']) ? 'Mostrar no site' : 'Esconder do site' ?>
              </button>
            </form>

            <a class="link-acao" href="curso.php?id=<?= (int) $c['id'] ?>"><i class="ri-edit-line"></i> Editar textos</a>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/_rodape.php'; ?>
