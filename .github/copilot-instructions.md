# Copilot / AI Agent Instructions for sistema-inventario-ti

Purpose: Help an AI coding assistant become productive quickly in this PHP inventory project.

- **Big picture:** This is a monolithic PHP app (procedural pages) that manages products and assembled machines, backed by MySQL. Pages are individual PHP files (e.g., `index.php`, `products.php`, `machines.php`) and share utilities via `includes/` and `config.php`.

- **Quick start (developer):**
  - Preferred: `cd devops && docker-compose up -d` to start app + DB (see `devops/docker-compose.yml`).
  - Manual: import `database_schema.sql`, configure DB creds in `config.php`, ensure `uploads/` writable, PHP 7.4+ with `pdo_mysql`, `gd`, `mbstring`.

- **Key files to inspect first:**
  - `config.php` — DB connection, session helpers, central logging functions (`logProductMovement`, `logMachineMovement`, `logAdminActivity`).
  - `database_schema.sql` — canonical schema and movement tables.
  - `includes/header.php` / `includes/footer.php` — UI shell, sidebar, theme handling.
  - `products.php`, `add_product.php`, `product_stock_in.php`, `product_stock_out.php` — show how stock changes are performed.
  - `scanner.php`, `generate_barcode_image.php`, `barcode_print.php` — barcode/QR code flow.

- **Architecture & dataflow patterns (important):**
  - Stock changes MUST be accompanied by a log insert. Typical sequence: start transaction (when needed) → read current quantity → update table(s) → call `logProductMovement(...)` or `logMachineMovement(...)` → commit.
  - Admin actions should call `logAdminActivity(...)` with old/new values to preserve audit trail.

- **Project-specific conventions:**
  - Procedural single-file pages; shared behavior is via `config.php` and `includes/` files.
  - Set `$page_title` before including `includes/header.php` and optionally `$hide_sidebar = true` for public pages.
  - Use prepared PDO statements via `getConnection()`; follow existing `getConnection()` usage in `config.php`.
  - Movement types use Portuguese terms: `entrada`, `saida`, `ajuste`. Status enums: `available`, `sold`, `reserved`, `maintenance`, `testing` for machines.

- **When changing stock or machine status — do this exact sequence:**
  1. Read current quantity/status.
  2. Update `products` or `ready_machines` table.
  3. Call `logProductMovement(...)` or `logMachineMovement(...)` with previous/new values.
  4. Insert into `product_outputs`/`machine_outputs` if removal.
  5. Use DB transaction for multi-step ops.

- **Safety notes & known issues (discoverable):**
  - `config.php` currently contains DB credentials and plain authentication logic in this repo (demo/demo-users). Avoid exposing secrets when modifying code.
  - Password handling in some demo scripts is not secure; do not implement new auth flows assuming secure storage unless refactoring.

- **Developer workflows & useful scripts:**
  - Backups: `backup_database.ps1` (PowerShell) — Windows-friendly backup automation.
  - Migration helpers: `migrate_passwords.php`, `execute_warranty_sql.php`.
  - Tests: there are no automated tests in the repo; prefer small, manual validation and use local Docker stack.

- **Editing guidelines for PRs:**
  - Preserve existing logging calls and behaviour when changing inventory code paths.
  - Keep changes minimal and consistent with procedural style unless an explicit refactor is requested.
  - Add DB migrations (SQL) alongside schema changes and update `database_schema.sql`.

- **Examples to reference when generating code:**
  - `config.php` — use `getConnection()` and logging function signatures.
  - `add_product.php` / `edit_product.php` — example patterns for inserts/updates and file uploads.

If anything above is unclear or you want more detailed examples (e.g., a sample PR that updates stock safely), tell me which area to expand and I will iterate.
