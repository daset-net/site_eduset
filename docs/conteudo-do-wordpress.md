# Conteúdo extraído do site antigo (eduset.com.br em WordPress)

Levantamento feito em **25/07/2026**, enquanto `eduset.com.br` ainda rodava o WordPress, para
alimentar este site novo. Fonte: API REST do WordPress (`/wp-json/wp/v2/`), sitemaps e o HTML
das páginas publicadas.

O que já foi **aplicado** está marcado; o resto fica aqui como material de referência para quando
as páginas institucionais forem construídas.

---

## 1. Identidade e posicionamento

O EDUSET se apresenta como **edtech e marketplace educacional** — conecta alunos a cursos de
instituições parceiras, em vez de se apresentar só como escola. É uma diferença real de discurso
em relação à EDUALFA, e vale manter nos textos.

| Item | Valor |
|---|---|
| Slogan da logo | Seu futuro, seu sucesso! |
| Razão social | EDUSET SOLUCOES E TECNOLOGIA INOVA SIMPLES (I.S.) |
| CNPJ | 58.620.468/0001-69 |
| Sede | Condomínio Via Alameda — Av. Salgado Filho, 2120, sala 2112 C, Centro, Guarulhos/SP, 07115-000 |
| Modalidades no site antigo | EJA Supletivo · Técnico · Profissionalizante · Pós-Graduação |
| Rodapé | "Desenvolvido e mantido por: EDUSET SOLUCOES E TECNOLOGIA INOVA SIMPLES (I.S.)" |

**Quem somos** — *aplicado em `institucional_quem_somos`*

> O EDUSET é uma edtech inovadora e marketplace educacional que conecta estudantes a cursos de EJA,
> Ensino Superior Sequencial e Pós-Graduação, todos autorizados e reconhecidos pelo MEC. Com um
> ambiente digital robusto, oferecemos uma experiência de ensino flexível, personalizada e acessível
> a todo o Brasil — ideal para quem estuda no próprio ritmo, com momentos presenciais apenas se
> necessário.

**Missão** — *aplicado em `institucional_missao`*

> Capacitar jovens e adultos por meio de formação de excelência, integrando tecnologia, inovação e
> parcerias estratégicas.

**Visão** — *aplicado em `institucional_visao`*

> Ser referência nacional em educação EAD e marketplace educacional, transformando vidas por meio de
> ensino de qualidade e alcance democrático.

**Por que escolher o EDUSET** (lista original da página Institucional)

- Cursos reconhecidos: total conformidade com as diretrizes do MEC
- Flexibilidade EAD: estude de onde estiver, no seu ritmo
- Excelência acadêmica: professores qualificados e conteúdos atualizados
- Formação valorizada: diplomas com validade nacional e aceitação no mercado de trabalho
- Inovação pedagógica: metodologias dinâmicas e interativas

**Os 4 blocos da home antiga** ("Acelere seu crescimento pessoal e profissional") — *aplicados em
`diferencial_1` a `diferencial_4`, no formato `Título|Texto`*

1. **Cursos autorizados pelo MEC** — Todos os cursos ofertados através de nosso Marketplace educacional são autorizados pelo MEC, garantindo ao aluno um aprendizado com excelência.
2. **Professores entusiasmados** — Aprender com quem ama estudar e ensinar é muito inspirador. No nosso Marketplace educacional a maioria dos professores são Mestres e Doutores e altamente qualificados para preparar os alunos em suas áreas do conhecimento.
3. **Você estuda no seu ritmo** — Cursos na modalidade EAD, onde a flexibilidade do tempo conta. O aluno estuda e aprende de onde estiver. Havendo atividades presenciais o aluno as desenvolve no local onde mora.
4. **Mais de milhares de alunos em todo Brasil** — O nosso Marketplace educacional já ajudou milhares de pessoas a realizar seus sonhos, por meio de uma Educação humana e de qualidade.

---

## 2. Contato — *aplicado*

| Chave | Valor adotado | Observação |
|---|---|---|
| `email_contato` | contato@eduset.com.br | a página Institucional cita **suporte@eduset.com.br**; escolhido o do "Fale Conosco" |
| `whatsapp` | 5512997640917 | |
| `telefone_exibicao` | (12) 99764-0917 | |
| `horario_atendimento` | Segunda a sexta, das 9h às 21h | o "Fale Conosco" dizia **9h às 18h**; adotado o do rodapé e da Institucional (9h–21h) |
| `instagram` | https://www.instagram.com/eduset_oficial | |

Os três indicadores da home (`stat_alunos`, `stat_cursos`, `stat_satisfacao`) ficaram como estão —
"Milhares", "60+" e "98% de satisfação". O 98% veio da EDUALFA e não tem lastro no material do
EDUSET; foi mantido por decisão do dono do site.

Redes sociais: **o site antigo não linkava nenhuma rede** em página alguma, embora o formulário do
rodapé perguntasse "Como conheceu?" com as opções *Google, Instagram, Facebook, Indicação*.

O perfil foi informado depois e já está no ar: `instagram` = <https://www.instagram.com/eduset_oficial>.
`facebook` e `youtube` seguem vazios — quando forem preenchidos, os ícones aparecem sozinhos no rodapé.

---

## 3. Rede de unidades — **material novo, ainda não usado no site**

O WordPress tinha uma seção "Rede de Unidades / Cobertura Nacional" com uma página por cidade,
trazendo endereço, código de autorização e horários. Isso **não existe** no site novo e é a maior
oportunidade de enriquecimento: prova física de presença nacional.

- Todas são **"Unidade Remota"**, com o mesmo horário: **segunda a sexta, 09:00–12:00 e 13:00–18:00**.
- Os dados brutos estão em [`unidades-wordpress.json`](unidades-wordpress.json).
- O `tabela_unidades` do Directus tem 29 linhas com as unidades EAD, mas **sem endereço** — os
  endereços abaixo são o que falta lá.

| Cidade / Estado | Código | Endereço |
|---|---|---|
| Águas Claras — Distrito Federal | 1327 | Avenida Castanheiras, Lote 1310/1370, Lj. 25 |
| Belo Horizonte — Minas Gerais | 1381 | Rua Manoel Elias De Aguiar, 22, Loja 13 |
| Boa Vista — Roraima | 1405 | Av. Nazaré Filgueiras, 3046-C, 69318-186 |
| Camaçari — Bahia | 1325 | Avenida Eixo Urbano Central, 38 |
| Campo Grande — Mato Grosso do Sul | 1417 | Avenida Mascarenhas De Moraes, 2980, Monte Castelo |
| Cuiabá — Mato Grosso | 1413 | Rua Benjamim Pedroso Da Silva, 146, Quadra 21 |
| Curitiba — Paraná | 1387 | Avenida Marechal Floriano Peixoto, 612 |
| Florianópolis — Santa Catarina | 1407 | Rua Felipe Schmidt, 316, Sala 202 |
| Fortaleza — Ceará | 1283 | Avenida João Pessoa, 3965, Térreo |
| Goiânia — Goiás | 1329 | Avenida 85, 2162, Qd. G-21 / Lt 29 |
| João Pessoa — Paraíba | 1385 | Rua Empresário João Rodrigues Alves, 125, Loja 107a |
| Macapá — Amapá | 1319 | Rua Adilson José Pinto Pereira, 669, Sala 3 |
| Maceió — Alagoas | 1321 | Rua Rita De Cassia, 36, Quadra 08 Lote 04 Sala 030 |
| Manaus — Amazonas | 1323 | Avenida Autaz Mirim, 5224 |
| Natal — Rio Grande do Norte | 1422 | Avenida Dos Xavantes, 1095, Loja 04 |
| Palmas — Tocantins | 1425 | Quadra Acno 1, Rua De Pedestre nº 4, 5 |
| Belém — Pará | 1383 | Travessa São Roque, 319, Sala 05 |
| Porto Alegre — Rio Grande do Sul | 1420 | Avenida Edgar Pires De Castro, 1940, Lojas 9 e 10 |
| Porto Velho — Rondônia | 1402 | Rua Do Cravo, 2708 |
| Recife — Pernambuco | 1415 | Avenida Conde Da Boa Vista, 1147 |
| Rio Branco — Acre | 1317 | Avenida Ceará, 803, Sala 02 |
| Rio de Janeiro — Rio de Janeiro | 1393 | Rua Amaral Costa, 333, Loja B |
| Aracaju — Sergipe | 1409 | Rua Maruim, 95, Salas 05 a 08, Térreo |
| São Gabriel do Oeste — Mato Grosso | 1378 | Avenida Mato Grosso Do Sul, 1610 |
| São Luís — Maranhão | 1338 | Rua Das Mitras, 10, Loja 08, Piso Térreo |
| São Paulo — São Paulo | 1427 | Avenida Paulista, 1498, Conj. 1498 Loja 48, Edif. D. Andrea Matarazzo |
| Teresina — Piauí | 1399 | Rua Luiz Antonio De Sousa, 13, Quadra 25, Conjunto Parque Piauí |

**Texto da página de unidade** (igual em todas, serve de base para uma página "Unidades"):

> Na Eduset, cada unidade é muito mais do que um local de estudos — é um centro de transformação de
> vidas. Com uma proposta moderna de ensino e foco total no aluno, oferecemos cursos técnicos
> reconhecidos pela qualidade, acessibilidade e conexão direta com o mercado de trabalho.
> Presente em diversas regiões, a Eduset se destaca por oferecer infraestrutura acolhedora, suporte
> personalizado e tecnologia educacional de ponta.

---

## 4. Páginas institucionais que o site novo ainda não tem

O WordPress tinha um menu institucional fixo no topo e no rodapé:

| Página | Situação no site novo |
|---|---|
| Institucional | textos já salvos no Directus, falta a página |
| Termos de Uso | **não portado** — texto completo no WordPress |
| Política de Privacidade | **não portado** — texto completo no WordPress |
| Fale Conosco | parcialmente coberto pela seção de contato da home |
| Unidades | **não portado** — dados na seção 3 |
| Contrato Digital | contrato de prestação de serviços, texto completo |

O **Contrato Digital** do WordPress identificava outra empresa —
*EDUSET TECNOLOGIA EDUCACIONAL LTDA, CNPJ 52.341.668/0001-00* — diferente do rodapé e da página
Institucional. **Decisão tomada:** o CNPJ do site é o
**58.620.468/0001-69 (EDUSET SOLUCOES E TECNOLOGIA INOVA SIMPLES)**, que é também o que está no
`avaset_configuracoes`. Se o Contrato Digital for portado, precisa ser atualizado para esse CNPJ.

Pontos do contrato que valem virar FAQ no site novo:

- Reembolso integral em até **7 dias** após a confirmação da matrícula; depois disso, sem reembolso.
- Certificado dos cursos próprios em até **90 dias** após a conclusão.
- Cursos de parceiros: certificado emitido pela instituição parceira, sem declaração de matrícula.
- Foro: comarca de Guarulhos/SP.

---

## 5. Imagens aproveitadas

Salvas em [`../logo/wordpress/`](../logo/wordpress):

| Arquivo | Uso sugerido |
|---|---|
| `capa_promocao_eduset_computador.jpg` (1340×383) | banner do hero em desktop — "Cursos em Alta! Conquiste seu diploma · Técnico EAD a partir de R$ 124,90/mês" |
| `capa_promocao_eduset_celular.jpg` (1000×1000) | versão mobile do mesmo banner |
| `cropped-logo-eduset-icone-192x192.png` | ícone quadrado da marca |

As logos principais (`logo/eduset_normal.png` e `logo/eduset_negativo.png`) já vieram do WordPress
e estão em uso no site.

> Atenção: o banner promocional traz **preço fixo ("a partir de R$ 124,90/mês")**. O site novo calcula
> preço em tempo real a partir do `ava_catalogo_curso`, então usar essa arte no hero cria risco de o
> banner contradizer a vitrine. Se for usar, vale pedir uma versão sem preço.

---

## 6. Cursos

O WordPress tinha 26 páginas de curso (Técnico, Profissionalizante, EJA e Pós). **Não foram
portadas de propósito**: no site novo o conteúdo de curso vive em `site_catalogo_cursos` no
Directus, e o catálogo de preços vem do `ava_catalogo_curso`.

Fica registrado que o site antigo divulgava **Pós-Graduação** e **Superior Sequencial** — duas
modalidades que o catálogo do AVASET hoje não tem. Se forem voltar, precisam entrar no catálogo
antes de aparecer no site.
