#!/bin/bash
# Wait for DB (optional)
sleep 5

# Run Laravel migrations
php artisan migrate --force

# Start Apache
apache2-foreground