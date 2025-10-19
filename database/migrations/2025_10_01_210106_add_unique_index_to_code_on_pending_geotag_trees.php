<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUniqueIndexToCodeOnPendingGeotagTrees extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pending_geotag_trees', function ($table) {
            $table->unique('code');
        });
    }

    public function down()
    {
        Schema::table('pending_geotag_trees', function ($table) {
            $table->dropUnique(['code']);
        });
    }
}
