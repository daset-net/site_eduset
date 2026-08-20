<?php
// api/_conteudo.php — texto de reserva da página do curso.
//
// O conteúdo de cada curso (chamada, promessa, o que aprende, público, saídas
// e argumento de mercado) é editado no Directus, na coleção site_catalogo_cursos.
// O que sobrou aqui é só a rede de segurança: se um curso novo aparecer no
// catálogo de preços antes de alguém escrever o texto dele, a página ainda abre
// com um conteúdo coerente da modalidade em vez de ficar vazia.

$CONTEUDO_PADRAO = [
  'eja' => [
    'chamada'  => 'Conclua seus estudos e destrave a sua próxima fase',
    'promessa' => 'Estude 100% online, no seu ritmo, e conquiste o certificado que falta para você concorrer às vagas, ao concurso ou à faculdade que quiser.',
    'aprender' => [
      'Conteúdo em módulos curtos, em linguagem feita para adulto',
      'Material acessível pelo celular a qualquer hora',
      'Provas online com quantas tentativas você precisar',
      'Tutoria para tirar dúvidas do começo ao fim',
    ],
    'publico' => ['Quem parou de estudar e quer retomar', 'Quem precisa do certificado para trabalhar ou estudar'],
    'saidas'  => ['Certificado com validade nacional', 'Acesso a faculdade, técnico e concursos'],
    'mercado' => 'Escolaridade completa é o primeiro filtro da maioria das seleções de emprego do país.',
  ],
  'tecnico' => [
    'chamada'  => 'Uma profissão de verdade, sem passar anos na faculdade',
    'promessa' => 'Formação técnica é o caminho mais curto entre onde você está e uma carteira assinada melhor — com conteúdo prático e certificado reconhecido.',
    'aprender' => [
      'Conteúdo técnico aplicado ao dia a dia da profissão',
      'Material online completo, disponível 24 horas',
      'Avaliações por módulo com correção imediata',
      'Tutoria especializada durante todo o curso',
    ],
    'publico' => ['Quem quer mudar de área', 'Quem quer crescer na empresa onde já está'],
    'saidas'  => ['Vagas técnicas no mercado formal', 'Concursos de nível técnico', 'Trabalho autônomo na área'],
    'mercado' => 'Falta técnico qualificado em praticamente todos os setores produtivos do Brasil.',
  ],
  'tecnico-competencia' => [
    'chamada'  => 'Transforme sua experiência em uma certificação técnica',
    'promessa' => 'Avance módulo a módulo realizando somente as provas que comprovam as competências que você já domina.',
    'aprender' => [
      'Prova online específica em cada módulo',
      'Avaliação objetiva dos conhecimentos da área',
      'Acompanhamento do resultado diretamente pelo AVA',
      'Progressão pelos módulos conforme o calendário do curso',
    ],
    'publico' => ['Quem já possui experiência ou conhecimento na área', 'Quem busca comprovar competências profissionais'],
    'saidas'  => ['Certificação técnica da área escolhida', 'Valorização da experiência profissional', 'Novas oportunidades no mercado'],
    'mercado' => 'A certificação formal ajuda a transformar experiência prática em novas oportunidades profissionais.',
  ],
  'livre' => [
    'chamada'  => 'Aprenda rápido, aplique amanhã e melhore o seu currículo',
    'promessa' => 'Curso livre é objetivo: conteúdo direto, certificado na conclusão e uma habilidade nova que você já leva para a próxima entrevista ou para o próprio negócio.',
    'aprender' => [
      'Conteúdo prático, sem teoria desnecessária',
      'Aulas curtas que cabem na sua rotina',
      'Acesso pelo celular, tablet ou computador',
      'Certificado de conclusão ao final',
    ],
    'publico' => ['Quem quer se qualificar rápido', 'Quem busca renda extra ou recolocação'],
    'saidas'  => ['Novas vagas e funções', 'Renda extra como autônomo', 'Diferencial no currículo'],
    'mercado' => 'Qualificação curta é a forma mais rápida de sair da lista de descartados numa seleção.',
  ],
];

/**
 * Conteúdo editorial de um curso: o que veio do Directus, completado pelo
 * texto padrão da modalidade nos campos que ainda estiverem em branco.
 */
function conteudoCurso(array $curso): array {
  global $CONTEUDO_PADRAO;
  $padrao = $CONTEUDO_PADRAO[$curso['categoria']] ?? $CONTEUDO_PADRAO['livre'];

  // A ficha editorial é compartilhada pelo técnico comum e pela versão por
  // competência. Nesta última, o texto próprio evita prometer aulas e materiais.
  if (($curso['categoria'] ?? '') === 'tecnico-competencia') return $padrao;

  $texto = fn(string $campo) => trim((string) ($curso[$campo] ?? '')) !== ''
    ? $curso[$campo]
    : $padrao[$campo];

  $lista = fn(string $campo) => !empty($curso[$campo]) && is_array($curso[$campo])
    ? $curso[$campo]
    : $padrao[$campo];

  return [
    'chamada'  => $texto('chamada'),
    'promessa' => $texto('promessa'),
    'mercado'  => $texto('mercado'),
    'aprender' => $lista('aprender'),
    'publico'  => $lista('publico'),
    'saidas'   => $lista('saidas'),
  ];
}
