<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusColumnToHarvestPrediction extends Migration
{
    public function up()
    {
        Schema::table('harvest_predictions', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('predicted_quantity');
            $table->decimal('actual_quantity', 10, 2)->nullable()->after('status');
            $table->unsignedBigInteger('harvest_id')->nullable()->after('actual_quantity');
            $table->foreign('harvest_id')->references('id')->on('harvests')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('harvest_predictions', function (Blueprint $table) {
            $table->dropForeign(['harvest_id']);
            $table->dropColumn(['harvest_id', 'actual_quantity', 'status']);
        });
    }
}
