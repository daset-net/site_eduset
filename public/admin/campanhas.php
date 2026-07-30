<?php
/**
 * admin/campanhas.php — a escola decide qual desconto o site anuncia.
 *
 * Sem campanha, o site gira os descontos sozinho a cada ciclo (ofertaDoCiclo).
 * Aqui a escola assume o volante de dois jeitos, e só um vale por vez:
 *
 *   PERMANENTE  — trava um desconto sem prazo. Como não há prazo, o contador da
 *                 página do curso desaparece: o site não inventa urgência.
 *   PROGRAMADO  — desconto valendo de uma data até outra (data sazonal, semana
 *                 do cliente, aniversário da escola). O contador aponta para o
 *                 fim real da campanha.
 *
 * Um permanente ligado impede programar datas, e datas programadas impedem
 * ligar o permanente — é a regra pedida, e ela evita a pergunta "qual dos dois
 * vale hoje?", que ninguém saberia responder olhando a tela.
 *
 * BOLSA nunca entra: as faixas ofertáveis vêm de faixasDeDesconto(), que aplica
 * a mesma régua da vitrine (ingresso=bolsa ou 60%+ ficam fora).
 */

require __DIR__ . '/_auth.php';
require __DIR__ . '/_dados.php';
exigirLogin();

// A leitura, a gravação e as regras de conflito ficam em _dados.php, junto do
// resto do acesso ao Directus; aqui fica só o que é tela.
function dataBr(string $iso): string {
  $d = DateTime::createFromFormat('Y-m-d', $iso);
  return $d ? $d->format('d/m/Y') : $iso;
}

$aviso = '';
$tipo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrfValido($_POST['csrf'] ?? null)) {
    $aviso = 'Sessão inválida. Recarregue a página e tente de novo.';
    $tipo  = 'erro';
  } else {
    $estado = campanhasAtuais();
    $acao   = $_POST['acao'] ?? '';
    $faixas = faixasDeDesconto();

    if ($acao === 'permanente') {
      $desconto = (int) ($_POST['desconto'] ?? 0);

      if ($desconto !== 0 && !in_array($desconto, $faixas, true)) {
        $aviso = 'Esse desconto não existe no catálogo (ou é faixa de bolsa, que não pode ser anunciada).';
        $tipo  = 'erro';
      } elseif ($desconto !== 0 && $estado['programadas']) {
        $aviso = 'Existe programação de datas ativa. Remova as campanhas programadas antes de fixar um desconto permanente.';
        $tipo  = 'erro';
      } else {
        $estado['permanente'] = $desconto;
        [$ok, $msg] = gravarCampanhas($estado);
        $aviso = $ok
          ? ($desconto === 0
              ? 'Desconto permanente desligado. O site volta a girar os descontos por ciclo.'
              : 'Pronto: o site passa a anunciar ' . $desconto . '% em todos os cursos, sem prazo.')
          : $msg;
        $tipo = $ok ? 'ok' : 'erro';
      }

    } elseif ($acao === 'programar') {
      $nova = [
        'nome'     => trim((string) ($_POST['nome'] ?? '')),
        'desconto' => (int) ($_POST['desconto'] ?? 0),
        'inicio'   => trim((string) ($_POST['inicio'] ?? '')),
        'fim'      => trim((string) ($_POST['fim'] ?? '')),
      ];

      $conflito = conflita($nova, $estado['programadas']);

      if ($estado['permanente'] > 0) {
        $aviso = 'Existe um desconto permanente de ' . $estado['permanente'] . '% ligado. Desligue-o para programar datas.';
        $tipo  = 'erro';
      } elseif (!in_array($nova['desconto'], $faixas, true)) {
        $aviso = 'Escolha uma das faixas de desconto do catálogo.';
        $tipo  = 'erro';
      } elseif (!janelaCampanha($nova)) {
        $aviso = 'Confira as datas: a de fim não pode ser antes da de início.';
        $tipo  = 'erro';
      } elseif ($conflito) {
        $aviso = 'Esse período se cruza com a campanha de '
               . dataBr((string) $conflito['inicio']) . ' a ' . dataBr((string) $conflito['fim'])
               . '. Ajuste as datas para não haver dois descontos no mesmo dia.';
        $tipo  = 'erro';
      } else {
        $estado['programadas'] = ordenarPorInicio(array_merge($estado['programadas'], [$nova]));
        [$ok, $msg] = gravarCampanhas($estado);
        $aviso = $ok ? 'Campanha programada.' : $msg;
        $tipo  = $ok ? 'ok' : 'erro';
      }

    } elseif ($acao === 'remover') {
      $i = (int) ($_POST['indice'] ?? -1);
      if (isset($estado['programadas'][$i])) {
        array_splice($estado['programadas'], $i, 1);
        [$ok, $msg] = gravarCampanhas($estado);
        $aviso = $ok ? 'Campanha removida.' : $msg;
        $tipo  = $ok ? 'ok' : 'erro';
      }
    }

    limparCache();
  }
}

$estado   = campanhasAtuais();
$faixas   = faixasDeDesconto();
$vigente  = campanhaDe($estado);
$hoje     = (new DateTime('now', new DateTimeZone(TZ_CAMPANHA)))->getTimestamp();
$cicloFim = cicloOferta()['fim'];

$titulo   = 'Campanhas de desconto';
$abaAtiva = 'campanhas';
require __DIR__ . '/_topo.php';
?>

<?php if ($aviso): ?>
  <div class="aviso aviso--<?= e($tipo) ?>">
    <i class="ri-<?= $tipo === 'ok' ? 'check' : 'error-warning' ?>-line"></i> <?= e($aviso) ?>
  </div>
<?php endif; ?>

<p class="painel-intro">
  Aqui você decide <strong>qual desconto o site anuncia</strong>. Pode fixar um desconto
  sem prazo ou programar períodos com data de início e fim, para uma data sazonal.
  O preço em si vem do catálogo do AVASET — o que se escolhe aqui é qual faixa vai para
  a vitrine. <strong>Bolsa não entra nesta lista</strong>: ela é concessão da escola, decidida
  caso a caso, e não pode ser anunciada.
</p>

<?php if (!$faixas): ?>
  <div class="aviso aviso--erro">
    <i class="ri-error-warning-line"></i>
    Não encontramos faixas de desconto no catálogo do AVASET. Cadastre as versões de
    desconto dos cursos no GESET antes de montar campanha.
  </div>
<?php else: ?>

<!-- ------------------------------------------------------------ o que está no ar -->
<div class="campanha-agora <?= $vigente ? 'campanha-agora--ativa' : '' ?>">
  <div class="campanha-agora__selo">
    <i class="ri-<?= $vigente ? 'megaphone' : 'refresh' ?>-line"></i>
  </div>
  <div>
    <?php if ($vigente && $vigente['permanente']): ?>
      <strong>No ar agora: <?= (int) $vigente['desconto'] ?>% em todos os cursos, sem prazo.</strong>
      <p>Como não há data de fim, a página do curso não mostra contador.</p>
    <?php elseif ($vigente): ?>
      <strong>No ar agora: <?= (int) $vigente['desconto'] ?>% até <?= e(date('d/m/Y', $vigente['fim'])) ?>.</strong>
      <p>O contador da página do curso aponta para essa data.</p>
    <?php else: ?>
      <strong>Nenhuma campanha ativa — o site está girando os descontos sozinho.</strong>
      <p>
        A rotação automática troca a faixa anunciada a cada ciclo; o ciclo atual termina em
        <?= e(date('d/m/Y', $cicloFim)) ?>. Programe datas ou fixe um desconto abaixo para assumir o controle.
      </p>
    <?php endif; ?>
  </div>
</div>

<!-- ------------------------------------------------------------ permanente -->
<section class="campanha-bloco">
  <h2><i class="ri-pushpin-line"></i> Desconto permanente</h2>
  <p class="campanha-bloco__ajuda">
    Trava um desconto para valer até você mudar. Enquanto estiver ligado,
    <strong>não é possível programar datas</strong> — e a rotação automática fica desligada.
  </p>

  <?php if ($estado['programadas'] && $estado['permanente'] === 0): ?>
    <p class="campanha-trava">
      <i class="ri-lock-line"></i>
      Você tem <?= count($estado['programadas']) ?> campanha(s) programada(s). Remova a programação
      abaixo para poder fixar um desconto permanente.
    </p>
  <?php endif; ?>

  <form method="post" class="campanha-form">
    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
    <input type="hidden" name="acao" value="permanente">

    <label>
      Desconto
      <select name="desconto" <?= ($estado['programadas'] && $estado['permanente'] === 0) ? 'disabled' : '' ?>>
        <option value="0">Nenhum (deixar o site girar)</option>
        <?php foreach ($faixas as $f): ?>
          <option value="<?= (int) $f ?>" <?= $estado['permanente'] === $f ? 'selected' : '' ?>>
            <?= (int) $f ?>%
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <button type="submit" class="btn btn-primary"
            <?= ($estado['programadas'] && $estado['permanente'] === 0) ? 'disabled' : '' ?>>
      Salvar
    </button>
  </form>
</section>

<!-- ------------------------------------------------------------ programação -->
<section class="campanha-bloco">
  <h2><i class="ri-calendar-event-line"></i> Programação por datas</h2>
  <p class="campanha-bloco__ajuda">
    Cada linha vale do primeiro ao último dia informado. Os períodos não podem se
    cruzar: dois descontos válidos no mesmo dia deixariam a vitrine indefinida.
  </p>

  <?php if ($estado['permanente'] > 0): ?>
    <p class="campanha-trava">
      <i class="ri-lock-line"></i>
      Há um desconto permanente de <?= (int) $estado['permanente'] ?>% ligado. Coloque o permanente
      em <em>Nenhum</em> para programar datas.
    </p>
  <?php else: ?>
    <form method="post" class="campanha-form">
      <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <input type="hidden" name="acao" value="programar">

      <label>
        Nome <small>opcional</small>
        <input type="text" name="nome" maxlength="60" placeholder="Semana do Cliente">
      </label>
      <label>
        Desconto
        <select name="desconto">
          <?php foreach ($faixas as $f): ?>
            <option value="<?= (int) $f ?>"><?= (int) $f ?>%</option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>
        Início
        <input type="date" name="inicio" required>
      </label>
      <label>
        Fim
        <input type="date" name="fim" required>
      </label>

      <button type="submit" class="btn btn-primary">Programar</button>
    </form>
  <?php endif; ?>

  <?php if (!$estado['programadas']): ?>
    <p class="campanha-vazio">Nenhuma campanha programada.</p>
  <?php else: ?>
    <table class="campanha-tabela">
      <thead>
        <tr><th>Campanha</th><th>Desconto</th><th>Período</th><th>Situação</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($estado['programadas'] as $i => $c): ?>
          <?php
            [$de, $ate] = janelaCampanha($c);
            if ($hoje < $de)       { $situacao = 'Agendada';  $classe = 'futura'; }
            elseif ($hoje > $ate)  { $situacao = 'Encerrada'; $classe = 'passada'; }
            else                   { $situacao = 'No ar';     $classe = 'agora'; }
          ?>
          <tr class="campanha-linha--<?= $classe ?>">
            <td><?= e(trim((string) ($c['nome'] ?? '')) !== '' ? $c['nome'] : '—') ?></td>
            <td><strong><?= (int) $c['desconto'] ?>%</strong></td>
            <td><?= e(dataBr((string) $c['inicio'])) ?> a <?= e(dataBr((string) $c['fim'])) ?></td>
            <td><span class="campanha-situacao campanha-situacao--<?= $classe ?>"><?= e($situacao) ?></span></td>
            <td>
              <form method="post" onsubmit="return confirm('Remover esta campanha?')">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="acao" value="remover">
                <input type="hidden" name="indice" value="<?= (int) $i ?>">
                <button type="submit" class="campanha-remover" title="Remover">
                  <i class="ri-delete-bin-line"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</section>

<?php endif; ?>

<?php require __DIR__ . '/_rodape.php'; ?>
