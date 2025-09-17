<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDiameterColumnsInPendingGeotagsTable extends Migration
{
    public function up()
    {
        Schema::table('pending_geotags', function (Blueprint $table) {
            // Drop old column
            $table->dropColumn('diameter');

            // Add correct columns
            $table->decimal('stem_diameter', 8, 2)->nullable();
            $table->decimal('canopy_diameter', 8, 2)->nullable();
        });
    }

    public function down()
    {
        Schema::table('pending_geotags', function (Blueprint $table) {
            $table->dropColumn(['stem_diameter', 'canopy_diameter']);
            $table->decimal('diameter', 8, 2)->nullable();
        });
    }
}
