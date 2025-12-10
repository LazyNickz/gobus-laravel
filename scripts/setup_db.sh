#!/usr/bin/env bash
# Create SQLite file, clear config, run migrations and optional seeder.
set -e

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

echo "Using PHP:"
php -v

# ensure database dir exists
mkdir -p database
DB_FILE="$ROOT_DIR/database/database.sqlite"
if [ ! -f "$DB_FILE" ]; then
  echo "Creating SQLite database file at $DB_FILE"
  touch "$DB_FILE"
  chmod 664 "$DB_FILE" || true
else
  echo "SQLite DB already exists: $DB_FILE"
fi

echo "Clearing config cache"
php artisan config:clear || true
php artisan cache:clear || true

# If you plan to use session/cache/queue with DB driver, create their migrations first
read -p "Create session/cache/queue tables before migrate? (y/N) " create_db_tables
if [[ "$create_db_tables" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  php artisan session:table || true
  php artisan cache:table || true
  php artisan queue:table || true
fi

echo "Running migrations"
php artisan migrate --force

read -p "Run DemoSeeder if present? (y/N) " run_seeder
if [[ "$run_seeder" =~ ^([yY][eE][sS]|[yY])$ ]]; then
  php artisan db:seed --class=DemoSeeder || echo "DemoSeeder not found or failed"
fi

echo "Done. You can run the app with: php artisan serve"
