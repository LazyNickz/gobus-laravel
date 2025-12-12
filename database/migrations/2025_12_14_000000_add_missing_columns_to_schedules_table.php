
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToSchedulesTable extends Migration
{
    public function up()
    {
        Schema::table('schedules', function (Blueprint $table) {
            if (!Schema::hasColumn('schedules', 'bus_type')) {
                $table->string('bus_type')->default('regular')->after('created_by');
            }
            if (!Schema::hasColumn('schedules', 'trip_type')) {
                $table->string('trip_type')->default('single')->after('bus_type');
            }
            if (!Schema::hasColumn('schedules', 'capacity')) {
                $table->unsignedInteger('capacity')->default(0)->after('trip_type');
            }
        });
    }

    public function down()
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['bus_type', 'trip_type', 'capacity']);
        });
    }
}
