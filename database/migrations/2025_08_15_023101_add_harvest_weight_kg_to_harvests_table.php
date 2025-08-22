<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHarvestWeightKgToHarvestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::table('harvests', function (Blueprint $table) {
        $table->string('quality')->nullable();
        $table->text('notes')->nullable();
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    Schema::table('harvests', function (Blueprint $table) {
        $table->dropColumn(['quality', 'notes']);
    });
}
}
