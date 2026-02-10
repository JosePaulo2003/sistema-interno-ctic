<?php
session_start();
if (empty($_SESSION['user'])) { header('Location: /ctic/login.php'); exit; }
require_once __DIR__ . '/../../config/db.php';

// Utilidades de data
$today = new DateTime('today');
$year = (int)($_GET['y'] ?? $today->format('Y'));
$month = (int)($_GET['m'] ?? $today->format('n'));
$day = (int)($_GET['d'] ?? $today->format('j'));
$selectedDate = DateTime::createFromFormat('!Y-n-j', "$year-$month-$day");
if (!$selectedDate) { $selectedDate = clone $today; }

// Turmas
$turmas = [];
try {
  $turmas = $pdo->query('SELECT id, nome FROM turmas ORDER BY nome')->fetchAll();
} catch (Throwable $e) { /* sem drama: tabela pode não existir ainda */ }

$turma_id = isset($_GET['turma']) ? (int)$_GET['turma'] : (count($turmas) ? (int)$turmas[0]['id'] : 0);

// Eu limpo os fantasmas: remove presenças cujos estagiários/turmas foram apagados
try {
  $pdo->exec('DELETE p FROM presencas p LEFT JOIN estagiarios e ON e.id = p.estagiario_id WHERE e.id IS NULL');
  $pdo->exec('DELETE p FROM presencas p LEFT JOIN turmas t ON t.id = p.turma_id WHERE t.id IS NULL');
} catch (Throwable $e) { /* banco sem FKs? Sem problemas, faço faxina manual. */ }

// Exportação CSV (resumo mensal por estagiário)
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $turma_id) {
  try {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="frequencias_turma_'.$turma_id.'_'.$year.'-'.str_pad((string)$month,2,'0',STR_PAD_LEFT).'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Estagiário', 'Mês', 'Total de Presenças (dias distintos)']);
    $stmt = $pdo->prepare('SELECT e.nome, COUNT(DISTINCT p.data) as total
      FROM turma_estagiarios te
      INNER JOIN estagiarios e ON e.id = te.estagiario_id
      LEFT JOIN presencas p ON p.estagiario_id = e.id AND p.turma_id = te.turma_id AND p.presente = 1 AND YEAR(p.data)=? AND MONTH(p.data)=?
      WHERE te.turma_id = ?
      GROUP BY e.id, e.nome
      ORDER BY e.nome');
    $stmt->execute([$year, $month, $turma_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      fputcsv($out, [ (string)$row['nome'], sprintf('%04d-%02d',$year,$month), (int)$row['total'] ]);
    }
    fclose($out);
    exit;
  } catch (Throwable $e) { /* se o CSV tropeçar, sigo com a renderização normal */ }
}

$flash = null; $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_presence'])) {
  $turma_id = (int)($_POST['turma_id'] ?? 0);
  $date = $_POST['date'] ?? $today->format('Y-m-d');
  $present_ids = array_map('intval', $_POST['presenca'] ?? []);
  // Buscar nomes dos presentes para compor a mensagem
  $present_names = [];
  if ($present_ids) {
    try {
      $in = implode(',', array_fill(0, count($present_ids), '?'));
      $qN = $pdo->prepare("SELECT nome FROM estagiarios WHERE id IN ($in)");
      $qN->execute($present_ids);
      $present_names = array_map(fn($r)=>$r['nome'], $qN->fetchAll());
    } catch (Throwable $e) { /* segue sem nomes */ }
  }
  try {
    // Eu não vou desfazer presença marcada. Nada de apagar registros.
    if ($present_ids) {
      $ins = $pdo->prepare('INSERT IGNORE INTO presencas (turma_id, estagiario_id, data, presente) VALUES (?,?,?,1)');
      foreach ($present_ids as $eid) { $ins->execute([$turma_id, $eid, $date]); }
    }
    $actor = htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'Usuário', ENT_QUOTES, 'UTF-8');
    $when = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
    if ($present_names) {
      $list = array_slice($present_names, 0, 3);
      $suffix = count($present_names) > 3 ? ' e mais ' . (count($present_names)-3) : '';
      $flash = "$actor registrou presença de " . htmlspecialchars(implode(', ', $list), ENT_QUOTES, 'UTF-8') . $suffix . " em $when.";
    } else {
      $flash = "$actor atualizou presenças em $when.";
    }
  } catch (Throwable $e) {
    $error = 'Falha ao salvar presenças.';
  }
}

// Estagiários da turma selecionada
$estagiarios = [];
$checked = [];
if ($turma_id) {
  try {
    $stmt = $pdo->prepare('SELECT e.id, e.nome, e.matricula FROM turma_estagiarios te INNER JOIN estagiarios e ON e.id = te.estagiario_id WHERE te.turma_id = ? ORDER BY e.nome');
    $stmt->execute([$turma_id]);
    $estagiarios = $stmt->fetchAll();

    $q = $pdo->prepare('SELECT estagiario_id FROM presencas WHERE turma_id = ? AND data = ? AND presente = 1');
    $q->execute([$turma_id, $selectedDate->format('Y-m-d')]);
    $checked = array_column($q->fetchAll(), 'estagiario_id');
  } catch (Throwable $e) { /* tabelas podem não existir ainda */ }
}

// Log do dia com horários exatos
$logs = [];
if ($turma_id) {
  try {
    $qlog = $pdo->prepare('SELECT e.nome, p.created_at FROM presencas p INNER JOIN estagiarios e ON e.id = p.estagiario_id WHERE p.turma_id = ? AND p.data = ? AND p.presente = 1 ORDER BY p.created_at ASC');
    $qlog->execute([$turma_id, $selectedDate->format('Y-m-d')]);
    $logs = $qlog->fetchAll();
  } catch (Throwable $e) { /* created_at pode não existir ainda */ }
}

// Calendário
$first = new DateTime("$year-$month-01");
$startDow = (int)$first->format('N'); // 1-7 (seg-dom)
$daysInMonth = (int)$first->format('t');
$weeks = [];
$week = array_fill(0,7,null);
$idx = $startDow-1;
for ($d=1; $d<=$daysInMonth; $d++) {
  $week[$idx] = $d;
  $idx++;
  if ($idx === 7) { $weeks[] = $week; $week = array_fill(0,7,null); $idx = 0; }
}
if ($idx !== 0) { $weeks[] = $week; }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Presença de Estagiários | CTIC CESIT</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ctic/assets/css/style.css" />
  <style>
    .calendar{background:var(--card);border:1px solid var(--border);border-radius:12px;overflow:hidden}
    .calendar .bar{display:flex;align-items:center;gap:8px;padding:10px 12px;border-bottom:1px solid var(--border);background:linear-gradient(180deg,#0d1413,#0a1110)}
    .cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:#0a0f0f;border-top:1px solid var(--border)}
    .cal-cell{min-height:68px;background:#0b1111;padding:8px;border-bottom:1px solid #0d1515;cursor:pointer;position:relative}
    .cal-cell .num{font-size:12px;color:var(--muted)}
    .cal-cell.active{outline:1px solid var(--primary); box-shadow:var(--glow)}
    .cal-head{display:grid;grid-template-columns:repeat(7,1fr);gap:1px;background:#0a0f0f}
    .cal-head div{padding:8px 6px;text-align:center;color:var(--muted);font-size:12px;background:#0c1212;border-bottom:1px solid var(--border)}
    .presence{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:12px}
  </style>
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
    <section class="hero" style="grid-template-columns:1fr 1.2fr; align-items:start">
      <div class="calendar">
        <div class="bar">
          <form method="get" style="display:flex;gap:8px;margin:0">
            <input type="hidden" name="turma" value="<?php echo (int)$turma_id; ?>">
            <select name="m" class="btn secondary" style="padding:6px 10px">
              <?php for($m=1;$m<=12;$m++): ?>
                <option value="<?php echo $m; ?>" <?php echo ($m===$month?'selected':''); ?>><?php echo str_pad((string)$m,2,'0',STR_PAD_LEFT); ?></option>
              <?php endfor; ?>
            </select>
            <select name="y" class="btn secondary" style="padding:6px 10px">
              <?php for($y=$year-1;$y<=$year+1;$y++): ?>
                <option value="<?php echo $y; ?>" <?php echo ($y===$year?'selected':''); ?>><?php echo $y; ?></option>
              <?php endfor; ?>
            </select>
            <button class="btn primary" type="submit">Ir</button>
          </form>
          <span class="title" style="margin-left:auto;color:var(--muted)"><?php echo $selectedDate->format('Y-m-d'); ?></span>
        </div>
        <div class="cal-head">
          <div>Seg</div><div>Ter</div><div>Qua</div><div>Qui</div><div>Sex</div><div>Sáb</div><div>Dom</div>
        </div>
        <div class="cal-grid">
          <?php foreach($weeks as $w): foreach($w as $i=>$d): ?>
            <?php if ($d===null): ?>
              <div class="cal-cell" style="background:#0a0f0f"></div>
            <?php else: $isSel = ($d===(int)$selectedDate->format('j')); $href = sprintf('?turma=%d&m=%d&y=%d&d=%d',$turma_id,$month,$year,$d); ?>
              <a href="<?php echo $href; ?>" class="cal-cell <?php echo $isSel?'active':''; ?>">
                <div class="num"><?php echo $d; ?></div>
              </a>
            <?php endif; ?>
          <?php endforeach; endforeach; ?>
        </div>
      </div>

      <div class="presence">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
          <?php if (!empty($_SESSION['user']['avatar'])): ?>
            <span class="avatar"><img src="<?php echo htmlspecialchars($_SESSION['user']['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar"/></span>
          <?php endif; ?>
          <div>
            <div style="font-weight:600"><?php echo htmlspecialchars($_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'Usuário', ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="muted" style="font-size:12px">Você está registrando presenças</div>
          </div>
        </div>
        <form method="get" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
          <label style="display:flex;gap:8px;align-items:center">
            <span>Turma</span>
            <select name="turma" class="btn secondary" onchange="this.form.submit()">
              <?php foreach($turmas as $t): ?>
                <option value="<?php echo (int)$t['id']; ?>" <?php echo ((int)$t['id']===$turma_id?'selected':''); ?>><?php echo htmlspecialchars($t['nome'], ENT_QUOTES, 'UTF-8'); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <input type="hidden" name="m" value="<?php echo $month; ?>">
          <input type="hidden" name="y" value="<?php echo $year; ?>">
          <input type="hidden" name="d" value="<?php echo (int)$selectedDate->format('j'); ?>">
        </form>

        <?php if ($flash): ?><div class="alert" style="border-color: rgba(0,255,136,.35); color: #bfffdc; background: rgba(0,255,136,.06)"><?php echo $flash; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert"><?php echo $error; ?></div><?php endif; ?>

        <form method="post" class="login-form">
          <input type="hidden" name="save_presence" value="1">
          <input type="hidden" name="turma_id" value="<?php echo (int)$turma_id; ?>">
          <input type="hidden" name="date" value="<?php echo $selectedDate->format('Y-m-d'); ?>">

          <?php if (!$turma_id): ?>
            <p class="muted">Nenhuma turma encontrada. Crie em <a href="/ctic/sistemas/presenca_estagiarios/turmas.php" class="btn ghost">Turmas</a>.</p>
          <?php else: ?>
            <div style="max-height:360px;overflow:auto;border:1px solid var(--border);border-radius:8px;padding:8px">
            <?php foreach($estagiarios as $e): $eid=(int)$e['id']; $isC=in_array($eid,$checked,true); ?>
              <label style="display:flex;align-items:center;justify-content:space-between;border:1px solid var(--border);border-radius:8px;padding:8px;margin-bottom:6px;background:#0a1010">
                <span><?php echo htmlspecialchars($e['nome'], ENT_QUOTES, 'UTF-8'); ?><?php if(!empty($e['matricula'])) echo ' — '.htmlspecialchars($e['matricula'], ENT_QUOTES, 'UTF-8'); ?></span>
                <input type="checkbox" name="presenca[]" value="<?php echo $eid; ?>" <?php echo $isC?'checked disabled title="Já registrado"':''; ?> />
              </label>
            <?php endforeach; ?>
            </div>
            <div class="actions">
              <button class="btn primary" type="submit">Salvar Presenças</button>
              <a href="/ctic/sistemas/presenca_estagiarios/turmas.php" class="btn secondary">Gerenciar Turmas</a>
            </div>
          <?php endif; ?>
        </form>
        <div class="about" style="margin-top:12px">
          <h4>Registros do dia</h4>
          <?php if (!$logs): ?>
            <p class="muted">Nenhum registro encontrado para esta data.</p>
          <?php else: ?>
            <div class="terminal-body" style="max-height:200px; overflow:auto">
              <?php foreach($logs as $row): ?>
                <div><span class="muted"><?php echo htmlspecialchars((string)$row['created_at'], ENT_QUOTES, 'UTF-8'); ?></span> — presença de <span class="ok"><?php echo htmlspecialchars((string)$row['nome'], ENT_QUOTES, 'UTF-8'); ?></span></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
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
