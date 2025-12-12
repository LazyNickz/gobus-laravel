<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerminalsTable extends Migration
{
    public function up()
    {
        Schema::create('terminals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('terminals');
    }
}
