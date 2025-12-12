<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder {
    public function run()
    {
        // admins
        DB::table('admins')->insertOrIgnore([
            [
                'name' => 'Admin',
                'email' => 'admin@gobus.local',
                'password' => Hash::make('admin123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // users
        DB::table('users')->insertOrIgnore([
            [
                'name' => 'Demo User',
                'email' => 'user@demo.local',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Jane Tester',
                'email' => 'jane@demo.local',
                'password' => Hash::make('test456'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // terminals
        DB::table('terminals')->insertOrIgnore([
            ['name'=>'Manila','city'=>'Manila','created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Cebu','city'=>'Cebu','created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Davao','city'=>'Davao','created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Baguio','city'=>'Baguio','created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Iloilo','city'=>'Iloilo','created_at'=>now(),'updated_at'=>now()],
        ]);

        // Insert admin@gobus.local / admin123 if not exists
        DB::table('admins')->updateOrInsert(
            ['email' => 'admin@gobus.local'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
