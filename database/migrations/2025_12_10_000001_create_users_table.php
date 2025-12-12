<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration {
    public function up() {
        // avoid trying to create the table if it already exists (prevents the 1050 error)
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password'); // hashed
                $table->string('phone')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down() {
        Schema::dropIfExists('users');
    }
}
