<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('route_from');
            $table->string('route_to');
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time')->nullable();
            $table->string('bus_number')->nullable();
            $table->integer('seats')->default(0);
            $table->integer('available_seats')->default(0);
            $table->decimal('fare', 10, 2)->default(0);
            $table->enum('status', ['active', 'inactive', 'cancelled'])->default('active');
            $table->string('bus_type')->default('regular'); // regular, deluxe
            $table->enum('trip_type', ['single', 'round'])->default('single');
            $table->integer('capacity')->default(40);
            $table->string('created_by')->nullable();
            $table->timestamps();
            
            $table->index(['route_from', 'route_to']);
            $table->index('departure_time');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
