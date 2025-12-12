<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$admin = DB::table('admins')->where('email', 'admin@gobus.local')->first();

if ($admin) {
    echo "Admin user found: " . $admin->email . "\n";
    if (Hash::check('admin123', $admin->password)) {
        echo "Password matches.\n";
    } else {
        echo "Password does NOT match.\n";
    }
} else {
    echo "Admin user NOT found.\n";
}

