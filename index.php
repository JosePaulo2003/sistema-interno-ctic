<?php
// Eu sei, um index.php raiz. Poderia ser um framework inteiro, mas vamos começar pelo óbvio.
// Este arquivo monta a landing "hacker profissional" com canvas Matrix e terminal fake.
// Se eu fizer direito, você nem vai notar que é só HTML + CSS + JS com cara de missão impossível.
// E sim, eu vou barrar quem não está logado, porque porta aberta só serve para vento.
session_start();
if (empty($_SESSION['user'])) {
  header('Location: /ctic/login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>CTIC | Console de Sistemas</title>
  <meta name="description" content="Centro de Tecnologia da Informação e Comunicação - Console de Sistemas" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/ctic/assets/css/style.css" />
</head>
<body>
  <!-- Eu poderia deixar estático, mas claro que preferi um canvas que consome GPU só para parecer sério. -->
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
      <a href="/ctic/profile.php" class="btn ghost">
        <?php if (!empty($_SESSION['user']['avatar'])): ?>
          <span class="avatar"><img src="<?php echo htmlspecialchars($_SESSION['user']['avatar'], ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar"/></span>
        <?php endif; ?>
        <span>Perfil</span>
      </a>
      <?php if (!empty($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'dev'): ?>
        <a href="/ctic/admin/users.php" class="btn ghost">Usuários</a>
      <?php endif; ?>
      <a href="/ctic/logout.php" class="btn ghost">Sair</a>
    </nav>
  </header>

  <main class="content" role="main">
    <?php if (!empty($_SESSION['just_logged_in'])): ?>
      <div id="welcome" class="welcome-overlay" role="status" aria-live="polite" data-welcome-duration="6500">
        <div class="welcome-card">
          <div class="terminal-bar">
            <span class="dot red" aria-hidden="true"></span>
            <span class="dot yellow" aria-hidden="true"></span>
            <span class="dot green" aria-hidden="true"></span>
            <span class="title">/session/welcome</span>
          </div>
          <div class="welcome-body">
            <h3>Bem-vindo, <?php echo htmlspecialchars($_SESSION['user']['name'] ?? ''); ?>!</h3>
            <div id="welcomeLog" class="welcome-log terminal-body" style="max-height:140px"></div>
            <div class="welcome-progress"><div class="bar" id="welcomeBar" style="width:0%"></div></div>
            <div class="welcome-actions">
              <button id="welcomeSkip" class="btn secondary small" type="button" aria-label="Pular animação">Pular</button>
            </div>
          </div>
        </div>
      </div>
      <?php unset($_SESSION['just_logged_in']); endif; ?>
    <section class="hero" aria-labelledby="hero-title">
      <div class="terminal" role="region" aria-live="polite" aria-label="Terminal de Status">
        <div class="terminal-bar">
          <span class="dot red" aria-hidden="true"></span>
          <span class="dot yellow" aria-hidden="true"></span>
          <span class="dot green" aria-hidden="true"></span>
          <span class="title">/var/www/html/ctic — status.log</span>
        </div>
        <pre class="terminal-body" id="terminal">
<span class="muted">Inicializando ambiente...</span>
        </pre>
      </div>
      <div class="cta">
        <h2 id="hero-title">Console CTIC CESIT</h2>
        <p>Acesso centralizado com foco em segurança e disponibilidade. Gerencie seu perfil e configurações.</p>
      </div>
    </section>


    <section class="grid" aria-labelledby="apps-title">
      <h3 id="apps-title">Sistemas</h3>
      <div class="cards">
        <a class="card" href="/ctic/sistemas/presenca_estagiarios/" aria-label="Acessar Presença de Estagiários">
          <div class="card-icon">🗓️</div>
          <div class="card-body">
            <h4>Presença de Estagiários</h4>
            <p>Calendário e marcação de presença por turma.</p>
          </div>
        </a>
        <a class="card" href="/ctic/sistemas/recursos/" aria-label="Acessar Gerenciamento de Recursos">
          <div class="card-icon">🛡️</div>
          <div class="card-body">
            <h4>Gerenciamento de Recursos</h4>
            <p>Acesso ao controle centralizado de recursos de T.I</p>
          </div>
        </a>
      </div>
    </section>

    <section id="sobre" class="about">
      <h3>Sobre</h3>
      <p>O CTIC integra sistemas críticos com foco em segurança, disponibilidade e usabilidade. Esta interface oferece uma experiência unificada para acesso rápido e gerenciamento eficiente.</p>
    </section>

    <section id="contato" class="contact">
      <h3>Contato</h3>
      <p>Precisa de suporte? Entre em contato com o desenvolvedor</p>
      <ul>
        <li>Email: ctic_uea@uea.edu.br</li>
        <li>Whatssap: (92) 98545-2285 </li>
      </ul>
    </section>
  </main>

  <footer class="site-footer" role="contentinfo">
    <p>© <?php echo date('Y'); ?> CTIC CESIT — Todos os direitos reservados.</p>
  </footer>

  <script src="/ctic/assets/js/app.js"></script>
  <script src="/ctic/assets/js/welcome.js"></script>
</body>
</html>
