# EDUSET — Site institucional

Site moderno e responsivo da **EDUSET**, construído com **HTML, CSS, JavaScript, PHP** e **Vue 3 (via CDN)**, servido por **OpenLiteSpeed + LSPHP** (extremamente rápido) em contêiner Docker.

Paleta visual baseada na logo da marca: azul-marinho, azul royal e ciano (a identidade visual definitiva da EDUSET será ajustada numa etapa seguinte).

## ✨ Recursos

- **Design moderno e responsivo** (mobile-first), com animações suaves.
- **3 modalidades de cursos:** Supletivo EJA, Curso Técnico e Curso Livre.
- **Catálogo dinâmico** com filtro por modalidade (Vue 3), alimentado pelo **Directus**,
  com preços e descontos reais.
- **Conteúdo editável no Directus**: textos, imagens de capa, contatos e SEO são
  administrados nas coleções do site — sem precisar mexer no código.
- **Página de conversão por curso** (`/curso.php?id=tecnico-em-administracao`):
  argumento de matrícula, **grade curricular vinda da plataforma**, público,
  saídas profissionais, oferta com desconto, FAQ e formulário. Aceita o slug ou
  o código do curso.
- **Matrícula online** na própria página do curso: o aluno preenche os dados e a
  matrícula é criada no AVASET na hora, com número e credenciais na tela.
- **API PHP** para catálogo (`/api/cursos.php`), matrícula (`/api/matricula.php`)
  e formulário de contato (`/api/contato.php`).
- **Páginas de unidade** (`/unidades.php` e `/unidade.php?id=<código>`): os polos
  vindos da `tabela_unidades`, com busca por cidade e matrícula pela unidade.
- **Painel próprio** em `/admin`, com o **mesmo login do `ead.eduset.com.br`**
  (sem cadastro nem senha separada), para trocar capas e editar textos.
- **Balões de prova social**: avisos de quem acabou de se matricular, com o texto
  escrito no painel.
- **Leads** salvos em CSV persistente (`data/leads.csv`).
- Botão flutuante de WhatsApp.

## 📁 Estrutura

```
site_eduset/
├── Dockerfile              # OpenLiteSpeed + LSPHP
├── .dockerignore
├── public/                 # docroot servido pelo OpenLiteSpeed
│   ├── index.php           # página principal (Vue 3)
│   ├── curso.php           # página de conversão de um curso (?id=CT005)
│   ├── unidades.php        # lista de unidades, agrupada por estado
│   ├── unidade.php         # ficha de uma unidade (?id=centro.aracaju.se)
│   ├── robots.txt          # bloqueia /admin e /api nos buscadores
│   ├── admin/              # painel: login do ead + capas e textos
│   │   ├── index.php       # login (tabela_gestores)
│   │   ├── painel.php      # configurações gerais do site
│   │   ├── cursos.php      # lista dos cursos + envio das capas
│   │   ├── curso.php       # edição dos textos de um curso
│   │   ├── _auth.php       # sessão, CSRF, limite de tentativas
│   │   ├── _dados.php      # escrita no Directus e upload de imagem
│   │   └── admin.css
│   ├── assets/
│   │   ├── css/style.css
│   │   ├── js/app.js       # Vue da home
│   │   ├── js/avisos.js    # balões de "fulano se matriculou"
│   │   ├── js/curso.js     # formulário e header da página do curso
│   │   ├── js/unidades.js  # busca e filtros (estado e tipo) na lista de unidades
│   │   └── img/
│   │       ├── eduset.png            # logo colorida (fundos claros)
│   │       ├── eduset-negativo.png   # logo branca (fundos escuros)
│   │       └── favicon.ico / .png
│   └── api/
│       ├── _catalogo.php   # leitura do Directus + cache (usado por todas as páginas)
│       ├── avisos.php      # inscrições recentes p/ os balões de prova social
│       ├── _conteudo.php   # texto de reserva por modalidade
│       ├── cursos.php      # catálogo em JSON
│       ├── imagem.php      # proxy das capas do Directus (não expõe o token)
│       ├── purgar.php      # limpa o cache sob demanda (chamado pelo AVASET)
│       ├── matricula.php   # matrícula online → AVASET (assina com o token)
│       └── contato.php     # recebe leads → data/leads.csv
└── README.md
```

## 🚀 Deploy no EasyPanel (App via Dockerfile)

1. No EasyPanel, crie um **App** no projeto desejado.
2. Em **Source**, selecione **GitHub** e aponte para o repositório `daset-net/site_eduset`, branch `main`.
3. Em **Build**, escolha **Dockerfile** (o EasyPanel detecta o `Dockerfile` na raiz).
4. Em **Domains/Ports**, publique a **porta 80** do contêiner no domínio desejado.
5. Em **Environment**, configure o acesso ao Directus (a pasta `conexao_eduset/` **não**
   vai para a imagem — está no `.dockerignore`):

   ```
   DIRECTUS_URL=https://cloud.daset.net
   DIRECTUS_TOKEN=<token estático do Directus>
   TOKEN_PURGA_SITE=<segredo compartilhado com o AVASET>
   TOKEN_MATRICULA_EXTERNA=<mesmo api_token_matricula_externa do AVASET>
   ```

   Os dois últimos são **os mesmos valores** já gravados no `avaset_configuracoes`
   do AVASET da EDUSET (chaves `api_token_matricula_externa` e `site_purga_token`).
   Se não baterem, a matrícula pelo site responde 401 e a purga é recusada. Os
   valores em claro estão em `conexao_eduset/conexao_directus_avaset_unico_eduset.txt`
   (fora do Git e da imagem).

   O `TOKEN_PURGA_SITE` é opcional: sem ele, `api/purgar.php` responde 503 e o
   cache só vence pelo tempo. Com ele, o AVASET limpa o cache na hora (veja
   *Cache e resiliência*).

   Localmente, sem essas variáveis, o `cursos.php` lê os valores de
   `conexao_eduset/conexao_directus_avaset_unico_eduset.txt`.
6. (Opcional) Em **Mounts**, adicione um volume persistente montado em
   `/var/www/vhosts/localhost/data` para preservar os leads (`leads.csv`).
7. **Deploy**. O OpenLiteSpeed sobe automaticamente e serve o site na porta 80.

> Painel admin do OpenLiteSpeed disponível na porta **7080** (exponha apenas se necessário).

## 🐳 Rodando localmente

```bash
docker build -t eduset .
docker run -p 8080:80 eduset
# abra http://localhost:8080
```

## 🎨 Personalização

- **Logos:** os originais ficam em `logo/`. As versões web (fundo transparente,
  redimensionadas e otimizadas) ficam em `public/assets/img/`. A **negativa** é
  usada sobre fundos escuros (hero, rodapé e topo do header) e a **colorida**
  sobre fundos claros (header ao rolar). O header alterna entre as duas
  automaticamente via CSS.
- **Cores:** variáveis CSS no topo de `public/assets/css/style.css`.

## 🗄️ Conteúdo no Directus

Quase tudo do site é editado no Directus da EDUSET, **sem mexer no código**.
Três coleções, com papéis bem separados:

| Coleção | Papel |
|---|---|
| `ava_catalogo_curso` | **Preço + quais cursos existem.** Fonte única de valores, parcelas e descontos. O campo `ativo` liga/desliga o curso (controlado no painel do AVASET). |
| `site_catalogo_cursos` | **Camada editorial (opcional).** Imagem de capa, textos, slug e ordem de cada curso. |
| `site_configuracoes` | **Configurações gerais.** Contato, redes sociais, textos da home, números e SEO. |
| `ava_pacote_curso` | **Grade curricular.** Uma linha por matéria do curso (veja *Grade curricular*). |
| `site_alunos_inscricoes_especiais` | **Prova social.** Inscrições mostradas nos balões (veja *Balões de matrícula*). |

> **Quais cursos aparecem no site:** todos os do `ava_catalogo_curso`, **menos os desativados**
> (`ativo = false`). O interruptor fica no painel do AVASET (Catálogo de Cursos) e vale ao mesmo
> tempo para o site e para a matrícula. A ficha em `site_catalogo_cursos` é **opcional**: quando
> existe, dá capa e textos próprios; quando não existe, o curso ainda aparece usando o nome do
> catálogo e um texto padrão da modalidade.

### `site_catalogo_cursos` — uma linha por curso

Ligada ao preço pelo campo `id_curso` (ex.: `CT005`). **Não guarda valores** — o
preço é buscado no `ava_catalogo_curso` na hora da leitura, então alterar um
desconto lá se reflete no site sozinho, sem risco de anunciar preço errado.

- `ativo` — oculta o curso **só do site**, sem tirá-lo da matrícula (para desligar dos dois, use o interruptor no AVASET).
- `ordem` — posição dentro da modalidade.
- `imagem_capa` — capa do card e da página. Sem imagem, o site usa o `emoji`.
- `nome_exibicao`, `descricao_card`, `duracao`, `modalidade`, `slug`.
- `chamada`, `promessa`, `mercado` — texto de conversão da página do curso.
- `aprender`, `publico`, `saidas` — **um item por linha**.
- `seo_titulo`, `seo_descricao`.

Campo em branco cai num padrão sensato da modalidade (`public/api/_conteudo.php`),
então a página nunca abre quebrada.

### `site_configuracoes` — chave/valor

Mesmo formato da `avaset_configuracoes`. Chaves usadas hoje: `whatsapp` (só
dígitos, formato internacional), `telefone_exibicao`, `email_contato`,
`horario_atendimento`, `instagram`, `facebook`, `youtube` (vazio esconde o
ícone), `hero_badge`, `hero_titulo`, `hero_subtitulo`, `stat_alunos`,
`stat_cursos`, `stat_satisfacao`, `seo_titulo`, `seo_descricao`.

Textos longos podem ir em `valor_extendido`, que tem precedência sobre `valor`.

### Cache e resiliência

O catálogo e as configurações ficam **10 minutos em cache** em disco. Se o
Directus ficar fora do ar, o site continua servindo a última versão conhecida em
vez de aparecer vazio.

Edições feitas pelo `/admin` do site limpam o cache na hora. Mudanças feitas no
**AVASET** (GESET → *Catálogo cursos*, ligar/desligar um curso) chegam pela
purga: o AVASET chama `POST /api/purgar.php` com o header `X-Token`, e o site
descarta o cache. Sem o `TOKEN_PURGA_SITE` configurado dos dois lados, a
mudança continua valendo — só demora até 10 minutos para aparecer.

```bash
curl -X POST -H "X-Token: $TOKEN_PURGA_SITE" https://eduset.com.br/api/purgar.php
```

> O cache é um arquivo no disco do contêiner: com mais de uma réplica, a purga
> atinge só a réplica que atendeu a chamada.

## 🏷️ Oferta por ciclo

O `ava_catalogo_curso` guarda a mesma matrícula em vários descontos. O site não
anuncia sempre o mesmo: `ofertaDoCiclo()` gira essa escada por períodos fechados
— um ciclo em 50%, o seguinte em 40%, o outro em 30% — e todos os cursos giram
juntos, como uma campanha só.

> **Bolsa nunca aparece.** As linhas de `ingresso = bolsa` (hoje, os 60%) são
> descartadas em `ehBolsa()` antes de qualquer cálculo: não entram na vitrine,
> no contador nem no formulário. Bolsa é concessão da escola, decidida caso a
> caso no GESET, e o endpoint de matrícula externa do AVASET recusa desconto de
> 60% ou mais vindo de fora — anunciar esse preço seria prometer o que a
> matrícula não entrega.

A página do curso mostra o preço cheio riscado, a economia por parcela e um
**contador para o fim do ciclo**. O prazo é real: quando ele zera, o preço muda
de fato, então nada de contador que reinicia a cada visita.

A mesma função decide a vitrine e a matrícula (`planoVigente()` chama
`ofertaDoCiclo()`), então **o preço exibido é sempre o preço gravado**.

Ajustes na `site_configuracoes`:

| chave | padrão | efeito |
|---|---|---|
| `oferta_modo` | `rotativo` | `fixo` trava no maior desconto disponível |
| `oferta_niveis` | `3` | quantos degraus entram na rotação (3 = 50/40/30) |
| `oferta_ciclo_dias` | `7` | duração do ciclo |
| `oferta_offset` | `0` | desloca a rotação, para escolher em que degrau ela começa |

Os ciclos sempre começam numa segunda-feira, no fuso `America/Fortaleza`.

## 📚 Grade curricular

A página do curso lista as **matérias de verdade**, puxadas da coleção
`ava_pacote_curso` (a mesma que monta o curso na plataforma): nome, ordem e
dias de acesso de cada uma. Nada é digitado à mão — mudou a grade no AVASET,
muda no site na próxima leitura (cache de 10 minutos).

Em vez do prazo de acesso, cada matéria mostra a **carga horária aproximada**,
calculada a partir do conteúdo cadastrado (`ava_pacote_materia` = atividades,
`ava_pacote_prova` = prova, `ava_pacote_anexo` / `pdf_apostila` / `pdf_jornada`):

| chave (`site_configuracoes`) | padrão | entra na conta |
|---|---|---|
| `carga_hora_questao` | `1` | por questão de exercício ou prova |
| `carga_hora_videoaula` | `1` | a vídeo-aula que acompanha cada questão |
| `carga_hora_apostila` | `1` | se a matéria tem apostila |
| `carga_hora_jornada` | `1` | se a matéria tem jornada |
| `carga_hora_podcast` | `10` | o podcast da matéria |
| `carga_horaria_padrao` | `30` | matéria ainda **sem** conteúdo cadastrado |

Ou seja, uma matéria com 10 questões e podcast dá `10×(1+1) + 10 = 30h`. O total
do curso é a soma das matérias e aparece no topo da seção.

### Carga mínima do Catálogo Nacional

Curso técnico tem carga horária mínima definida no **CNCT** (Administração 800h,
a maioria 1200h) e o site não pode anunciar menos. O mínimo de cada curso fica no
campo `carga_horaria_minima` da `site_catalogo_cursos`; sem ele, vale o padrão da
modalidade (`carga_minima_tecnico`, `carga_minima_eja`, `carga_minima_livre`).

O site anuncia sempre **um pouco acima** do mínimo — `carga_margem_percentual`
(padrão `5`) diz quanto. As horas contadas do conteúdo dão o **peso relativo** de
cada matéria e a lista é ajustada proporcionalmente até o total cair nesse alvo:
para cima quando o conteúdo cadastrado é pouco, para baixo quando é muito. A
proporção entre as matérias não muda e o total continua sendo a soma da lista.

Curso **livre** não tem mínimo legal (`carga_minima_livre = 0`): mostra a soma
real, sem ajuste.

> Enquanto as atividades não estiverem cadastradas no Directus da escola, quase
> tudo cai no `carga_horaria_padrao`. Assim que elas entram, o número passa a
> variar por matéria sozinho.

O elo é o `id_curso`, **conferido pelo nome**: em alguns pacotes antigos o
mesmo código aponta para cursos diferentes nas duas tabelas (`CT008` é Meio
Ambiente no catálogo e Estética no pacote). Se os nomes não baterem,
`materiasDoCurso()` procura a grade pelo nome do curso; não achando, a seção
simplesmente não aparece — melhor não mostrar matéria nenhuma do que mostrar a
do curso errado.

## 🔔 Balões de matrícula (prova social)

De tempos em tempos aparece um balão no canto da tela — *"Fulana, de Sorocaba -
SP, se matriculou no curso EJA Ensino Médio"* — para o visitante ver que outras
pessoas estão comprando. Vale na home e na página do curso.

Os nomes vêm da coleção **`site_alunos_inscricoes_especiais`** do Directus (uma
linha por inscrição: `nome`, `curso`, `id_curso`, `cidade`, `estado`,
`visto_por_ultimo`). O `api/avisos.php` monta a lista — com o mesmo cache de 10
minutos do catálogo — e o `assets/js/avisos.js` faz o rodízio. Cada visitante
começa num ponto diferente da lista, então dois visitantes não veem a mesma
sequência. O nome da tabela sai da chave `site_alunos_inscricoes_especiais` da
`site_configuracoes` — trocar a tabela não exige mexer no código.

**Só aparece curso à venda.** O `id_curso` da inscrição é conferido contra o
catálogo do site (que já vem sem os cursos desativados no AVASET): id que não
está lá — curso desligado, combo que saiu de linha — some do rodízio sozinho.
Como a tabela tem milhares de linhas, o site sorteia uma **janela de 60** a cada
vez que o cache vence, em vez de repetir sempre as mesmas pessoas.

Casado o `id_curso`, o balão ganha a **capa (ou o emoji) do curso** e vira link
para a página de conversão dele. O nome mostrado é o que está escrito na
inscrição — normalmente mais específico, como "EJA Ensino Médio + Técnico em
Estética".

**O texto é escrito no painel**, na `site_configuracoes`:

| chave | padrão | efeito |
|---|---|---|
| `aviso_ativo` | `sim` | `nao` desliga os balões |
| `aviso_texto` | `*{nome}*, de {cidade} - {estado}, se matriculou no curso *{curso}*` | linha principal |
| `aviso_rodape` | `{quando} · matrícula confirmada` | segunda linha, menor (vazio esconde) |
| `aviso_posicao` | `esquerda` | `direita` joga o balão para o outro canto |
| `aviso_primeiro_segundos` | `8` | espera até o primeiro balão |
| `aviso_intervalo_segundos` | `25` | espaço entre um balão e o próximo |
| `aviso_duracao_segundos` | `7` | tempo que cada um fica na tela |

Marcadores aceitos nos dois textos: `{nome}`, `{primeiro_nome}`, `{curso}`,
`{cidade}`, `{estado}` e `{quando}` — este último é a coluna `visto_por_ultimo`
copiada como está ("20 minutos atrás", "1:20 hora atrás"), sem o site recalcular
nada. O que estiver *entre asteriscos* sai em **negrito**. Quem escreve escolhe o verbo — "comprou", "se matriculou",
"garantiu a vaga".

> O balão não aparece com a aba em segundo plano e some de vez se o visitante
> clicar no **X** (volta na próxima visita). Todo o texto é escapado antes de ir
> para a tela, então nome vindo do Directus não injeta HTML.

## 🎓 Matrícula online

A página do curso matricula de verdade: o formulário grava o aluno no AVASET da
EDUSET, no mesmo lugar em que caem as matrículas do GESET.

```
navegador → /api/matricula.php → ead.eduset.com.br/api/matricula_externa.php → Directus
```

O que o site faz antes de repassar:

- **Preço e plano não vêm do navegador.** `planoVigente()` lê o
  `ava_catalogo_curso` na hora e usa a versão ativa de menor parcela — a mesma
  oferta exibida no card. Curso desativado no GESET recusa a matrícula.
- **Valida** nome, CPF (dígitos verificadores), nascimento, e-mail, WhatsApp,
  sexo e endereço completo. Menor de 18 anos só passa com nome e CPF do
  responsável financeiro.
- **Antisspam**: campo-armadilha escondido e limite de 5 envios por IP a cada 15
  minutos (`data/matriculas_ip.json`).
- **Afiliado**: se o visitante chegou por `?af=email@dominio`, o e-mail fica num
  cookie por 30 dias e vai no payload — o AVASET calcula a comissão pela
  categoria. Sem isso, é venda direta (`origem = Site`).

A matrícula nasce como no painel: `financeiro=aguardando`, `acesso=BLOQUEADO`,
`contrato=pendente`, usuário = CPF e senha inicial = data de nascimento (com
troca obrigatória no primeiro login). O aluno vê o número da matrícula e essas
credenciais na tela de sucesso. **Modalidade BOLSA não é oferecida no site** — o
próprio endpoint do AVASET recusa desconto ≥ 60% vindo de fora.

Configuração: `TOKEN_MATRICULA_EXTERNA` no site (EasyPanel) e a chave
`api_token_matricula_externa` na `avaset_configuracoes` do Directus, com o mesmo
valor. Sem o token, o formulário responde que a matrícula online está
indisponível e orienta o WhatsApp. Para apontar para outro ambiente, use
`AVASET_MATRICULA_URL` (padrão: `https://ead.eduset.com.br/api/matricula_externa.php`).

### Imagens

As capas são servidas por `public/api/imagem.php`, que busca o arquivo no
Directus pelo servidor e devolve só os bytes — assim o token **não** vai para o
navegador. Aceita `?w=` em 400, 600, 800, 1200 ou 1600.

## 📍 Unidades

O site tem duas páginas alimentadas pela `tabela_unidades` do Directus — a mesma
tabela em que o GESET cadastra polo:

| Página | O que mostra |
|---|---|
| `/unidades.php` | Todas as unidades ativas, agrupadas por estado, com busca por cidade/estado e filtro por UF (tudo no navegador: a lista já vem pronta do PHP). |
| `/unidade.php?id=<código>` | A ficha da unidade: cidade, estado e referência. Código inexistente volta para a lista. |

O **código** é o e-mail da unidade sem o domínio (`centro.aracaju.se`) — o mesmo
usado no link de divulgação `?polo=`, mas as duas coisas param aí.

> ### ⚠️ A página de unidade NÃO atribui a venda
>
> Nenhum link do site sai com `?polo=`. Quem chega pelo domínio (busca, anúncio
> da escola, menu) e passa pela página de uma unidade **não** fica preso a ela:
> segue sem cookie e o AVASET registra a matrícula na unidade EAD do estado do
> aluno, como em qualquer visita orgânica.
>
> Atribuir venda é papel exclusivo do link de divulgação do polo,
> `eduset.com.br/?polo=<código>` — é ele que grava o cookie de 30 dias.
> Isso mantém o tráfego pago da escola separado do tráfego pago do polo.
>
> Se a ficha da unidade voltar a apontar para `index.php?polo=...`, o problema
> volta junto: basta o visitante clicar numa unidade para a venda sair dela.

> **A página não publica endereço nem telefone de unidade.** Ela diz em que cidade
> e estado a unidade fica, e o contato oferecido é sempre o canal oficial da
> escola (WhatsApp e e-mail da `site_configuracoes`). A consulta ao Directus pede
> só `unidade_nome`, `unidade_email`, `cidade`, `estado` e `situacao` — rua, CEP,
> celular, senha, chave de API, CNPJ e repasse nem chegam a ser lidos.

Outras regras que a página aplica sozinha:

- **Polo e unidade a distância aparecem iguais.** A página não separa as duas:
  para quem procura a escola na sua cidade, é tudo "unidade". A regra do GESET
  que reconhece a unidade a distância (segmento `ead` no e-mail — `ead.cidade.uf@`
  ou `cidade.uf.ead@` — **ou** nome terminando em "EAD") sobrou só para tirar
  esse "EAD" do fim do nome da cidade e da referência.
- **Só unidade com `situacao = ativo`.**
- **Cadastro incompleto não quebra a página.** Sem cidade/estado nos campos, o
  nome da unidade completa (o padrão é "Estado - Cidade - Referência"); estado
  gravado como "Bahia", "BA" ou em branco dá no mesmo; cidade em CAIXA ALTA sai
  como nome próprio; e a busca ignora acento ("camacari" acha Camaçari).

A lista fica em cache por 10 minutos, como o resto do site, e o `/api/purgar.php`
já limpa esse cache junto com os outros.

> Sem nenhuma unidade ativa a página não fica vazia: `/unidades.php` cai no bloco
> de atendimento a distância com as siglas dos estados atendidos.

Textos editáveis na `site_configuracoes`: `unidades_titulo`, `unidades_subtitulo`,
`unidades_seo_titulo` e `unidades_seo_descricao`.

## 🔐 Painel do site (`/admin`)

Para quem não quer abrir o Directus, o site tem um painel próprio em
`https://eduset.com.br/admin`.

**Não existe cadastro nem senha separada.** O login é validado contra a
`tabela_gestores` do Directus da EDUSET — a mesma do `ead.eduset.com.br`.
Quem troca a senha no AVASET troca aqui junto; quem é bloqueado lá perde o
acesso aqui na hora.

- **Quem entra:** gestores de nível `admin`/`geral` (mesma regra que o painel do
  AVASET usa para liberar a tela de gestores). Gestor com `situacao` bloqueado,
  inativo ou desativado é barrado.
- **Verificação de senha:** bcrypt → texto puro (legado) → MD5, na mesma ordem do
  `api/login.php` do AVASET, para nenhum gestor existente ficar de fora.
- **O que dá para fazer:** trocar a **imagem do topo (hero)** da home,
  trocar/remover a capa dos cursos, mostrar ou esconder um curso, pôr um curso
  livre na vitrine, editar todos os textos da página do curso e as configurações
  gerais do site (topo, contatos, redes, números e SEO).
- **O que NÃO dá para fazer:** mexer em preço, parcelas ou desconto — isso é do
  catálogo do AVASET, de propósito.

Ao salvar, o cache é limpo automaticamente, então a mudança aparece no site na
hora. O painel tem `noindex`, está bloqueado no `robots.txt`, exige token CSRF
em todo formulário, expira a sessão em 2 horas de inatividade e trava o IP após
8 tentativas de login erradas em 15 minutos.

> As imagens são validadas pelo conteúdo (não pela extensão) e limitadas a 8 MB.
> Aceita JPG, PNG, WEBP e GIF.
