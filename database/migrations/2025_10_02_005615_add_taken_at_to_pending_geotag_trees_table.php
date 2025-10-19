<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTakenAtToPendingGeotagTreesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pending_geotag_trees', function (Blueprint $table) {
            $table->dateTime('taken_at')->nullable()->after('code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pending_geotag_trees', function (Blueprint $table) {
            $table->dropColumn('taken_at');
        });
    }
}
