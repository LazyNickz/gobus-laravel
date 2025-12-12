<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchedulesTableFix extends Migration
{
    public function up()
    {
        // Only create if schedules table is missing (safe to run even if previous migration was recorded)
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
            $table->decimal('fare', 10, 2)->default(0);
            $table->string('status')->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('bus_type')->default('regular');
            $table->string('trip_type')->default('single');
            $table->unsignedInteger('capacity')->default(0);
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
