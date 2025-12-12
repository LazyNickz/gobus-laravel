<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationsTable extends Migration {
    public function up() {
        // avoid creating if already exists
        if (! Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                // make schedule_id nullable so onDelete('set null') can work
                $table->unsignedBigInteger('schedule_id')->nullable()->index();
                $table->integer('qty')->default(1);
                $table->json('seats')->nullable();
                $table->string('status')->default('pending'); // pending, confirmed, cancelled
                $table->timestamps();

                // foreign key: schedule_id -> schedules(id); set null on delete
                $table->foreign('schedule_id')
                      ->references('id')->on('schedules')
                      ->onDelete('set null');
            });
        }
    }

    public function down() {
        Schema::dropIfExists('reservations');
    }
}

