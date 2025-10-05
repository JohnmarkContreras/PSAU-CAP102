<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCarbonColumnToTreeDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tree_data', function (Blueprint $table) {
            $table->float('estimated_biomass_kg')->nullable()->after('canopy_diameter');
            $table->float('carbon_stock_kg')->nullable()->after('estimated_biomass_kg');
            $table->float('annual_sequestration_kgco2')->nullable()->after('carbon_stock_kg');
        });
    }

    public function down()
    {
        Schema::table('tree_data', function (Blueprint $table) {
            $table->dropColumn(['estimated_biomass_kg','carbon_stock_kg','annual_sequestration_kgco2']);
        });
    }

}
