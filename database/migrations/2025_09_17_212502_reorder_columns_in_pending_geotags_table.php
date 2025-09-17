<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReorderColumnsInPendingGeotagsTable extends Migration
{
    public function up()
    {
        Schema::table('pending_geotags', function (Blueprint $table) {
            $table->decimal('stem_diameter', 8, 2)->nullable()->after('height');
            $table->decimal('canopy_diameter', 8, 2)->nullable()->after('stem_diameter');
        });
    }

    public function down()
    {
        Schema::table('pending_geotags', function (Blueprint $table) {
            $table->dropColumn(['stem_diameter', 'canopy_diameter']);
        });
    }
}
