# Guia de Instalação do Sistema de Estoque de TI

Este guia detalha os passos para instalar e configurar o sistema de estoque de TI em seu ambiente local.

## Pré-requisitos:
- Servidor Web (Apache2 ou Nginx)
- PHP 7.4 ou superior (com extensões `pdo_mysql`, `gd`, `mbstring`)
- MySQL/MariaDB
- Composer (opcional, para gerenciamento de dependências futuras)

## Passos para Instalação:

1.  **Extrair os arquivos:**
    Descompacte o arquivo `it_inventory_updated.zip` em seu diretório raiz do servidor web (ex: `/var/www/html/` ou `htdocs/`). O caminho final deve ser algo como `/var/www/html/it_inventory/`.

2.  **Configurar o Banco de Dados:**
    a. Crie um novo banco de dados MySQL/MariaDB (ex: `it_inventory_db`).
    b. Importe o arquivo `database_schema.sql` para o banco de dados recém-criado. Você pode fazer isso via phpMyAdmin, MySQL Workbench ou linha de comando:
    ```bash
    mysql -u seu_usuario -p seu_banco_de_dados < /caminho/para/it_inventory_updated/database_schema.sql
    ```
    c. Edite o arquivo `config.php` localizado em `it_inventory/config.php` para configurar as credenciais do seu banco de dados:
    ```php
    <?php
    // Configurações do Banco de Dados
    define("DB_HOST", "localhost");
    define("DB_NAME", "it_inventory_db"); // Nome do seu banco de dados
    define("DB_USER", "seu_usuario"); // Seu usuário do banco de dados
    define("DB_PASS", "sua_senha"); // Sua senha do banco de dados
    
    // Outras configurações...
    ?>
    ```

3.  **Configurar Permissões de Pasta (Linux/macOS):**
    Certifique-se de que o servidor web tenha permissões de escrita na pasta `uploads/products/` e `uploads/machines/` para que as imagens possam ser salvas.
    ```bash
    sudo chown -R www-data:www-data /caminho/para/it_inventory/uploads
    sudo chmod -R 755 /caminho/para/it_inventory/uploads
    ```
    (Substitua `www-data` pelo usuário do seu servidor web, se for diferente).

4.  **Ajustar Limite de Upload de Arquivos (Opcional - para imagens maiores):**
    Para permitir uploads de imagens de até 50MB, você precisará ajustar as configurações do PHP. Localize seu arquivo `php.ini` (geralmente em `/etc/php/<versao>/apache2/php.ini` ou `/etc/php/<versao>/fpm/php.ini`) e altere as seguintes diretivas:
    ```ini
    upload_max_filesize = 50M
    post_max_size = 50M
    memory_limit = 128M ; ou mais, se necessário
    ```
    Após as alterações, reinicie seu servidor web (Apache/Nginx) para que as mudanças entrem em vigor.

5.  **Acessar o Sistema:**
    Abra seu navegador e acesse `http://localhost/it_inventory/` (ou o caminho correspondente à sua instalação).

## Credenciais Padrão:
- **Usuário:** `admin`
- **Senha:** `admin123`

- **Usuário:** `user`
- **Senha:** `user123`

Em caso de dúvidas ou problemas, consulte a documentação online ou entre em contato com o suporte.

