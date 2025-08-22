<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHarvestPredictionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('harvest_predictions', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->date('predicted_date');
        $table->decimal('predicted_quantity', 8, 2)->nullable();
        $table->timestamps();

        $table->unique(['code', 'predicted_date']);
        $table->foreign('code')->references('code')->on('trees')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('harvest_predictions');
    }
}
