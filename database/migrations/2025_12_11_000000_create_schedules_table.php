<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTable extends Migration
{
    public function up()
    {
        // If the table already exists, skip to avoid "table already exists" errors.
        if (Schema::hasTable('schedules')) {
            return;
        }

        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('route_from');
            $table->string('route_to');
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time')->nullable();
            $table->string('bus_number')->nullable();
            $table->unsignedInteger('seats')->default(0);
            $table->unsignedInteger('available_seats')->default(0);
            $table->decimal('fare', 8, 2)->default(0);
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable(); // admin user id
            $table->timestamps();

            $table->index(['route_from','route_to']);
            $table->index('departure_time');
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedules');
    }
}
