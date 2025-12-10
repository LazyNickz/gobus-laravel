<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteStatsTable extends Migration {
    public function up() {
        Schema::create('route_stats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('from_terminal_id')->nullable();
            $table->unsignedBigInteger('to_terminal_id')->nullable();
            $table->date('date')->nullable();
            $table->string('day_of_week')->nullable(); // mon,tue,...
            $table->time('time_slot')->nullable(); // canonical time bucket
            $table->integer('bookings')->default(0);
            $table->decimal('demand_score', 8, 4)->default(0); // normalized for ML ranking
            $table->timestamps();

            $table->index(['from_terminal_id','to_terminal_id','date']);
        });
    }
    public function down() {
        Schema::dropIfExists('route_stats');
    }
}
