<?php
// Sim, um login minimalista. Porque ferramental de SSO inteiro agora seria... ambicioso.
session_start();
// Eu vou puxar a conexão PDO daqui, porque reinventar roda de SQL hoje não está no meu roteiro.
require_once __DIR__ . '/config/db.php';

// Se já está logado, eu não vou fazer você ver o show outra vez.
if (!empty($_SESSION['user'])) {
  header('Location: /ctic/');
  exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if ($username === '') { $errors[] = 'Informe o usuário.'; }
  if ($password === '') { $errors[] = 'Informe a senha.'; }

  if (!$errors) {
    try {
      // Consultinha humilde e preparada, porque SQL injection não é hobby.
      // Tenta ler role e avatar_path se já existirem; se não, faz fallback silencioso.
      $user = null;
      try {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, nome, role, avatar_path FROM usuarios WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
      } catch (Throwable $inner) {
        // Muito drama por duas colunas? Então tá, eu volto ao básico.
        $stmt = $pdo->prepare('SELECT id, username, password_hash, nome FROM usuarios WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        // Enriquecimento manual para manter a interface da sessão intacta.
        if ($user) { $user['role'] = null; $user['avatar_path'] = null; }
      }

      if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
          'id' => (int)$user['id'],
          'name' => $user['nome'] ?: $user['username'],
          'username' => $user['username'],
          'role' => $user['role'] ?? null,
          'avatar' => $user['avatar_path'] ?? null,
          'time' => time()
        ];
        header('Location: /ctic/');
        exit;
      } else {
        // Eu poderia dizer "usuário ou senha inválidos" sem especificar qual, e é exatamente o que vou fazer.
        $errors[] = 'Usuário ou senha inválidos.';
      }
    } catch (Throwable $e) {
      // Em produção: logue isso direito. Aqui eu só aviso com educação.
      $errors[] = 'Falha ao processar o login. Tente novamente mais tarde.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | CTIC CESIT</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ctic/assets/css/style.css" />
</head>
<body class="login-body" data-has-errors="<?php echo $errors ? '1' : '0'; ?>">
  <canvas id="matrix"></canvas>

  <!-- Loader full-screen, porque suspense vende. -->
  <div id="loader" class="loader" role="status" aria-live="polite" aria-label="Carregando">
    <div class="loader-inner">
      <div class="spinner"></div>
      <div class="loader-text">Inicializando Console...</div>
    </div>
  </div>

  <main class="login-container" role="main" aria-labelledby="login-title" hidden>
    <section class="login-card" role="region" id="loginCard">
      <div class="terminal-bar">
        <span class="dot red" aria-hidden="true"></span>
        <span class="dot yellow" aria-hidden="true"></span>
        <span class="dot green" aria-hidden="true"></span>
        <span class="title">/auth/login</span>
      </div>
      <div class="login-content">
        <h1 id="login-title">Acesso ao Console</h1>
        <p class="muted">Autentique-se para continuar.</p>

        <?php if ($errors): ?>
          <div class="alert">
            <?php foreach ($errors as $e): ?>
              <div>• <?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="login-form" novalidate id="loginForm">
          <label>
            <span>Usuário</span>
            <input type="text" name="username" autocomplete="username" required>
          </label>
          <label class="password-field">
            <span>Senha</span>
            <span class="input-wrap">
              <input type="password" name="password" id="password" autocomplete="current-password" required>
              <button type="button" class="toggle-visibility" id="togglePassword" aria-label="Mostrar senha" aria-pressed="false">
                <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M12 5C7 5 2.73 8.11 1 12c1.73 3.89 6 7 11 7s9.27-3.11 11-7c-1.73-3.89-6-7-11-7Z" stroke="currentColor" stroke-width="1.5"/>
                  <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"/>
                </svg>
                <svg class="icon-eye-off icon-hidden" width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M3 3l18 18" stroke="currentColor" stroke-width="1.5"/>
                  <path d="M12 5c-2.51 0-4.86.71-6.8 1.92M4 17c2.04 1.61 4.67 3 8 3 5 0 9.27-3.11 11-7-.67-1.5-1.72-2.86-3.03-4.01" stroke="currentColor" stroke-width="1.5"/>
                </svg>
              </button>
            </span>
          </label>
          <div class="actions">
            <button type="submit" class="btn primary">Entrar</button>
          </div>
        </form>
      </div>
    </section>
  </main>

  <script src="/ctic/assets/js/app.js"></script>
  <script src="/ctic/assets/js/login.js"></script>
</body>
</html>
