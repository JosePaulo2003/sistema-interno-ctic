<?php
// Perfil do usuário: upload de avatar com um mínimo de dignidade e segurança.
session_start();
if (empty($_SESSION['user'])) {
  header('Location: /ctic/login.php');
  exit;
}
require_once __DIR__ . '/config/db.php';

$me = $_SESSION['user'];
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
  // Sim, eu vou avaliar esse arquivo como se ele pudesse me trair. Porque ele pode.
  $file = $_FILES['avatar'];
  if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors[] = 'Falha no upload do arquivo.';
  } else {
    $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png', 'image/webp' => '.webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
      $errors[] = 'Formato de imagem não suportado. Use JPG, PNG ou WEBP.';
    }
    if ($file['size'] > 2 * 1024 * 1024) { // 2MB
      $errors[] = 'Arquivo muito grande. Máximo de 2MB.';
    }

    if (!$errors) {
      $ext = $allowed[$mime];
      $dir = __DIR__ . '/uploads/avatars';
      if (!is_dir($dir)) {
        // Eu poderia pedir para você criar, mas vou ser gentil e criar aqui mesmo.
        @mkdir($dir, 0755, true);
      }
      $name = 'u' . (int)$me['id'] . '_' . bin2hex(random_bytes(6)) . $ext;
      $destPath = $dir . '/' . $name;
      if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        $errors[] = 'Não foi possível salvar o arquivo.';
      } else {
        $relPath = '/ctic/uploads/avatars/' . $name;
        try {
          $stmt = $pdo->prepare('UPDATE usuarios SET avatar_path = ? WHERE id = ?');
          $stmt->execute([$relPath, (int)$me['id']]);
          $_SESSION['user']['avatar'] = $relPath;
          $success = 'Avatar atualizado com sucesso.';
        } catch (Throwable $e) {
          $errors[] = 'Falha ao atualizar o perfil.';
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Perfil | CTIC CESIT</title>
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
      <a href="/ctic/logout.php" class="btn ghost">Sair</a>
    </nav>
  </header>

  <main class="content" role="main">
    <section class="about">
      <h3>Meu Perfil</h3>
      <p>Atualize sua foto de perfil. Formatos aceitos: JPG, PNG ou WEBP. Tamanho máximo: 2MB.</p>

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

      <div style="display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap">
        <div>
          <div style="width:120px;height:120px;border-radius:12px;overflow:hidden;border:1px solid var(--border);background:#0a1010;display:flex;align-items:center;justify-content:center">
            <?php if (!empty($_SESSION['user']['avatar'])): ?>
              <img src="<?php echo htmlspecialchars($_SESSION['user']['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar" style="max-width:100%;max-height:100%"/>
            <?php else: ?>
              <span class="muted">Sem avatar</span>
            <?php endif; ?>
          </div>
        </div>
        <form method="post" enctype="multipart/form-data" class="login-form" style="min-width:260px">
          <label>
            <span>Selecionar imagem</span>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required />
          </label>
          <div class="actions">
            <button type="submit" class="btn primary">Salvar</button>
          </div>
        </form>
      </div>
    </section>
  </main>

  <footer class="site-footer" role="contentinfo">
    <p>© <?php echo date('Y'); ?> CTIC CESIT — Todos os direitos reservados.</p>
  </footer>

  <script src="/ctic/assets/js/app.js"></script>
</body>
</html>
