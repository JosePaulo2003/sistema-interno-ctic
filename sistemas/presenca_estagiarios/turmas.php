<?php
session_start();
if (empty($_SESSION['user'])) { header('Location: /ctic/login.php'); exit; }
require_once __DIR__ . '/../../config/db.php';

$errors = [];$success = null;

// Criar turma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_turma') {
  $nome = trim($_POST['nome'] ?? '');
  if ($nome === '') { $errors[] = 'Informe o nome da turma.'; }
  if (!$errors) {
    try { $stmt = $pdo->prepare('INSERT INTO turmas (nome) VALUES (?)'); $stmt->execute([$nome]); $success = 'Turma criada.'; }
    catch (Throwable $e) { $errors[] = 'Falha ao criar turma.'; }
  }
}

// Criar estagiário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_estagiario') {
  $nome = trim($_POST['e_nome'] ?? '');
  $matricula = trim($_POST['matricula'] ?? '');
  if ($nome === '') { $errors[] = 'Informe o nome do estagiário.'; }
  if (!$errors) {
    try { $stmt = $pdo->prepare('INSERT INTO estagiarios (nome, matricula) VALUES (?, ?)'); $stmt->execute([$nome, $matricula !== ''?$matricula:null]); $success = 'Estagiário criado.'; }
    catch (Throwable $e) { $errors[] = 'Falha ao criar estagiário.'; }
  }
}

// Vincular estagiário à turma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_turma') {
  $turma_id = (int)($_POST['turma_id'] ?? 0);
  $estagiario_id = (int)($_POST['estagiario_id'] ?? 0);
  if ($turma_id<=0 || $estagiario_id<=0) { $errors[] = 'Seleção inválida.'; }
  if (!$errors) {
    try { $pdo->prepare('INSERT IGNORE INTO turma_estagiarios (turma_id, estagiario_id) VALUES (?,?)')->execute([$turma_id,$estagiario_id]); $success='Vinculado.'; }
    catch (Throwable $e) { $errors[] = 'Falha ao vincular.'; }
  }
}

// Remover estagiário da turma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_from_turma') {
  $turma_id = (int)($_POST['turma_id'] ?? 0);
  $estagiario_id = (int)($_POST['estagiario_id'] ?? 0);
  if ($turma_id<=0 || $estagiario_id<=0) { $errors[] = 'Seleção inválida.'; }
  if (!$errors) {
    try { $pdo->prepare('DELETE FROM turma_estagiarios WHERE turma_id=? AND estagiario_id=?')->execute([$turma_id,$estagiario_id]); $success='Removido da turma.'; }
    catch (Throwable $e) { $errors[] = 'Falha ao remover.'; }
  }
}

// Excluir turma (cascateia para vínculos e presenças via FK ON DELETE CASCADE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_turma') {
  $turma_id = (int)($_POST['turma_id'] ?? 0);
  if ($turma_id<=0) { $errors[] = 'Turma inválida.'; }
  if (!$errors) {
    try { $pdo->prepare('DELETE FROM turmas WHERE id=?')->execute([$turma_id]); $success='Turma excluída.'; }
    catch (Throwable $e) { $errors[] = 'Falha ao excluir turma.'; }
  }
}

// Carregar dados
$turmas = $pdo->query('SELECT id,nome FROM turmas ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$estagiarios = $pdo->query('SELECT id,nome,matricula FROM estagiarios ORDER BY nome')->fetchAll(PDO::FETCH_ASSOC);
$turma_id = isset($_GET['turma']) ? (int)$_GET['turma'] : (count($turmas)?(int)$turmas[0]['id']:0);
$daTurma = [];
if ($turma_id) {
  $st = $pdo->prepare('SELECT e.id,e.nome,e.matricula FROM turma_estagiarios te INNER JOIN estagiarios e ON e.id=te.estagiario_id WHERE te.turma_id=? ORDER BY e.nome');
  $st->execute([$turma_id]);
  $daTurma = $st->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Turmas | Presença de Estagiários</title>
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
      <a href="/ctic/sistemas/presenca_estagiarios/" class="btn ghost">Calendário</a>
      <a href="/ctic/logout.php" class="btn ghost">Sair</a>
    </nav>
  </header>

  <main class="content" role="main">
    <section class="about">
      <h3>Gerenciar Turmas e Estagiários</h3>
      <?php if ($success): ?><div class="alert" style="border-color: rgba(0,255,136,.35); color: #bfffdc; background: rgba(0,255,136,.06)"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
      <?php if ($errors): ?><div class="alert"><?php foreach($errors as $e){ echo '<div>• '.htmlspecialchars($e,ENT_QUOTES,'UTF-8').'</div>'; } ?></div><?php endif; ?>

      <div class="grid">
        <div class="about" style="margin-bottom:12px">
          <h4>Criar Turma</h4>
          <form method="post" class="login-form" style="max-width:520px">
            <input type="hidden" name="action" value="create_turma" />
            <label>
              <span>Nome da Turma</span>
              <input type="text" name="nome" required />
            </label>
            <div class="actions"><button class="btn primary">Criar</button></div>
          </form>
        </div>

        <div class="about" style="margin-bottom:12px">
          <h4>Excluir Turma</h4>
          <form method="post" class="login-form" style="max-width:520px" onsubmit="return confirm('Excluir esta turma e todos os vínculos/presenças?');">
            <input type="hidden" name="action" value="delete_turma" />
            <label>
              <span>Turma</span>
              <select name="turma_id">
                <?php foreach($turmas as $t){ echo '<option value="'.(int)$t['id'].'">'.htmlspecialchars($t['nome'],ENT_QUOTES,'UTF-8').'</option>'; } ?>
              </select>
            </label>
            <div class="actions"><button class="btn danger">Excluir</button></div>
          </form>
        </div>

        <div class="about" style="margin-bottom:12px">
          <h4>Criar Estagiário</h4>
          <form method="post" class="login-form" style="max-width:520px">
            <input type="hidden" name="action" value="create_estagiario" />
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <label><span>Nome</span><input type="text" name="e_nome" required /></label>
              <label><span>Matrícula (opcional)</span><input type="text" name="matricula" /></label>
            </div>
            <div class="actions"><button class="btn secondary">Adicionar</button></div>
          </form>
        </div>

        <div class="about">
          <h4>Vincular Estagiário à Turma</h4>
          <form method="post" class="login-form">
            <input type="hidden" name="action" value="add_to_turma" />
            <div style="display:grid;grid-template-columns:1fr 1fr 0.4fr;gap:12px;align-items:end">
              <label>
                <span>Turma</span>
                <select name="turma_id">
                  <?php foreach($turmas as $t){ echo '<option value="'.(int)$t['id'].'">'.htmlspecialchars($t['nome'],ENT_QUOTES,'UTF-8').'</option>'; } ?>
                </select>
              </label>
              <label>
                <span>Estagiário</span>
                <select name="estagiario_id">
                  <?php foreach($estagiarios as $e){ $label=$e['nome'].($e['matricula']?(' — '.$e['matricula']):''); echo '<option value="'.(int)$e['id'].'">'.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</option>'; } ?>
                </select>
              </label>
              <div class="actions"><button class="btn primary">Vincular</button></div>
            </div>
          </form>
        </div>
      </div>

      <div class="about" style="margin-top:16px">
        <h4>Estagiários da Turma</h4>
        <form method="get" style="display:flex;gap:8px;margin-bottom:8px">
          <label style="display:flex;gap:8px;align-items:center"><span>Turma</span>
            <select name="turma" class="btn secondary" onchange="this.form.submit()">
              <?php foreach($turmas as $t): ?>
                <option value="<?php echo (int)$t['id']; ?>" <?php echo ((int)$t['id']===$turma_id?'selected':''); ?>><?php echo htmlspecialchars($t['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
        </form>
        <div style="max-height:360px;overflow:auto;border:1px solid var(--border);border-radius:8px;padding:8px">
          <?php foreach($daTurma as $e): ?>
            <form method="post" class="login-form" style="display:flex;justify-content:space-between;align-items:center;border:1px solid var(--border);border-radius:8px;padding:8px;margin-bottom:6px;background:#0a1010">
              <div><?php echo htmlspecialchars($e['nome'],ENT_QUOTES,'UTF-8'); ?><?php if(!empty($e['matricula'])) echo ' — '.htmlspecialchars($e['matricula'],ENT_QUOTES,'UTF-8'); ?></div>
              <input type="hidden" name="action" value="remove_from_turma" />
              <input type="hidden" name="turma_id" value="<?php echo (int)$turma_id; ?>" />
              <input type="hidden" name="estagiario_id" value="<?php echo (int)$e['id']; ?>" />
              <button class="btn danger" onclick="return confirm('Remover este estagiário da turma?');">Remover</button>
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
