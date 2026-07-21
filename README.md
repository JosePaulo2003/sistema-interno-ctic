# Sistema Interno CTIC

Sistema interno em PHP para centralizar acessos e ferramentas do setor de TI.

## Funcionalidades

- Autenticacao de usuarios.
- Gerenciamento de usuarios administrativos.
- Perfil com avatar.
- Modulo de presenca de estagiarios.

## Requisitos

- PHP 8+
- MySQL ou MariaDB
- Servidor web com suporte a PHP
- Extensao PDO MySQL habilitada

## Configuracao

1. Copie `.env.example` para `.env`.
2. Ajuste as credenciais do banco.
3. Importe os arquivos SQL em `config/`.
4. Configure o servidor web apontando para a raiz do projeto.

## Seguranca

- Nao versionar `.env`.
- Nao versionar uploads reais de usuarios.
- Trocar a senha inicial apos instalar o sistema.
- Usar usuario de banco com permissoes restritas em producao.
