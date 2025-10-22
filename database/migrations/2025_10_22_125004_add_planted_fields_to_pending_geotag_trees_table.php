<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlantedFieldsToPendingGeotagTreesTable extends Migration
{
    public function up()
    {
        Schema::table('pending_geotag_trees', function (Blueprint $table) {
            // Exact planting date, nullable
            $table->date('planted_at')->nullable()->after('taken_at');

            // Flag to indicate if only year is provided
            $table->boolean('planted_year_only')->default(false)->after('planted_at');
        });
    }

    public function down()
    {
        Schema::table('pending_geotag_trees', function (Blueprint $table) {
            $table->dropColumn(['planted_at', 'planted_year_only']);
        });
    }
}
