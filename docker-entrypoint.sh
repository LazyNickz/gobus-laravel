 #!/bin/bash
set -e

# Navigate to Laravel root
WORKDIR /var/www/html

RUN php artisan migrate --force

echo "Running migrations in sequence..."

# 1️⃣ Run core migrations
run php artisan migrate --path=/database/migrations/2025_12_10_000001_create_users_table.php --force
run php artisan migrate --path=/database/migrations/2025_12_10_000002_create_admins_table.php --force
run php artisan migrate --path=/database/migrations/2025_12_10_000003_create_terminals_table.php --force
run php artisan migrate --path=/database/migrations/2025_12_10_000005_create_reservations_table.php --force
run php artisan migrate --path=/database/migrations/2025_12_10_000006_create_route_stats_table.php --force

# 2️⃣ Run schedules-related migrations in order
run php artisan migrate --path=/database/migrations/2025_12_13_000000_create_schedules_table_fix.php --force
run php artisan migrate --path=/database/migrations/2025_12_14_000000_add_missing_columns_to_schedules_table.php --force
run php artisan migrate --path=/database/migrations/2025_12_15_000000_create_clean_schedules_table.php --force

echo "All migrations completed."

# Start PHP built-in server (or replace with your web server start command)
run php artisan serve --host=0.0.0.0 --port=80