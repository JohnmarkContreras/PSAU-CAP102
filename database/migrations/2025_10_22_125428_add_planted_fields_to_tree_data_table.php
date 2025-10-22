<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlantedFieldsToTreeDataTable extends Migration
{
    public function up()
    {
        Schema::table('tree_data', function (Blueprint $table) {
            // Exact planting date, nullable
            $table->date('planted_at')->nullable()->after('age');

            // Flag to indicate if only year is provided
            $table->boolean('planted_year_only')->default(false)->after('planted_at');
        });
    }

    public function down()
    {
        Schema::table('tree_data', function (Blueprint $table) {
            $table->dropColumn(['planted_at', 'planted_year_only']);
        });
    }
}
