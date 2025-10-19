<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDataColumnsToPrending extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pending_geotag_trees', function (Blueprint $table) {
            $table->decimal('dbh', 8, 2)->nullable()->after('code');
            $table->decimal('height', 8, 2)->nullable()->after('dbh');
            $table->integer('age')->nullable()->after('height');
            $table->decimal('canopy_diameter', 8, 2)->nullable()->after('age');
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
            $table->dropColumn(['dbh', 'height', 'age', 'canopy_diameter']);
        });
    }
}