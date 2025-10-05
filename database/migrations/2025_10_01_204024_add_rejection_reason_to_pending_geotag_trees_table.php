<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRejectionReasonToPendingGeotagTreesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pending_geotag_trees', function ($table) {
            $table->string('rejection_reason')->nullable();
        });
    }
    public function down()
    {
        Schema::table('pending_geotag_trees', function ($table) {
            $table->dropColumn('rejection_reason');
        });
    }
}
