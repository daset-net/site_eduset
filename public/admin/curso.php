<?php
// admin/curso.php — edição dos textos de um curso (site_catalogo_cursos).

require __DIR__ . '/_auth.php';
require __DIR__ . '/_dados.php';
exigirLogin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($id <= 0) { header('Location: cursos.php'); exit; }

// Campos que o painel deixa editar, com rótulo e ajuda.
$CAMPOS = [
  'nome_exibicao'  => ['Nome exibido no site', 'Deixe em branco para usar o nome do catálogo.', 'texto'],
  'descricao_card' => ['Descrição do card', 'Frase curta que aparece embaixo do nome, na home.', 'area'],
  'duracao'        => ['Duração', 'Ex.: 18 meses, 80 horas.', 'texto'],
  'modalidade'     => ['Modalidade', 'Ex.: EAD com Polo Digital.', 'texto'],
  'emoji'          => ['Ícone', 'Usado quando o curso não tem capa.', 'texto'],
  'slug'           => ['Endereço da página', 'Só letras minúsculas e hífens. Ex.: tecnico-em-administracao.', 'texto'],
  'ordem'          => ['Ordem', 'Posição do curso dentro da modalidade.', 'numero'],
  'chamada'        => ['Chamada principal', 'O título grande da página do curso. É o argumento mais forte.', 'texto'],
  'promessa'       => ['Parágrafo de abertura', 'Explica o que muda na vida de quem faz o curso.', 'area'],
  'mercado'        => ['Argumento de mercado', 'Por que vale a pena: demanda, empregabilidade.', 'area'],
  'aprender'       => ['O que vai aprender', 'UM ITEM POR LINHA.', 'lista'],
  'publico'        => ['Para quem é', 'UM ITEM POR LINHA.', 'lista'],
  'saidas'         => ['Onde pode atuar', 'UM ITEM POR LINHA.', 'lista'],
  'seo_titulo'     => ['Título para o Google', 'Aparece na aba do navegador e na busca.', 'texto'],
  'seo_descricao'  => ['Descrição para o Google', 'Resumo que aparece no resultado da busca.', 'area'],
];

$aviso = '';
$tipo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrfValido($_POST['csrf'] ?? null)) {
    $aviso = 'Sessão inválida. Recarregue a página e tente de novo.';
    $tipo  = 'erro';
  } else {
    $campos = [];
    foreach ($CAMPOS as $nome => [, , $formato]) {
      if (!array_key_exists($nome, $_POST)) continue;
      $v = trim((string) $_POST[$nome]);
      if ($formato === 'numero') {
        $campos[$nome] = $v === '' ? null : (int) $v;
      } elseif ($nome === 'slug') {
        $v = strtolower($v);
        $campos[$nome] = preg_replace('/[^a-z0-9-]+/', '-', $v);
      } else {
        $campos[$nome] = $v;
      }
    }

    [$ok, $msg] = salvarItem(COL_CURSOS, $id, $campos);
    limparCache();
    $aviso = $ok ? 'Textos salvos! O site já está atualizado.' : $msg;
    $tipo  = $ok ? 'ok' : 'erro';
  }
}

$linhas = buscarColecao(COL_CURSOS, ['fields' => '*', 'filter' => json_encode(['id' => ['_eq' => $id]]), 'limit' => 1]);
$curso  = $linhas[0] ?? null;
if (!$curso) { header('Location: cursos.php'); exit; }

$titulo   = 'Editar: ' . ($curso['nome_exibicao'] ?: $curso['id_curso']);
$abaAtiva = 'cursos';
require __DIR__ . '/_topo.php';
?>

<a class="voltar" href="cursos.php"><i class="ri-arrow-left-line"></i> Voltar para os cursos</a>

<?php if ($aviso): ?>
  <div class="aviso aviso--<?= e($tipo) ?>">
    <i class="ri-<?= $tipo === 'ok' ? 'check' : 'error-warning' ?>-line"></i> <?= e($aviso) ?>
  </div>
<?php endif; ?>

<form method="post" class="painel-form">
  <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
  <input type="hidden" name="id" value="<?= (int) $id ?>">

  <p class="painel-intro">
    Curso <strong><?= e($curso['id_curso']) ?></strong>.
    O preço e as parcelas vêm do catálogo do AVASET e não são editados aqui.
    Campo deixado em branco volta a usar o texto padrão da modalidade.
  </p>

  <div class="campos">
    <?php foreach ($CAMPOS as $nome => [$rotulo, $ajuda, $formato]):
      $valor = (string) ($curso[$nome] ?? '');
    ?>
      <div class="campo <?= $formato === 'lista' || $formato === 'area' ? 'campo--largo' : '' ?>">
        <label for="f<?= e($nome) ?>">
          <?= e($rotulo) ?>
          <small><?= e($ajuda) ?></small>
        </label>
        <?php if ($formato === 'area'): ?>
          <textarea id="f<?= e($nome) ?>" name="<?= e($nome) ?>" rows="3"><?= e($valor) ?></textarea>
        <?php elseif ($formato === 'lista'): ?>
          <textarea id="f<?= e($nome) ?>" name="<?= e($nome) ?>" rows="6" class="area-lista"><?= e($valor) ?></textarea>
        <?php elseif ($formato === 'numero'): ?>
          <input id="f<?= e($nome) ?>" type="number" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
        <?php else: ?>
          <input id="f<?= e($nome) ?>" type="text" name="<?= e($nome) ?>" value="<?= e($valor) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="barra-salvar">
    <?php if (!empty($curso['slug'])): ?>
      <a class="btn btn-outline" target="_blank" rel="noopener"
         href="../curso.php?id=<?= e($curso['slug']) ?>">Ver a página <i class="ri-external-link-line"></i></a>
    <?php endif; ?>
    <button type="submit" class="btn btn-primary">Salvar textos <i class="ri-save-line"></i></button>
  </div>
</form>

<?php require __DIR__ . '/_rodape.php'; ?>
