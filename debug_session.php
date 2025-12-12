<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "Checking Database Connection...\n";
try {
    DB::connection()->getPdo();
    echo "Database connection established.\n";
} catch (\Exception $e) {
    echo "Could not connect to the database. " . $e->getMessage() . "\n";
    exit;
}

echo "Checking sessions table...\n";
if (Schema::hasTable('sessions')) {
    echo "Table 'sessions' exists.\n";
} else {
    echo "Table 'sessions' does NOT exist.\n";
}

echo "Current Session Driver: " . config('session.driver') . "\n";
echo "Session Cookie Name: " . config('session.cookie') . "\n";

