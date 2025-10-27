<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropQuantityFromHarvestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }

    public function down()
    {
        Schema::table('harvests', function (Blueprint $table) {
            $table->integer('quantity')->nullable();
        });
    }
}
