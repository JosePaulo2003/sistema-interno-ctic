-- 1) Banco
CREATE DATABASE IF NOT EXISTS ctic
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 2) Usuário da aplicação (ajuste a senha!)
CREATE USER IF NOT EXISTS 'ctic_app'@'localhost' IDENTIFIED BY 'ctic2025';
-- 3) Permissões
GRANT ALL PRIVILEGES ON ctic.* TO 'ctic_app'@'localhost';
FLUSH PRIVILEGES;

-- 4) Estrutura
USE ctic;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(64) NOT NULL,
  nome VARCHAR(120) NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uk_usuarios_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6) Papéis e Avatar (eu coloco aqui, depois do USE e da tabela)
--    Papéis aceitos: 'user', 'supervisor do setor', 'estagiario tecnico', 'dev'
--    Se sua versão do MariaDB não aceitar IF NOT EXISTS, remova essa cláusula.
ALTER TABLE usuarios
  ADD COLUMN IF NOT EXISTS role ENUM('user','supervisor do setor','estagiario tecnico','dev') NOT NULL DEFAULT 'user',
  ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(255) NULL;

-- 7) Promover usuário inicial a desenvolvedor
UPDATE usuarios SET role = 'dev' WHERE username = 'ayano_sakawa';

-- 5) Usuário inicial
INSERT INTO usuarios (username, nome, password_hash)
VALUES (
  'ayano_sakawa',
  'Ayano Sakawa',
  '$2a$12$VMCVh2BxASX3565Gwio0KedfLH8TagPx4aEJcfgdQRYXQEaSLezx6' -- a senha é desenvolvedor
);