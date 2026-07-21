<?php

declare(strict_types=1);

$DB_CONFIG = [
  'host' => getenv('DB_HOST') ?: '127.0.0.1',
  'port' => (int) (getenv('DB_PORT') ?: 3306),
  'dbname' => getenv('DB_NAME') ?: 'ctic',
  'user' => getenv('DB_USER') ?: 'ctic_app',
  'pass' => getenv('DB_PASS') ?: '',
  'charset' => getenv('DB_CHARSET') ?: 'utf8mb4',
];

try {
  $dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $DB_CONFIG['host'],
    $DB_CONFIG['port'],
    $DB_CONFIG['dbname'],
    $DB_CONFIG['charset']
  );

  $pdo = new PDO($dsn, $DB_CONFIG['user'], $DB_CONFIG['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo 'Falha na conexao com o banco de dados.';
  exit;
}
