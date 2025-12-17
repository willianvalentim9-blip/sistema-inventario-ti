# Diretório de Configuração

Este diretório contém os arquivos de configuração do sistema.

## Arquivos

- **database.php** - Configurações do banco de dados MySQL/MariaDB
- **database_sqlite.php** - Configurações alternativas para SQLite (backup)

## Como usar

Todos os arquivos do sistema devem incluir o `config.php` da raiz:

```php
require_once __DIR__ . '/../config.php';  // De dentro de um subdiretório
require_once __DIR__ . '/../../config.php';  // De dentro de modules/*/
```

O arquivo `config.php` na raiz carrega automaticamente as configurações do banco de dados deste diretório.

## Segurança

- Nunca commite senhas reais no repositório
- Use variáveis de ambiente em produção
- Mantenha este diretório fora do document root em produção
