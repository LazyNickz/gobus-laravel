<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTable extends Migration {
    public function up() {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_terminal_id')->nullable();
            $table->unsignedBigInteger('to_terminal_id')->nullable();
            $table->time('time'); // HH:MM:SS
            $table->date('date')->nullable(); // optional single-date schedule
            $table->enum('bus_type',['regular','deluxe'])->default('regular');
            $table->integer('capacity')->default(40);
            $table->decimal('price', 10, 2)->default(0);
            $table->string('trip_type')->default('single');
            $table->timestamps();

            $table->foreign('from_terminal_id')->references('id')->on('terminals')->onDelete('set null');
            $table->foreign('to_terminal_id')->references('id')->on('terminals')->onDelete('set null');
        });
    }
    public function down() {
        Schema::dropIfExists('schedules');
    }
}
