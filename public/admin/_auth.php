<?php
// admin/_auth.php — autenticação do painel do site.
//
// NÃO existe cadastro próprio aqui: o login é o mesmo do ead.eduset.com.br,
// validado contra a tabela_gestores do Directus da EDUSET. Quem troca a senha
// no AVASET troca aqui junto, e quem é bloqueado lá perde o acesso aqui.
//
// A verificação de senha replica a ordem usada em api/login.php do AVASET
// (bcrypt → texto puro legado → md5), para que nenhum gestor existente fique de fora.

require_once __DIR__ . '/../api/_catalogo.php';

const COL_GESTORES   = 'tabela_gestores';
const SESSAO_TEMPO   = 7200;  // 2 horas de inatividade
const MAX_TENTATIVAS = 8;     // por IP, dentro da janela
const JANELA_BLOQUEIO = 900;  // 15 minutos

// ---------------------------------------------------------------- sessão
function iniciarSessao(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;

  $seguro = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'httponly' => true,           // JavaScript não lê o cookie
    'secure'   => $seguro,        // só trafega em HTTPS quando disponível
    'samesite' => 'Lax',
  ]);
  session_name('EDUSET_ADMIN');
  session_start();
}

function logado(): bool {
  iniciarSessao();
  if (empty($_SESSION['admin_id'])) return false;

  // Expira por inatividade.
  if (time() - ($_SESSION['admin_visto'] ?? 0) > SESSAO_TEMPO) {
    encerrarSessao();
    return false;
  }
  $_SESSION['admin_visto'] = time();
  return true;
}

function exigirLogin(): void {
  if (!logado()) {
    header('Location: index.php?e=sessao');
    exit;
  }
}

function encerrarSessao(): void {
  iniciarSessao();
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}

function gestorNome(): string {
  iniciarSessao();
  return (string) ($_SESSION['admin_nome'] ?? '');
}

// ---------------------------------------------------------------- CSRF
function csrf(): string {
  iniciarSessao();
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}

function csrfValido(?string $enviado): bool {
  iniciarSessao();
  return !empty($_SESSION['csrf']) && is_string($enviado)
    && hash_equals($_SESSION['csrf'], $enviado);
}

// ---------------------------------------------------------------- limite de tentativas
function arquivoTentativas(): string {
  return rtrim(sys_get_temp_dir(), '/\\') . '/eduset_admin_tentativas.json';
}

function ipAtual(): string {
  return (string) ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido');
}

function bloqueadoPorTentativas(): bool {
  $dados = json_decode((string) @file_get_contents(arquivoTentativas()), true) ?: [];
  $reg = $dados[ipAtual()] ?? null;
  if (!$reg) return false;
  if (time() - ($reg['inicio'] ?? 0) > JANELA_BLOQUEIO) return false;
  return ($reg['n'] ?? 0) >= MAX_TENTATIVAS;
}

function registrarTentativa(bool $sucesso): void {
  $arq = arquivoTentativas();
  $dados = json_decode((string) @file_get_contents($arq), true) ?: [];
  $ip = ipAtual();

  if ($sucesso) {
    unset($dados[$ip]);
  } else {
    $reg = $dados[$ip] ?? ['n' => 0, 'inicio' => time()];
    if (time() - $reg['inicio'] > JANELA_BLOQUEIO) $reg = ['n' => 0, 'inicio' => time()];
    $reg['n']++;
    $dados[$ip] = $reg;
  }

  // Limpa registros vencidos para o arquivo não crescer sem limite.
  foreach ($dados as $k => $v) {
    if (time() - ($v['inicio'] ?? 0) > JANELA_BLOQUEIO) unset($dados[$k]);
  }
  @file_put_contents($arq, json_encode($dados));
}

// ---------------------------------------------------------------- login
/**
 * Procura o gestor pelo usuário (ou e-mail, como no AVASET) e confere a senha.
 * @return array{ok: bool, msg: string, gestor?: array}
 */
function autenticar(string $login, string $senha): array {
  $login = strtolower(trim($login));
  if ($login === '' || $senha === '') {
    return ['ok' => false, 'msg' => 'Informe usuário e senha.'];
  }

  $campos = 'id,nome,email,usuario,senha,senha_md5,situacao,nivel';

  // 1) por usuário exato (caminho normal do GESET)
  $achado = buscarColecao(COL_GESTORES, [
    'fields' => $campos, 'limit' => 1,
    'filter' => json_encode(['usuario' => ['_eq' => $login]]),
  ]);

  // 2) legado: gestor sem "usuario" entra pelo e-mail
  if (!$achado) {
    $achado = buscarColecao(COL_GESTORES, [
      'fields' => $campos, 'limit' => 1,
      'filter' => json_encode(['email' => ['_icontains' => $login]]),
    ]);
  }

  if ($achado === null) {
    return ['ok' => false, 'msg' => 'Não foi possível validar o acesso agora. Tente novamente.'];
  }
  if ($achado === []) {
    return ['ok' => false, 'msg' => 'Usuário ou senha incorretos.'];
  }

  $g = $achado[0];
  $guardada = (string) ($g['senha'] ?? '');

  // Mesma ordem de verificação do AVASET, para não deixar gestor legado de fora.
  $ok = false;
  if ($guardada !== '' && password_verify($senha, $guardada)) {
    $ok = true;
  } elseif ($guardada !== '' && hash_equals($guardada, $senha)) {
    $ok = true;
  } elseif (!empty($g['senha_md5']) && hash_equals((string) $g['senha_md5'], md5($senha))) {
    $ok = true;
  }

  if (!$ok) {
    return ['ok' => false, 'msg' => 'Usuário ou senha incorretos.'];
  }

  // Bloqueio replicado do AVASET: só barra quem está explicitamente marcado.
  $situacao = strtolower(trim((string) ($g['situacao'] ?? '')));
  if (in_array($situacao, ['bloqueado', 'inativo', 'desativado'], true)) {
    return ['ok' => false, 'msg' => 'Seu acesso está bloqueado. Fale com o administrador.'];
  }

  // Editar o site público é restrito ao nível administrativo, como no painel do AVASET.
  $nivel = strtolower(trim((string) ($g['nivel'] ?? '')));
  if (!in_array($nivel, ['admin', 'geral', ''], true)) {
    return ['ok' => false, 'msg' => 'Seu usuário não tem permissão para editar o site.'];
  }

  return ['ok' => true, 'msg' => '', 'gestor' => $g];
}

function abrirSessao(array $gestor): void {
  iniciarSessao();
  session_regenerate_id(true);
  $_SESSION['admin_id']    = $gestor['id'] ?? '';
  $_SESSION['admin_nome']  = $gestor['nome'] ?? ($gestor['usuario'] ?? 'Gestor');
  $_SESSION['admin_nivel'] = strtolower(trim((string) ($gestor['nivel'] ?? '')));
  $_SESSION['admin_visto'] = time();
}
