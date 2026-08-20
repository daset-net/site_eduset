<?php
// curso.php — página de conversão de um curso.
// Preço vem do ava_catalogo_curso; textos e imagem, da site_catalogo_cursos.
// Uso: /curso.php?id=CT005  ou  /curso.php?id=tecnico-em-administracao
// Acrescente &ir=matricula ao link para a página abrir direto no formulário.

require __DIR__ . '/api/_catalogo.php';
require __DIR__ . '/api/_conteudo.php';

// Aceita tanto o código (CT005) quanto o slug (tecnico-em-administracao).
$chave = preg_replace('/[^A-Za-z0-9-]/', '', $_GET['id'] ?? '');
$curso = $chave !== '' ? (cursoPorId(strtoupper($chave)) ?? cursoPorId(strtolower($chave))) : null;

if (!$curso) {
  header('Location: index.php#cursos', true, 302);
  exit;
}

[$todos] = catalogo();
$conteudo = conteudoCurso($curso);
$ano = date('Y');

// Link de divulgação de um polo (?polo=codigo): precisa ser lido antes de
// qualquer saída, porque é aqui que o cookie da visita é gravado.
$polo = poloUnidade();

// ?ir=matricula (link de campanha) segue nos links internos, para o visitante
// não perder o atalho se trocar de curso pelo caminho.
$sufixoIr = (($_GET['ir'] ?? '') === 'matricula') ? '&ir=matricula' : '';

// Até 3 cursos da mesma modalidade para o rodapé da página.
$relacionados = array_values(array_filter(
  $todos,
  fn($c) => $c['categoria'] === $curso['categoria'] && $c['id'] !== $curso['id']
));
shuffle($relacionados);
$relacionados = array_slice($relacionados, 0, 3);

// Depoimento de quem fez este curso (site_alunos_depoimentos). Sem depoimento
// cadastrado o bloco some: a página não empresta elogio de outro curso.
$depoimento = depoimentosDoCurso($curso['idCatalogo'] ?? $curso['id'], 1)[0] ?? null;

$economia = max(0, (float) str_replace(['.', ','], ['', '.'], $curso['precoDe'])
                 - (float) str_replace(['.', ','], ['', '.'], $curso['preco']));

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$tituloPagina = $curso['seoTitulo'] !== ''
  ? $curso['seoTitulo']
  : $curso['nome'] . ' · ' . $curso['categoriaLabel'] . ' · EDUSET';

$metaDescricao = $curso['seoDescricao'] !== ''
  ? $curso['seoDescricao']
  : $conteudo['chamada'] . '. ' . $curso['descricao'];

// Grade do curso (ava_pacote_curso): as matérias que o aluno vai estudar.
$materias = materiasDoCurso($curso);
$horasGrade = array_sum(array_column($materias, 'horas'));

// Sob o link de campanha de um polo, quem recebe a dúvida é o polo.
$whatsapp = whatsappLink('Olá! Quero saber mais sobre o curso ' . $curso['nome'] . '.');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($metaDescricao) ?>">
  <meta name="theme-color" content="#002454">
  <title><?= e($tituloPagina) ?></title>

  <meta property="og:title" content="<?= e($curso['nome']) ?> · EDUSET">
  <meta property="og:description" content="<?= e($conteudo['chamada']) ?>">
  <meta property="og:type" content="website">
  <?php if ($curso['imagem'] !== ''): ?>
  <meta property="og:image" content="<?= e($curso['imagem']) ?>">
  <?php endif; ?>

  <link rel="icon" href="assets/img/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="apple-touch-icon" href="assets/img/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <link rel="stylesheet" href="<?= versao('assets/css/style.css') ?>">
</head>
<body class="page-curso">

  <!-- ===================== HEADER ===================== -->
  <header class="header" id="header">
    <div class="container header__inner">
      <a href="index.php" class="brand">
        <img class="brand__neg" src="assets/img/eduset-negativo.png" alt="EDUSET">
        <img class="brand__cor" src="assets/img/eduset.png" alt="EDUSET">
      </a>
      <nav class="nav">
        <a href="index.php">Início</a>
        <?php if ($materias): ?><a href="#grade">Matérias</a><?php endif; ?>
        <a href="index.php#cursos">Cursos</a>
        <?php if (!visitaDoPolo()): ?><a href="unidades.php">Unidades</a><?php endif; ?>
        <a href="index.php#diferenciais">Diferenciais</a>
        <a href="index.php#contato">Contato</a>
      </nav>
      <div class="header__cta">
        <a href="#matricula" class="btn btn-primary">Quero me matricular <i class="ri-arrow-right-line"></i></a>
      </div>
    </div>
  </header>

  <!-- ===================== HERO DO CURSO ===================== -->
  <section class="curso-hero">
    <div class="container curso-hero__grid">
      <div class="curso-hero__content">
        <nav class="crumbs">
          <a href="index.php">Início</a> <i class="ri-arrow-right-s-line"></i>
          <a href="index.php#cursos"><?= e($curso['categoriaLabel']) ?></a> <i class="ri-arrow-right-s-line"></i>
          <span><?= e($curso['nome']) ?></span>
        </nav>

        <span class="curso-hero__badge"><span class="dot"></span> Matrículas abertas · Turmas <?= $ano ?></span>
        <h1><?= e($conteudo['chamada']) ?></h1>
        <p class="curso-hero__nome"><?= e($curso['emoji']) ?> <?= e($curso['nome']) ?> · <?= e($curso['categoriaLabel']) ?></p>
        <p class="lead"><?= e($conteudo['promessa']) ?></p>

        <ul class="curso-hero__marcas">
          <li><i class="ri-time-line"></i> <?= e($curso['duracao']) ?></li>
          <li><i class="ri-computer-line"></i> <?= e($curso['modalidade']) ?></li>
          <li><i class="ri-award-line"></i> Certificado com validade nacional</li>
          <li><i class="ri-calendar-check-line"></i> Comece hoje mesmo</li>
        </ul>

        <div class="curso-hero__actions">
          <a href="#matricula" class="btn btn-light">Garantir minha vaga <i class="ri-arrow-right-line"></i></a>
          <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" class="btn btn-ghost-light">
            <i class="ri-whatsapp-line"></i> Tirar dúvidas no WhatsApp
          </a>
        </div>
      </div>

      <!-- Cartão de oferta -->
      <aside class="oferta" id="oferta">
        <div class="oferta__topo" style="background: <?= e($curso['cor']) ?>">
          <?php if ($curso['imagem'] !== ''): ?>
            <img class="oferta__capa" src="<?= e($curso['imagem']) ?>" alt="<?= e($curso['nome']) ?>" loading="lazy">
          <?php else: ?>
            <span class="oferta__emoji"><?= e($curso['emoji']) ?></span>
          <?php endif; ?>
          <?php if ($curso['desconto']): ?>
            <span class="oferta__off">-<?= (int) $curso['desconto'] ?>% hoje</span>
          <?php endif; ?>
        </div>
        <div class="oferta__corpo">
          <span class="oferta__cat"><?= e($curso['categoriaLabel']) ?></span>
          <h2><?= e($curso['nome']) ?></h2>

          <?php if ($curso['precoDe'] !== '0,00'): ?>
            <p class="oferta__de">De <s>R$ <?= e($curso['precoDe']) ?></s> por mês</p>
          <?php endif; ?>

          <p class="oferta__por">
            <?php if ($curso['parcelas']): ?><em><?= (int) $curso['parcelas'] ?>x de</em><?php endif; ?>
            <strong>R$ <?= e($curso['preco']) ?></strong>
          </p>
          <?php if ($curso['valorTotal'] !== '0,00'): ?>
            <p class="oferta__total">Total do curso: R$ <?= e($curso['valorTotal']) ?></p>
          <?php endif; ?>
          <?php if ($economia > 0): ?>
            <p class="oferta__economia"><i class="ri-price-tag-3-line"></i> Você economiza R$ <?= e(number_format($economia, 2, ',', '.')) ?> por parcela</p>
          <?php endif; ?>

          <?php if ($curso['desconto']): ?>
          <div class="contador" data-fim="<?= e($curso['ofertaFim']) ?>">
            <p class="contador__titulo"><i class="ri-timer-flash-line"></i> Condição de <?= (int) $curso['desconto'] ?>% termina em</p>
            <div class="contador__relogio">
              <span><strong data-parte="dias">--</strong><small>dias</small></span>
              <span><strong data-parte="horas">--</strong><small>horas</small></span>
              <span><strong data-parte="min">--</strong><small>min</small></span>
              <span><strong data-parte="seg">--</strong><small>seg</small></span>
            </div>
            <p class="contador__nota">Depois disso o valor volta para a tabela do próximo ciclo.</p>
          </div>
          <?php endif; ?>

          <p class="online" id="online" data-curso="<?= e($curso['id']) ?>" hidden>
            <span class="online__ponto"></span>
            <span id="online-texto"></span>
          </p>

          <a href="#matricula" class="btn btn-primary oferta__btn">Quero me matricular <i class="ri-arrow-right-line"></i></a>

          <ul class="oferta__lista">
            <li><i class="ri-check-line"></i> Sem taxa de matrícula</li>
            <li><i class="ri-check-line"></i> Material didático incluso</li>
            <li><i class="ri-check-line"></i> Tutoria durante todo o curso</li>
            <li><i class="ri-check-line"></i> Acesso imediato após a matrícula</li>
          </ul>
          <p class="oferta__nota">Condição válida para as matrículas desta turma.</p>
        </div>
      </aside>
    </div>
  </section>

  <!-- ===================== POR QUE FAZER ===================== -->
  <section class="section">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Por que fazer</span>
        <h2>O que muda na sua vida <span class="gradient-text">depois deste curso</span></h2>
        <p><?= e($conteudo['mercado']) ?></p>
      </div>

      <div class="curso-cols">
        <div class="curso-box">
          <h3><i class="ri-book-open-line"></i> O que você vai aprender</h3>
          <ul class="lista-check">
            <?php foreach ($conteudo['aprender'] as $item): ?>
              <li><i class="ri-check-line"></i> <?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="curso-box">
          <h3><i class="ri-user-heart-line"></i> Este curso é para você se…</h3>
          <ul class="lista-check">
            <?php foreach ($conteudo['publico'] as $item): ?>
              <li><i class="ri-check-line"></i> <?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>

          <h3 style="margin-top:26px"><i class="ri-briefcase-line"></i> Onde você pode atuar</h3>
          <ul class="lista-check">
            <?php foreach ($conteudo['saidas'] as $item): ?>
              <li><i class="ri-arrow-right-line"></i> <?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== GRADE ===================== -->
  <?php if ($materias): ?>
  <section class="section section--soft" id="grade">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Grade curricular</span>
        <h2>As matérias que você <span class="gradient-text">vai estudar</span></h2>
        <p>
          <?= count($materias) ?> matéria<?= count($materias) > 1 ? 's' : '' ?>
          <?php if ($horasGrade > 0): ?>
            e cerca de <?= number_format($horasGrade, 0, ',', '.') ?> horas de conteúdo —
          <?php endif; ?>
          liberadas na ordem, para você avançar sem se perder.
        </p>
      </div>

      <ol class="grade-lista">
        <?php foreach ($materias as $i => $m): ?>
          <li>
            <span class="grade-lista__n"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <div>
              <strong><?= e($m['nome']) ?></strong>
              <?php if (!empty($m['horas'])): ?><small><?= (int) $m['horas'] ?>h de conteúdo</small><?php endif; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
    </div>
  </section>
  <?php endif; ?>

  <!-- ===================== INCLUSO ===================== -->
  <section class="section section--soft">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Está incluído</span>
        <h2>Tudo o que você recebe ao <span class="gradient-text">se matricular</span></h2>
      </div>
      <div class="inclui-grid">
        <div class="inclui"><i class="ri-smartphone-line"></i><h4>Estude pelo celular</h4><p>Plataforma leve, que abre no celular, no tablet ou no computador, a qualquer hora do dia.</p></div>
        <div class="inclui"><i class="ri-user-voice-line"></i><h4>Tutor de verdade</h4><p>Alguém para responder sua dúvida quando o conteúdo travar — do primeiro ao último módulo.</p></div>
        <div class="inclui"><i class="ri-award-line"></i><h4>Certificado</h4><p>Documento de conclusão com validade nacional, aceito por empresas e instituições de ensino.</p></div>
        <div class="inclui"><i class="ri-time-line"></i><h4>Seu ritmo</h4><p>Sem horário fixo de aula. Você acelera quando sobra tempo e desacelera quando a semana aperta.</p></div>
        <div class="inclui"><i class="ri-refresh-line"></i><h4>Provas refeitas</h4><p>Não passou de primeira? Refaz. Aqui o objetivo é você concluir, não reprovar.</p></div>
        <div class="inclui"><i class="ri-customer-service-2-line"></i><h4>Suporte na matrícula</h4><p>Um consultor acompanha você desde a documentação até o primeiro acesso à plataforma.</p></div>
      </div>
    </div>
  </section>

  <!-- ===================== PROVA SOCIAL ===================== -->
  <section class="section">
    <div class="container">
      <div class="curso-prova<?= $depoimento ? '' : ' curso-prova--sozinho' ?>">
        <div>
          <span class="eyebrow">Quem já fez</span>
          <h2>Mais de <span class="gradient-text">12 mil alunos</span> já começaram aqui</h2>
          <p>Gente que trabalhava o dia inteiro, achava que não ia dar conta e hoje tem o certificado na mão. O curso foi desenhado exatamente para essa rotina apertada.</p>
          <div class="curso-prova__stats">
            <div><strong><?= e(config('stat_alunos', '+12 mil')) ?></strong><span>alunos matriculados</span></div>
            <div><strong><?= e(config('stat_satisfacao', '98%')) ?></strong><span>de satisfação</span></div>
            <div><strong>100%</strong><span>online e no seu ritmo</span></div>
          </div>
        </div>
        <?php if ($depoimento): ?>
        <div class="testi">
          <div class="stars"><?php for ($i = 0; $i < $depoimento['estrelas']; $i++): ?><i class="ri-star-fill"></i><?php endfor; ?></div>
          <p>“<?= e($depoimento['texto']) ?>”</p>
          <div class="who">
            <div class="av"><?= e($depoimento['iniciais']) ?></div>
            <div><strong><?= e($depoimento['nome']) ?></strong><span><?= e($depoimento['curso'] !== '' ? $depoimento['curso'] : $curso['nome']) ?></span></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ===================== MATRÍCULA ===================== -->
  <section class="section section--soft" id="matricula">
    <div class="container contact-grid">
      <div class="contact-info">
        <span class="eyebrow" style="display:inline-block;font-size:13px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--green-600);background:#fff;padding:6px 16px;border-radius:100px;margin-bottom:16px">Matrícula</span>
        <h2>Comece <span class="gradient-text">hoje</span> — a vaga é sua</h2>
        <p>Preencha seus dados e conclua a matrícula em <strong><?= e($curso['nome']) ?></strong> agora, na condição de <strong>R$ <?= e($curso['preco']) ?><?= $curso['parcelas'] ? ' em ' . (int) $curso['parcelas'] . 'x' : '' ?></strong>. Ao final você já recebe o número da matrícula e os dados de acesso à plataforma.</p>

        <div class="line"><div class="ic"><i class="ri-whatsapp-line"></i></div><div><strong>WhatsApp</strong><span><?= e(whatsappExibicao()) ?></span></div></div>
        <div class="line"><div class="ic"><i class="ri-mail-line"></i></div><div><strong>E-mail</strong><span><?= e(config('email_contato', 'contato@eduset.com.br')) ?></span></div></div>
        <div class="line"><div class="ic"><i class="ri-map-pin-line"></i></div><div><strong>Atendimento</strong><span><?= e(config('horario_atendimento', 'Segunda a sexta, das 8h às 18h')) ?></span></div></div>
      </div>

      <form class="contact-form" id="form-matricula" data-curso="<?= e($curso['id']) ?>" novalidate>
        <!-- Deixa claro, antes do primeiro campo, que aqui a matrícula é feita de verdade. -->
        <div class="form-topo">
          <span class="form-topo__selo"><i class="ri-file-list-3-line"></i> Ficha de matrícula</span>
          <h3>Você está se matriculando em <strong><?= e($curso['nome']) ?></strong></h3>
          <p>Preencha com atenção: estes dados vão para a secretaria e valem para o certificado.</p>
        </div>

        <?php if ($polo): ?>
        <!-- Veio pelo link de divulgação de um polo: a matrícula fica com ele. -->
        <div class="form-polo">
          <i class="ri-map-pin-2-line"></i>
          <span>Matrícula pela unidade <strong><?= e($polo['nome']) ?></strong></span>
        </div>
        <?php endif; ?>
        <div class="form-alert" id="form-alerta" hidden></div>

        <p class="form-etapa">1. Seus dados</p>
        <div class="field">
          <label for="mat-nome">Nome completo</label>
          <input type="text" id="mat-nome" name="nome" placeholder="Como está no documento" autocomplete="name" required>
        </div>
        <div class="form-linha">
          <div class="field">
            <label for="mat-cpf">CPF</label>
            <input type="tel" id="mat-cpf" name="cpf" placeholder="000.000.000-00" inputmode="numeric" required>
          </div>
          <div class="field">
            <label for="mat-nascimento">Nascimento</label>
            <input type="tel" id="mat-nascimento" name="nascimento" placeholder="DD/MM/AAAA" inputmode="numeric" required>
          </div>
        </div>
        <div class="form-linha">
          <div class="field">
            <label for="mat-sexo">Sexo</label>
            <select id="mat-sexo" name="sexo" required>
              <option value="">Selecione</option>
              <option value="M">Masculino</option>
              <option value="F">Feminino</option>
            </select>
          </div>
          <div class="field">
            <label for="mat-celular">WhatsApp</label>
            <input type="tel" id="mat-celular" name="celular" placeholder="(00) 00000-0000" autocomplete="tel" required>
          </div>
        </div>
        <div class="field">
          <label for="mat-email">E-mail</label>
          <input type="email" id="mat-email" name="email" placeholder="voce@email.com" autocomplete="email" required>
        </div>

        <p class="form-etapa">2. Endereço</p>
        <div class="form-linha">
          <div class="field">
            <label for="mat-cep">CEP</label>
            <input type="tel" id="mat-cep" name="cep" placeholder="00000-000" inputmode="numeric" autocomplete="postal-code" required>
          </div>
          <div class="field">
            <label for="mat-numero">Número</label>
            <input type="text" id="mat-numero" name="numero" placeholder="123" required>
          </div>
        </div>
        <div class="field">
          <label for="mat-endereco">Endereço</label>
          <input type="text" id="mat-endereco" name="endereco" placeholder="Rua, avenida…" autocomplete="address-line1" required>
        </div>
        <div class="form-linha">
          <div class="field">
            <label for="mat-bairro">Bairro</label>
            <input type="text" id="mat-bairro" name="bairro" required>
          </div>
          <div class="field">
            <label for="mat-complemento">Complemento</label>
            <input type="text" id="mat-complemento" name="complemento" placeholder="Opcional">
          </div>
        </div>
        <div class="form-linha">
          <div class="field">
            <label for="mat-cidade">Cidade</label>
            <input type="text" id="mat-cidade" name="cidade" required>
          </div>
          <div class="field">
            <label for="mat-estado">UF</label>
            <input type="text" id="mat-estado" name="estado" maxlength="2" placeholder="CE" required>
          </div>
        </div>

        <div id="bloco-responsavel" hidden>
          <p class="form-etapa">3. Responsável financeiro</p>
          <p class="form-nota form-nota--esq">Menores de 18 anos precisam de um responsável.</p>
          <div class="field">
            <label for="mat-resp-nome">Nome do responsável</label>
            <input type="text" id="mat-resp-nome" name="nome_responsavel">
          </div>
          <div class="field">
            <label for="mat-resp-cpf">CPF do responsável</label>
            <input type="tel" id="mat-resp-cpf" name="cpf_responsavel" placeholder="000.000.000-00" inputmode="numeric">
          </div>
        </div>

        <!-- armadilha anti-robô: fica escondida e deve continuar vazia -->
        <div class="campo-armadilha" aria-hidden="true">
          <label>Não preencha este campo</label>
          <input type="text" name="site" tabindex="-1" autocomplete="off">
        </div>

        <div class="resumo-matricula">
          <span><?= e($curso['nome']) ?></span>
          <strong><?= $curso['parcelas'] ? (int) $curso['parcelas'] . 'x de R$ ' . e($curso['preco']) : 'R$ ' . e($curso['preco']) ?></strong>
        </div>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
          Concluir minha matrícula <i class="ri-send-plane-line"></i>
        </button>
        <p class="form-nota">Sem taxa de matrícula. O primeiro boleto é enviado por e-mail e o acesso libera após a confirmação do pagamento.</p>
      </form>

      <div class="matricula-sucesso" id="matricula-sucesso" hidden>
        <div class="matricula-sucesso__selo"><i class="ri-check-line"></i></div>
        <h3>Matrícula registrada!</h3>
        <p>Bem-vindo(a) à EDUSET, <strong id="suc-nome"></strong>. Sua matrícula em <strong id="suc-curso"></strong> foi criada.</p>
        <div class="matricula-sucesso__dados">
          <div><span>Número da matrícula</span><strong id="suc-numero"></strong></div>
          <div><span>Usuário (CPF)</span><strong id="suc-usuario"></strong></div>
          <div><span>Senha inicial</span><strong id="suc-senha"></strong></div>
        </div>
        <p class="form-nota form-nota--esq">Guarde esses dados. O acesso à plataforma é liberado assim que o pagamento da primeira parcela é confirmado — o boleto chega no seu e-mail. No primeiro login o sistema pede a troca da senha.</p>
        <a id="suc-portal" class="btn btn-primary" href="https://ead.eduset.com.br" target="_blank" rel="noopener">Ir para a plataforma <i class="ri-external-link-line"></i></a>
      </div>
    </div>
  </section>

  <!-- ===================== FAQ ===================== -->
  <section class="section">
    <div class="container container--estreito">
      <div class="section-head">
        <span class="eyebrow">Dúvidas</span>
        <h2>Perguntas que <span class="gradient-text">todo mundo faz</span></h2>
      </div>
      <div class="faq">
        <details>
          <summary>O certificado tem validade?</summary>
          <p>Sim. Ao concluir o curso você recebe o certificado emitido por instituição parceira credenciada, com validade nacional — o mesmo aceito por empresas, instituições de ensino e órgãos públicos.</p>
          <?php if (($curso['codigoMec'] ?? '') !== ''): ?>
            <p style="margin-top:10px;font-size:13px;opacity:.65">Registro da instituição parceira: <span style="font-variant-numeric:tabular-nums;letter-spacing:.3px"><?= e($curso['codigoMec']) ?></span></p>
          <?php endif; ?>
        </details>
        <details>
          <summary>Preciso ir até algum lugar assistir aula?</summary>
          <p><?php if ($curso['categoria'] === 'tecnico-competencia'): ?>Nesta modalidade, o AVA disponibiliza somente as provas de cada módulo. Não são exibidos videoaulas, exercícios, apostilas, jornada ou podcast.<?php else: ?>Não. O conteúdo é <?= e($curso['modalidade']) ?>: você estuda de onde estiver, pelo celular ou computador, no horário que der. <?= $curso['categoria'] === 'tecnico' ? 'Nos cursos técnicos, apenas atividades práticas e estágio, quando exigidos, acontecem com apoio de polo.' : '' ?><?php endif; ?></p>
        </details>
        <details>
          <summary>Quanto tempo leva para concluir?</summary>
          <p>A duração prevista é de <?= e($curso['duracao']) ?>, mas o ritmo é seu: quem estuda mais horas por semana termina antes.</p>
        </details>
        <details>
          <summary>E se eu não conseguir acompanhar?</summary>
          <p>Você tem tutoria durante todo o curso e pode refazer as avaliações. O curso foi feito para quem trabalha e estuda no tempo que sobra — não para reprovar ninguém.</p>
        </details>
        <details>
          <summary>Como funciona o pagamento?</summary>
          <p><?= $curso['parcelas'] ? 'Você paga em ' . (int) $curso['parcelas'] . 'x de R$ ' . e($curso['preco']) . '.' : 'O consultor apresenta as formas de pagamento disponíveis.' ?> Não há taxa de matrícula: o valor da parcela é tudo o que você paga.</p>
        </details>
        <details>
          <summary>Quando começo a estudar?</summary>
          <p>O acesso é liberado assim que a matrícula é confirmada. Não existe espera por início de turma.</p>
        </details>
      </div>
    </div>
  </section>

  <!-- ===================== RELACIONADOS ===================== -->
  <?php if ($relacionados): ?>
  <section class="section section--soft">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">Veja também</span>
        <h2>Outros cursos de <span class="gradient-text"><?= e($curso['categoriaLabel']) ?></span></h2>
      </div>
      <div class="course-grid">
        <?php foreach ($relacionados as $r): ?>
          <article class="course-card">
            <a class="course-card__link" href="curso.php?id=<?= e($r['slug'] !== '' ? $r['slug'] : $r['id']) . $sufixoIr ?>">
              <div class="course-card__media" style="background: <?= e($r['cor']) ?>">
                <?php if ($r['imagem'] !== ''): ?>
                  <img class="course-card__capa" src="<?= e($r['imagem']) ?>" alt="<?= e($r['nome']) ?>" loading="lazy">
                <?php else: ?>
                  <span class="emoji"><?= e($r['emoji']) ?></span>
                <?php endif; ?>
                <span class="course-card__badge"><?= e($r['categoriaLabel']) ?></span>
                <?php if ($r['desconto']): ?><span class="course-card__off">-<?= (int) $r['desconto'] ?>%</span><?php endif; ?>
              </div>
              <div class="course-card__body">
                <h4><?= e($r['nome']) ?></h4>
                <p><?= e($r['descricao']) ?></p>
                <div class="course-card__meta">
                  <span><i class="ri-time-line"></i> <?= e($r['duracao']) ?></span>
                  <span><i class="ri-computer-line"></i> <?= e($r['modalidade']) ?></span>
                </div>
                <div class="course-card__foot">
                  <div class="course-card__price">
                    <small><s>R$ <?= e($r['precoDe']) ?></s></small>
                    <strong><em><?= (int) $r['parcelas'] ?>x</em> R$ <?= e($r['preco']) ?><span>/mês</span></strong>
                  </div>
                  <span class="btn btn-primary" style="padding:10px 18px;font-size:14px">Ver <i class="ri-arrow-right-line"></i></span>
                </div>
              </div>
            </a>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ===================== CHAMADA FINAL ===================== -->
  <section class="curso-final">
    <div class="container">
      <h2>Daqui a <?= e($curso['duracao']) ?>, você pode estar com o certificado na mão</h2>
      <p>Ou exatamente onde está hoje. A diferença é uma decisão de dois minutos.</p>
      <a href="#matricula" class="btn btn-light">Quero me matricular agora <i class="ri-arrow-right-line"></i></a>
    </div>
  </section>

  <!-- ===================== FOOTER ===================== -->
  <footer class="footer">
    <div class="container">
      <div class="footer__grid">
        <div class="footer__brand">
          <img src="assets/img/eduset-negativo.png" alt="EDUSET">
          <p>Educação que transforma vidas. Supletivo EJA, cursos técnicos e cursos livres com certificação reconhecida e 100% online.</p>
          <div class="footer__social">
            <?php if (config('instagram')): ?><a href="<?= e(config('instagram')) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="ri-instagram-line"></i></a><?php endif; ?>
            <?php if (config('facebook')): ?><a href="<?= e(config('facebook')) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="ri-facebook-fill"></i></a><?php endif; ?>
            <a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="ri-whatsapp-line"></i></a>
            <?php if (config('youtube')): ?><a href="<?= e(config('youtube')) ?>" target="_blank" rel="noopener" aria-label="YouTube"><i class="ri-youtube-fill"></i></a><?php endif; ?>
          </div>
        </div>
        <div>
          <h5>Modalidades</h5>
          <ul>
            <li><a href="index.php#cursos">Supletivo EJA</a></li>
            <li><a href="index.php#cursos">Curso Técnico</a></li>
            <li><a href="index.php#cursos">Curso Livre</a></li>
          </ul>
        </div>
        <div>
          <h5>Institucional</h5>
          <ul>
            <li><a href="index.php#categorias">Sobre nós</a></li>
            <?php if (!visitaDoPolo()): ?><li><a href="unidades.php">Unidades</a></li><?php endif; ?>
            <li><a href="index.php#diferenciais">Diferenciais</a></li>
            <li><a href="index.php#contato">Contato</a></li>
          </ul>
        </div>
        <div>
          <h5>Atendimento</h5>
          <ul>
            <li><a href="#matricula">Matrícula</a></li>
            <li><a href="index.php#contato">Fale conosco</a></li>
            <li><a href="<?= e($whatsapp) ?>" target="_blank" rel="noopener">WhatsApp</a></li>
          </ul>
        </div>
      </div>
      <div class="footer__bottom">
        <span>© <?= $ano ?> EDUSET · Todos os direitos reservados.</span>
        <span>Feito com <i class="ri-heart-fill" style="color:#ff5a5a"></i> para transformar vidas.</span>
      </div>
    </div>
  </footer>

  <a href="<?= e($whatsapp) ?>" class="whatsapp-float" target="_blank" rel="noopener" aria-label="WhatsApp">
    <i class="ri-whatsapp-line"></i>
  </a>

  <!-- Barra fixa de matrícula (mobile) -->
  <div class="barra-matricula">
    <div>
      <small><?= $curso['parcelas'] ? (int) $curso['parcelas'] . 'x de' : 'A partir de' ?></small>
      <strong>R$ <?= e($curso['preco']) ?></strong>
    </div>
    <a href="#matricula" class="btn btn-primary">Matricular <i class="ri-arrow-right-line"></i></a>
  </div>

<script src="<?= versao('assets/js/curso.js') ?>"></script>
<script src="<?= versao('assets/js/matricula.js') ?>"></script>
<script src="<?= versao('assets/js/avisos.js') ?>"></script>
</body>
</html>
