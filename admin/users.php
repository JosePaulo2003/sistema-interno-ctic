<?php
// Administração de usuários: só para desenvolvedor. Não, eu não vou deixar a festa aberta.
session_start();
if (empty($_SESSION['user'])) {
  header('Location: /ctic/login.php');
  exit;
}
if (empty($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'dev') {
  http_response_code(403);
  echo 'Acesso negado.';
  exit;
}
require_once __DIR__ . '/../config/db.php';

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? 'create';

  if ($action === 'create') {
    $username = trim($_POST['username'] ?? '');
    $nome = trim($_POST['nome'] ?? '');
    $senha = (string)($_POST['senha'] ?? '');
    $role = trim($_POST['role'] ?? 'user');

    if ($username === '' || !preg_match('/^[a-zA-Z0-9_\.\-]{3,64}$/', $username)) {
      $errors[] = 'Informe um usuário válido (3-64, letras, números, _ . -).';
    }
    if (strlen($senha) < 6) {
      $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
    }
    if (!in_array($role, ['user','dev','supervisor do setor','estagiario tecnico'], true)) {
      $errors[] = 'Papel inválido.';
    }

    if (!$errors) {
      try {
        $check = $pdo->prepare('SELECT id FROM usuarios WHERE username = ? LIMIT 1');
        $check->execute([$username]);
        if ($check->fetch()) {
          $errors[] = 'Usuário já existe.';
        } else {
          $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
          $ins = $pdo->prepare('INSERT INTO usuarios (username, nome, password_hash, role) VALUES (?, ?, ?, ?)');
          $ins->execute([$username, $nome !== '' ? $nome : null, $hash, $role]);
          $success = 'Usuário criado com sucesso.';
        }
      } catch (Throwable $e) {
        $errors[] = 'Falha ao criar usuário.';
      }
    }
  }
  elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $role = trim($_POST['role'] ?? 'user');
    if ($id <= 0) { $errors[] = 'ID inválido.'; }
    if (!in_array($role, ['user','dev','supervisor do setor','estagiario tecnico'], true)) {
      $errors[] = 'Papel inválido.';
    }
    if (!$errors) {
      try {
        $upd = $pdo->prepare('UPDATE usuarios SET nome = ?, role = ? WHERE id = ?');
        $upd->execute([$nome !== '' ? $nome : null, $role, $id]);
        $success = 'Usuário atualizado.';
      } catch (Throwable $e) {
        $errors[] = 'Falha ao atualizar usuário.';
      }
    }
  }
  elseif ($action === 'reset_pwd') {
    $id = (int)($_POST['id'] ?? 0);
    $senha = (string)($_POST['senha'] ?? '');
    if ($id <= 0) { $errors[] = 'ID inválido.'; }
    if (strlen($senha) < 6) { $errors[] = 'A senha deve ter pelo menos 6 caracteres.'; }
    if (!$errors) {
      try {
        $hash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        $upd = $pdo->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
        $upd->execute([$hash, $id]);
        $success = 'Senha atualizada.';
      } catch (Throwable $e) {
        $errors[] = 'Falha ao atualizar senha.';
      }
    }
  }
  elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { $errors[] = 'ID inválido.'; }
    if (!empty($_SESSION['user']['id']) && (int)$_SESSION['user']['id'] === $id) {
      $errors[] = 'Você não pode excluir a própria conta.';
    }
    if (!$errors) {
      try {
        $del = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
        $del->execute([$id]);
        $success = 'Usuário excluído.';
      } catch (Throwable $e) {
        $errors[] = 'Falha ao excluir usuário.';
      }
    }
  }
}

// Listagem simples para conferência (limitada)
$users = [];
try {
  $q = $pdo->query('SELECT id, username, nome, role, created_at FROM usuarios ORDER BY id DESC LIMIT 50');
  $users = $q->fetchAll();
} catch (Throwable $e) {
  // Eu fingiria surpresa, mas banco às vezes tropeça.
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Usuários | CTIC CESIT</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ctic/assets/css/style.css" />
</head>
<body>
  <canvas id="matrix"></canvas>
  <header class="site-header" role="banner">
    <div class="brand">
      <div class="logo" aria-hidden="true">▮</div>
      <div class="meta">
        <h1>CTIC - CESIT</h1>
        <p>Controle de sistemas locais</p>
      </div>
    </div>
    <nav aria-label="Acesso Rápido" class="quick-nav">
      <a href="/ctic/" class="btn ghost">Início</a>
      <a href="/ctic/profile.php" class="btn ghost">Perfil</a>
      <a href="/ctic/logout.php" class="btn ghost">Sair</a>
    </nav>
  </header>

  <main class="content" role="main">
    <section class="about">
      <h3>Gerenciar Usuários</h3>
      <p>Crie novos usuários e defina o papel de acesso.</p>

      <?php if ($success): ?>
        <div class="alert" style="border-color: rgba(0,255,136,.35); color: #bfffdc; background: rgba(0,255,136,.06)">
          <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>
      <?php if ($errors): ?>
        <div class="alert">
          <?php foreach ($errors as $e): ?>
            <div>• <?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form method="post" class="login-form" style="max-width:520px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <label>
            <span>Usuário</span>
            <input type="text" name="username" required placeholder="ex: jdoe" />
          </label>
          <label>
            <span>Nome</span>
            <input type="text" name="nome" placeholder="Nome completo (opcional)" />
          </label>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <label>
            <span>Senha</span>
            <input type="password" name="senha" required />
          </label>
          <label>
            <span>Papel</span>
            <select name="role">
              <option value="user">Usuário</option>
              <option value="supervisor do setor">Supervisor do Setor</option>
              <option value="estagiario tecnico">Estagiário Técnico</option>
              <option value="dev">Desenvolvedor</option>
            </select>
          </label>
        </div>
        <div class="actions">
          <button type="submit" class="btn primary">Criar</button>
        </div>
      </form>
    </section>

    <section class="grid" style="margin-top:20px">
      <h3>Últimos 50 usuários</h3>
      <div class="about" style="overflow:auto">
        <div style="display:flex;flex-direction:column;gap:10px;padding:8px">
<?php foreach ($users as $u): ?>
          <form method="post" class="login-form" style="border:1px solid var(--border);border-radius:10px;padding:10px">
            <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>" />
            <div style="display:grid;grid-template-columns:1.2fr 1fr 1fr 1fr;gap:10px;align-items:end">
              <label>
                <span>Usuário</span>
                <input type="text" value="<?php echo htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8'); ?>" disabled />
              </label>
              <label>
                <span>Nome</span>
                <input type="text" name="nome" value="<?php echo htmlspecialchars((string)$u['nome'], ENT_QUOTES, 'UTF-8'); ?>" />
              </label>
              <label>
                <span>Papel</span>
                <select name="role">
                  <option value="user" <?php echo ($u['role']==='user'?'selected':''); ?>>Usuário</option>
                  <option value="supervisor do setor" <?php echo ($u['role']==='supervisor do setor'?'selected':''); ?>>Supervisor do Setor</option>
                  <option value="estagiario tecnico" <?php echo ($u['role']==='estagiario tecnico'?'selected':''); ?>>Estagiário Técnico</option>
                  <option value="dev" <?php echo ($u['role']==='dev'?'selected':''); ?>>Desenvolvedor</option>
                </select>
              </label>
              <div class="actions">
                <button class="btn primary" name="action" value="update">Salvar</button>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr auto auto;gap:10px;margin-top:8px;align-items:end">
              <label>
                <span>Nova Senha</span>
                <input type="password" name="senha" placeholder="Mín. 6 caracteres" />
              </label>
              <button class="btn secondary" name="action" value="reset_pwd">Atualizar Senha</button>
              <button class="btn danger" name="action" value="delete" onclick="return confirm('Excluir este usuário?');">Excluir</button>
            </div>
          </form>
<?php endforeach; ?>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer" role="contentinfo">
    <p>© <?php echo date('Y'); ?> CTIC CESIT — Todos os direitos reservados.</p>
  </footer>

  <script src="/ctic/assets/js/app.js"></script>
</body>
</html>
