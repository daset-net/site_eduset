<?php
// index.php — página principal do site EDUSET
// Textos e contatos vêm da coleção site_configuracoes (Directus).
require __DIR__ . '/api/_catalogo.php';

$ano = date('Y');
$whatsapp = 'https://wa.me/' . config('whatsapp', '5500000000000');

// Link de divulgação de um polo (?polo=codigo): guarda o cookie da visita antes
// de qualquer saída e, se a unidade existir, anuncia por quem o visitante veio.
$polo = poloUnidade();

function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e(config('seo_descricao', 'EDUSET — Educação que transforma. Supletivo EJA, Cursos Técnicos e Cursos Livres com certificação reconhecida, 100% online e no seu ritmo.')) ?>">
  <meta name="theme-color" content="#044928">
  <title><?= e(config('seo_titulo', 'EDUSET · Educação que transforma vidas')) ?></title>

  <link rel="icon" href="assets/img/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" href="assets/img/favicon.png">
  <link rel="apple-touch-icon" href="assets/img/favicon.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div id="app">

  <!-- ===================== HEADER ===================== -->
  <header class="header" :class="{ scrolled: scrolled }">
    <div class="container header__inner">
      <a href="#home" class="brand">
        <!-- negativa sobre o hero escuro, colorida quando o header fica branco -->
        <img class="brand__neg" src="assets/img/eduset-negativo.png" alt="EDUSET">
        <img class="brand__cor" src="assets/img/eduset.png" alt="EDUSET">
      </a>

      <nav class="nav" :class="{ open: menuOpen }" @click="menuOpen = false">
        <a href="#home">Início</a>
        <a href="#categorias">Modalidades</a>
        <a href="#cursos">Cursos</a>
        <a href="#diferenciais">Diferenciais</a>
        <a href="#contato">Contato</a>
      </nav>

      <div class="header__cta">
        <a href="#cursos" class="btn btn-outline">Ver cursos</a>
        <a href="#contato" class="btn btn-primary">Matricule-se <i class="ri-arrow-right-line"></i></a>
        <button class="nav-toggle" @click="menuOpen = !menuOpen" aria-label="Menu">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </header>

  <!-- ===================== HERO ===================== -->
  <?php $heroImg = urlImagem(config('hero_imagem')); ?>
  <section class="hero" id="home">
    <div class="container hero__grid">
      <div class="hero__content">
        <span class="hero__badge"><span class="dot"></span>
          <?php if ($polo): ?>
            Unidade <?= e($polo['nome']) ?>
          <?php else: ?>
            <?= e(config('hero_badge', 'Matrículas abertas · Turmas ' . $ano)) ?>
          <?php endif; ?>
        </span>
        <h1>
          <?= e(config('hero_titulo', 'Seu futuro começa com uma')) ?>
          <?php if (config('hero_destaque')): ?><span class="hl"><?= e(config('hero_destaque')) ?></span><?php endif; ?>
        </h1>
        <p class="lead">
          <?= e(config('hero_subtitulo', 'EJA, Cursos Técnicos e Cursos Livres reconhecidos. Estude 100% online, no seu tempo e de onde estiver.')) ?>
        </p>
        <div class="hero__actions">
          <a href="#contato" class="btn btn-primary">Matricule-se agora <i class="ri-arrow-right-line"></i></a>
          <a href="#cursos" class="btn btn-ghost-light">Conheça os cursos</a>
        </div>
        <ul class="hero__feats">
          <li><i class="ri-medal-line"></i><div><strong>Certificado válido</strong><span>em todo Brasil</span></div></li>
          <li><i class="ri-user-star-line"></i><div><strong>Professores</strong><span>especializados</span></div></li>
          <li><i class="ri-time-line"></i><div><strong>Estude no seu</strong><span>tempo</span></div></li>
          <li><i class="ri-service-line"></i><div><strong>Suporte</strong><span>humanizado</span></div></li>
        </ul>
      </div>

      <div class="hero__visual">
        <div class="glow"></div>
        <div class="hero__media<?= $heroImg === '' ? ' hero__media--vazio' : '' ?>">
          <?php if ($heroImg !== ''): ?>
            <img src="<?= e($heroImg) ?>&w=1200" alt="Estude na EDUSET">
          <?php else: ?>
            <img class="hero__media-logo" src="assets/img/eduset-negativo.png" alt="EDUSET">
          <?php endif; ?>
        </div>
        <div class="hero__chip hero__chip--1"><i class="ri-award-fill"></i> Certificado reconhecido</div>
        <div class="hero__chip hero__chip--2"><i class="ri-play-circle-fill"></i> Aulas 100% online</div>
        <div class="hero__chip hero__chip--3"><i class="ri-wifi-line"></i> Estude de onde estiver</div>
      </div>
    </div>

    <div class="container">
      <div class="hero__stats">
        <div class="stat"><i class="ri-graduation-cap-line"></i><div><strong><?= e(config('stat_alunos', '12.000+')) ?></strong><span>Alunos matriculados em todo Brasil</span></div></div>
        <div class="stat"><i class="ri-book-open-line"></i><div><strong><?= e(config('stat_cursos', '80+')) ?></strong><span>Cursos disponíveis para você</span></div></div>
        <div class="stat"><i class="ri-star-line"></i><div><strong><?= e(config('stat_satisfacao', '98%')) ?></strong><span>Taxa de satisfação dos alunos</span></div></div>
      </div>
    </div>

    <div class="hero__wave">
      <svg viewBox="0 0 1440 80" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
        <path fill="#ffffff" d="M0,40 C360,90 720,0 1080,30 C1260,45 1380,60 1440,50 L1440,80 L0,80 Z"></path>
      </svg>
    </div>
  </section>

  <!-- ===================== FRASE ===================== -->
  <?php if (config('frase_transformacao')): ?>
  <section class="frase-band">
    <div class="container">
      <p><?= e(config('frase_transformacao')) ?></p>
    </div>
  </section>
  <?php endif; ?>

  <!-- ===================== CATEGORIAS ===================== -->
  <section class="section" id="categorias">
    <div class="container">
      <div class="section-head" data-reveal>
        <span class="eyebrow">Modalidades</span>
        <h2>Escolha o caminho ideal para <span class="gradient-text">o seu objetivo</span></h2>
        <p>Três modalidades pensadas para diferentes momentos da sua vida acadêmica e profissional.</p>
      </div>

      <div class="cat-grid">
        <div class="cat-card" data-reveal @click="filtrar('eja')" style="cursor:pointer">
          <div class="ic"><i class="ri-book-open-line"></i></div>
          <h3>Supletivo EJA</h3>
          <p>Conclua o Ensino Fundamental ou Médio de forma rápida, flexível e com certificação reconhecida pelo MEC.</p>
          <div class="tags"><span>Ensino Fundamental</span><span>Ensino Médio</span><span>EAD</span></div>
          <span class="more">Ver cursos <i class="ri-arrow-right-line"></i></span>
        </div>

        <div class="cat-card" data-reveal @click="filtrar('tecnico')" style="cursor:pointer">
          <div class="ic"><i class="ri-tools-line"></i></div>
          <h3>Curso Técnico</h3>
          <p>Forme-se em uma profissão em alta no mercado com cursos técnicos práticos e reconhecidos nacionalmente.</p>
          <div class="tags"><span>Enfermagem</span><span>Administração</span><span>TI</span></div>
          <span class="more">Ver cursos <i class="ri-arrow-right-line"></i></span>
        </div>

        <div class="cat-card" data-reveal @click="filtrar('livre')" style="cursor:pointer">
          <div class="ic"><i class="ri-lightbulb-flash-line"></i></div>
          <h3>Curso Livre</h3>
          <p>Atualize-se e desenvolva novas habilidades com cursos livres de curta duração e certificado imediato.</p>
          <div class="tags"><span>Marketing</span><span>Excel</span><span>Design</span></div>
          <span class="more">Ver cursos <i class="ri-arrow-right-line"></i></span>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== CURSOS ===================== -->
  <section class="section section--soft" id="cursos">
    <div class="container">
      <div class="section-head" data-reveal>
        <span class="eyebrow">Catálogo</span>
        <h2>Encontre o curso <span class="gradient-text">perfeito para você</span></h2>
        <p>Filtre por modalidade e comece a estudar hoje mesmo.</p>
      </div>

      <div class="filter-bar">
        <button :class="{active: filtro==='todos'}" @click="filtro='todos'">Todos</button>
        <button :class="{active: filtro==='eja'}" @click="filtro='eja'">Supletivo EJA</button>
        <button :class="{active: filtro==='tecnico'}" @click="filtro='tecnico'">Curso Técnico</button>
        <button :class="{active: filtro==='livre'}" @click="filtro='livre'">Curso Livre</button>
      </div>

      <div class="course-grid">
        <div v-if="carregando" class="empty">Carregando cursos…</div>
        <div v-else-if="cursosFiltrados.length === 0" class="empty">Nenhum curso encontrado nesta modalidade.</div>

        <article class="course-card" v-for="c in cursosFiltrados" :key="c.id">
          <a class="course-card__link" :href="'curso.php?id=' + (c.slug || c.id)">
          <div class="course-card__media" :style="{ background: c.cor }">
            <img v-if="c.imagem" class="course-card__capa" :src="c.imagem" :alt="c.nome" loading="lazy">
            <span v-else class="emoji">{{ c.emoji }}</span>
            <span class="course-card__badge">{{ c.categoriaLabel }}</span>
            <span v-if="c.desconto" class="course-card__off">-{{ c.desconto }}%</span>
          </div>
          <div class="course-card__body">
            <h4>{{ c.nome }}</h4>
            <p>{{ c.descricao }}</p>
            <div class="course-card__meta">
              <span><i class="ri-time-line"></i> {{ c.duracao }}</span>
              <span><i class="ri-computer-line"></i> {{ c.modalidade }}</span>
            </div>
            <div class="course-card__foot">
              <div class="course-card__price">
                <small>
                  <s v-if="c.precoDe">R$ {{ c.precoDe }}</s>
                  <template v-else>a partir de</template>
                </small>
                <strong>
                  <em v-if="c.parcelas">{{ c.parcelas }}x</em>
                  R$ {{ c.preco }}<span>/mês</span>
                </strong>
              </div>
              <span class="btn btn-primary" style="padding:10px 18px;font-size:14px">Quero <i class="ri-arrow-right-line"></i></span>
            </div>
          </div>
          </a>
        </article>
      </div>
    </div>
  </section>

  <!-- ===================== DIFERENCIAIS ===================== -->
  <section class="section" id="diferenciais">
    <div class="container">
      <div class="section-head" data-reveal>
        <span class="eyebrow">Por que EDUSET</span>
        <h2>Uma experiência de ensino <span class="gradient-text">feita para você</span></h2>
      </div>
      <div class="feat-grid">
        <div class="feat" data-reveal>
          <div class="ic"><i class="ri-award-line"></i></div>
          <h4>Certificado reconhecido</h4>
          <p>Diplomas e certificados válidos em todo o território nacional.</p>
        </div>
        <div class="feat" data-reveal>
          <div class="ic"><i class="ri-time-line"></i></div>
          <h4>Estude no seu ritmo</h4>
          <p>Plataforma 100% online, disponível 24h por dia, onde você estiver.</p>
        </div>
        <div class="feat" data-reveal>
          <div class="ic"><i class="ri-user-star-line"></i></div>
          <h4>Suporte dedicado</h4>
          <p>Equipe pedagógica e tutores acompanham você em toda a jornada.</p>
        </div>
        <div class="feat" data-reveal>
          <div class="ic"><i class="ri-money-dollar-circle-line"></i></div>
          <h4>Preço que cabe no bolso</h4>
          <p>Mensalidades acessíveis e condições especiais de matrícula.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== CTA FAIXA ===================== -->
  <section class="section" style="padding-top:0">
    <div class="container">
      <div class="cta-band" data-reveal>
        <h2>Dê o próximo passo na sua carreira</h2>
        <p>Milhares de alunos já transformaram suas vidas com a EDUSET. Agora é a sua vez.</p>
        <a href="#contato" class="btn btn-light">Fazer minha matrícula <i class="ri-arrow-right-line"></i></a>
      </div>
    </div>
  </section>

  <!-- ===================== DEPOIMENTOS ===================== -->
  <section class="section section--soft">
    <div class="container">
      <div class="section-head" data-reveal>
        <span class="eyebrow">Depoimentos</span>
        <h2>Histórias de quem <span class="gradient-text">estudou conosco</span></h2>
      </div>
      <div class="testi-grid">
        <div class="testi" data-reveal>
          <div class="stars"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i></div>
          <p>“Consegui concluir o Ensino Médio pelo EJA em poucos meses. O suporte foi incrível e o certificado saiu rapidinho!”</p>
          <div class="who"><div class="av">MR</div><div><strong>Maria Ribeiro</strong><span>Supletivo EJA</span></div></div>
        </div>
        <div class="testi" data-reveal>
          <div class="stars"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i></div>
          <p>“Fiz o curso técnico em Enfermagem e hoje já estou trabalhando na área. Recomendo demais a EDUSET!”</p>
          <div class="who"><div class="av">JS</div><div><strong>João Silva</strong><span>Técnico em Enfermagem</span></div></div>
        </div>
        <div class="testi" data-reveal>
          <div class="stars"><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i><i class="ri-star-fill"></i></div>
          <p>“Os cursos livres me ajudaram a evoluir no trabalho. Conteúdo direto ao ponto e com certificado na hora.”</p>
          <div class="who"><div class="av">AC</div><div><strong>Ana Costa</strong><span>Marketing Digital</span></div></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== CONTATO ===================== -->
  <section class="section" id="contato">
    <div class="container contact-grid">
      <div class="contact-info" data-reveal>
        <span class="eyebrow" style="display:inline-block;font-size:13px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--green-600);background:var(--bg-softer);padding:6px 16px;border-radius:100px;margin-bottom:16px">Fale conosco</span>
        <h2>Pronto para começar? <span class="gradient-text">Vamos conversar</span></h2>
        <p>Preencha o formulário e um de nossos consultores entrará em contato para tirar todas as suas dúvidas e ajudar na sua matrícula.</p>

        <div class="line"><div class="ic"><i class="ri-whatsapp-line"></i></div><div><strong>WhatsApp</strong><span><?= e(config('telefone_exibicao', '(00) 00000-0000')) ?></span></div></div>
        <div class="line"><div class="ic"><i class="ri-mail-line"></i></div><div><strong>E-mail</strong><span><?= e(config('email_contato', 'contato@eduset.com.br')) ?></span></div></div>
        <div class="line"><div class="ic"><i class="ri-map-pin-line"></i></div><div><strong>Atendimento</strong><span><?= e(config('horario_atendimento', 'Segunda a sexta, das 8h às 18h')) ?></span></div></div>
      </div>

      <form class="contact-form" data-reveal @submit.prevent="enviar">
        <div class="form-alert" :class="feedback.tipo" v-if="feedback.msg">{{ feedback.msg }}</div>

        <div class="field">
          <label>Nome completo</label>
          <input type="text" v-model="form.nome" placeholder="Seu nome" required>
        </div>
        <div class="field">
          <label>E-mail</label>
          <input type="email" v-model="form.email" placeholder="voce@email.com" required>
        </div>
        <div class="field">
          <label>WhatsApp</label>
          <input type="tel" v-model="form.telefone" placeholder="(00) 00000-0000" required>
        </div>
        <div class="field">
          <label>Modalidade de interesse</label>
          <select v-model="form.interesse" required>
            <option value="">Selecione…</option>
            <option value="Supletivo EJA">Supletivo EJA</option>
            <option value="Curso Técnico">Curso Técnico</option>
            <option value="Curso Livre">Curso Livre</option>
          </select>
        </div>
        <div class="field">
          <label>Mensagem (opcional)</label>
          <textarea v-model="form.mensagem" placeholder="Como podemos ajudar?"></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center" :disabled="enviando">
          {{ enviando ? 'Enviando…' : 'Enviar mensagem' }} <i class="ri-send-plane-line"></i>
        </button>
      </form>
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
            <li><a href="#cursos">Supletivo EJA</a></li>
            <li><a href="#cursos">Curso Técnico</a></li>
            <li><a href="#cursos">Curso Livre</a></li>
          </ul>
        </div>
        <div>
          <h5>Institucional</h5>
          <ul>
            <li><a href="#categorias">Sobre nós</a></li>
            <li><a href="#diferenciais">Diferenciais</a></li>
            <li><a href="#contato">Contato</a></li>
          </ul>
        </div>
        <div>
          <h5>Atendimento</h5>
          <ul>
            <li><a href="#contato">Central do aluno</a></li>
            <li><a href="#contato">Fale conosco</a></li>
            <li><a href="#contato">WhatsApp</a></li>
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
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/avisos.js"></script>
</body>
</html>
