# Task Manager API

Lightweight PHP REST API (MVC-like structure) for managing tasks.

## Project structure

- `index.php` — Front controller (delegates to `routes/api.php`).
- `routes/api.php` — Routes dispatcher that reads `routes/links_map.php` and calls controller actions.
- `routes/links_map.php` — Route map array (method, path, action) used by the dispatcher.
- `app/`
  - `models/Task.php` — DB access methods (all, find, create, update, delete).
  - `controllers/TaskController.php` — Controller methods: index, store, update, destroy, etc.
  - `views/JsonView.php` — Helper to render JSON responses.
- `database/`
  - `config.php` — PDO config (supports env vars: DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS).
  - `tables/tasks.sql` — SQL to create `task_manager` database and `tasks` table.

## Requirements

- PHP 7.4+ (tested with PHP 8.x)
- MySQL server

## Environment variables (optional)

The app reads DB connection values from `database/config.php`. You can set these env vars to override defaults:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=task_manager
export DB_USER=root
export DB_PASS=''
```

## Initialize database (one-time)

Interactive (recommended):

```bash
mysql -u root -p
# Then paste the SQL in `database/tables/tasks.sql`
```

## Run the server

Start the PHP built-in server from the project root. Use `index.php` as the front controller:

```bash
php -S localhost:8000 index.php
```

## Route map

Routes are defined in `routes/links_map.php`