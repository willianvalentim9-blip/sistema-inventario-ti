# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is an IT inventory management system built with PHP and MySQL. It manages hardware products, assembled machines ready for sale, tracks stock movements, and generates QR codes/barcodes for inventory items.

**Stack:** PHP 7.4+, MySQL/MariaDB, Bootstrap 5, JavaScript

## Architecture

### Database Layer
- **Connection:** Centralized through `config.php`'s `getConnection()` function using PDO with singleton pattern
- **Configuration:** Database credentials are in `config.php` (lines 10-14) and Docker Compose config
- **Schema:** Defined in `database_schema.sql` with main tables:
  - `users` - Authentication and user management with role-based access (admin/user)
  - `products` - Individual IT hardware items (CPUs, RAM, cables, etc.) with quantity tracking
  - `ready_machines` - Assembled computers with specifications and quantity
  - `product_movements` / `machine_movements` - Audit trail for all stock changes
  - `product_outputs` / `machine_outputs` - Detailed history of stock removals (sales, internal use, etc.)
  - `admin_logs` - Administrative actions with IP and user agent tracking
  - `system_settings` - Key-value configuration store

### Authentication & Authorization
- Session-based authentication managed in `config.php`
- Helper functions: `isLoggedIn()`, `isAdmin()`, `requireLogin()`, `requireAdmin()`
- Two-tier access: regular users and administrators
- Admin users have access to user management, system settings, and detailed logs

### Logging System
Critical for audit trails - three main logging functions in `config.php`:
- `logProductMovement()` - Tracks all product stock changes (entrada/saida/ajuste)
- `logMachineMovement()` - Tracks machine status and movement changes
- `logAdminActivity()` - Records administrative actions with old/new values as JSON

**IMPORTANT:** Always call appropriate logging functions when modifying inventory quantities or performing admin actions.

### File Structure
- **Root PHP files:** Each page is a standalone PHP file (e.g., `products.php`, `dashboard.php`, `login.php`)
- **includes/:** Shared components
  - `header.php` - Navigation sidebar, top bar, theme switching
  - `footer.php` - Closing HTML, JavaScript includes
  - `log_functions.php` - Additional logging utilities
  - `validation.php` - Input validation helpers
  - `dashboard_stats.php` - Dashboard metrics queries
  - `export_functions.php` - CSV export functionality
- **uploads/:** User-uploaded content (products/, machines/, avatars/)
- **barcode-lib/:** Third-party barcode generation library
- **PHPMailer-master/:** Email functionality library
- **css/:** Bootstrap + custom themes (themes.css, custom.css)
- **js/:** Custom JavaScript (custom.js, enhanced_ui.js, mobile-improvements.js, simple-mask-money.js)

### Theme System
- Multi-theme support via `system_settings` table
- User preferences stored as `user_theme_{user_id}` keys
- Theme applied in `includes/header.php` via `theme-{color}` body class
- Theme toggle button sends AJAX to `save_theme_preference.php`

### Barcode/QR Code System
- Library: `barcode-lib/` (Picqer barcode generator)
- Generation: `generate_barcode_image.php` creates barcode images
- Scanning: `scanner.php` and `scanner_modal.php` use device camera for QR/barcode scanning
- Printing: `barcode_print.php` for printable labels

## Common Development Tasks

### Running the Application

**Using Docker (Recommended):**
```bash
cd devops
docker-compose up -d
```
Access at: http://localhost:8081

**Manual Setup:**
1. Import `database_schema.sql` into MySQL
2. Configure `config.php` with database credentials
3. Ensure PHP 7.4+ with extensions: pdo_mysql, gd, mbstring
4. Set write permissions on `uploads/` directories
5. Point web server to project root

### Database Configuration
**Docker:** Uses environment variables defined in `devops/docker-compose.yml`
- DB_HOST: `db`
- DB_NAME: `it_inventory`
- DB_USER: `admin`
- DB_PASS: `@#8520@#`

**Manual:** Edit `config.php` lines 10-14

### Default Credentials
See `documentation/demo_users.md`:
- **Admin:** username `admin`, password `@#8520@#`
- Email: `admin@example.com`

### Adding New Features That Modify Stock

When creating functionality that changes product/machine quantities:

1. **Start transaction** (if multiple operations)
2. **Fetch current quantity** from products/ready_machines table
3. **Update quantity** in the table
4. **Call logging function** immediately:
   ```php
   logProductMovement($productId, $_SESSION['user_id'], 'entrada'|'saida',
                      $quantity, $previousQuantity, $newQuantity, $reason);
   ```
5. **Create output record** (for stock removals) in product_outputs/machine_outputs
6. **Commit transaction**

### Working with the Sidebar Navigation

Sidebar is in `includes/header.php` (lines 40-124). It:
- Collapses on mobile via `toggleSidebar()` JavaScript
- Highlights active page using `$_SERVER['PHP_SELF']`
- Supports nested collapsible sections (e.g., Movimentações → Entradas/Saídas)
- Shows admin-only links when `isAdmin()` returns true

### Image Upload Handling

- Products: `uploads/products/`
- Machines: `uploads/machines/`
- Avatars: `uploads/avatars/`
- Upload script: `upload_image.php`
- Deletion script: `delete_image.php`
- Max file size configurable in php.ini (default: 50MB per installation docs)

### System Settings Management

Settings stored in `system_settings` table as key-value pairs:
- Loaded in `config.php` lines 42-63
- Merged with defaults array
- Examples: `system_name`, `company_name`, `company_logo`, `theme_color`
- Admin can modify via `settings.php`

## Key Conventions

1. **Session must be active:** All authenticated pages call `requireLogin()` or `requireAdmin()` after including `config.php`
2. **PDO prepared statements:** Always use parameterized queries via `$pdo->prepare()` to prevent SQL injection
3. **Error logging:** Use `error_log()` for server-side errors, not user-facing output
4. **HTML escaping:** Use `htmlspecialchars()` when outputting user data to prevent XSS
5. **Page title:** Set `$page_title` variable before including `includes/header.php`
6. **Hide sidebar on login/public pages:** Set `$hide_sidebar = true` before including header

## Database Interaction Patterns

**Fetching data:**
```php
$pdo = getConnection();
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();
```

**Inserting with logging:**
```php
$pdo = getConnection();
$stmt = $pdo->prepare("INSERT INTO products (name, quantity, ...) VALUES (?, ?, ...)");
$stmt->execute([$name, $quantity, ...]);
$productId = $pdo->lastInsertId();

logAdminActivity($_SESSION['user_id'], 'CREATE_PRODUCT', 'products', $productId);
```

**Updating with audit trail:**
```php
// Fetch old values first
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$oldValues = $stmt->fetch();

// Perform update
$stmt = $pdo->prepare("UPDATE products SET name = ?, quantity = ? WHERE id = ?");
$stmt->execute([$newName, $newQuantity, $id]);

// Log changes
logAdminActivity($_SESSION['user_id'], 'UPDATE_PRODUCT', 'products', $id, $oldValues, ['name' => $newName, 'quantity' => $newQuantity]);
```

## Important Notes

- **Password Storage:** Currently uses plain text comparison (`login.php` line 43). This is a known security issue for a demo system.
- **Session Security:** Sessions are started in `config.php` but lack additional hardening (regeneration, timeout checks).
- **Movement Types:** Product/machine movements use types: `entrada` (stock in), `saida` (stock out), `ajuste` (adjustment)
- **Status Values:**
  - Products: `available`, `low_stock`, `out_of_stock`, `discontinued`
  - Machines: `available`, `sold`, `reserved`, `maintenance`, `testing`
- **ID Generation:** Uses MySQL AUTO_INCREMENT for all primary keys
- **Unique Constraints:** SKU, barcode, qr_code, serial_number have UNIQUE constraints where applicable

## Common Files Reference

- **Dashboard:** `dashboard.php` - Overview with charts, recent items, stock alerts
- **Product Management:** `products.php`, `add_product.php`, `edit_product.php`, `view_product.php`, `delete_product.php`
- **Machine Management:** `ready_machines.php`, `add_machine.php`, `edit_machine.php`, `view_machine.php`, `delete_machine.php`
- **Stock Movement:** `product_stock_in.php`, `product_stock_out.php`, `machine_stock_in.php`, `machine_stock_out.php`
- **Movement Logs:** `product_movements_log.php`, `machine_movements_log.php`, `product_inputs_log.php`, `product_outputs_log.php`
- **Admin:** `users.php`, `settings.php`, `admin_logs.php`, `system_logs.php`
- **Utilities:** `scanner.php`, `search.php`, `export_csv.php`, `barcode_print.php`
