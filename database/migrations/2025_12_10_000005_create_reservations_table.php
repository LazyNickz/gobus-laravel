<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration {
    public function up() {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->date('date');
            $table->time('time');
            $table->unsignedBigInteger('from_terminal_id')->nullable();
            $table->unsignedBigInteger('to_terminal_id')->nullable();
            $table->string('bus_type')->nullable();
            $table->integer('qty')->default(1);
            $table->json('seats')->nullable(); // reserved seat numbers
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('status',['pending','confirmed','cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('schedule_id')->references('id')->on('schedules')->onDelete('set null');
            $table->foreign('from_terminal_id')->references('id')->on('terminals')->onDelete('set null');
            $table->foreign('to_terminal_id')->references('id')->on('terminals')->onDelete('set null');
        });
    }
    public function down() {
        Schema::dropIfExists('reservations');
    }
}
