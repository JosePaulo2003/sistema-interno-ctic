<?php
// Sim, uma conexão PDO enxuta. Se quebrar, pelo menos quebra com estilo.
$DB_CONFIG = [
  'host' => '127.0.0.1', // ajuste se necessário
  'port' => 3306,
  'dbname' => 'ctic',
  'user' => 'ctic_app', // crie este usuário no MariaDB
  'pass' => 'ctic2025', // troque por uma senha forte
  'charset' => 'utf8mb4',
];

try {
  $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $DB_CONFIG['host'], $DB_CONFIG['port'], $DB_CONFIG['dbname'], $DB_CONFIG['charset']
  );
  $pdo = new PDO($dsn, $DB_CONFIG['user'], $DB_CONFIG['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
} catch (Throwable $e) {
  // Eu poderia mascarar o erro, mas prefiro a verdade nua e crua durante o dev.
  http_response_code(500);
  echo 'Falha na conexão com o banco de dados.';
  // Para produção, esconda detalhes. Aqui vou mostrar um gostinho.
  // echo htmlspecialchars($e->getMessage());
  exit;
}
