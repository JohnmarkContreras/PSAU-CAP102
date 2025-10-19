<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHarvestsTable extends Migration
{
    public function up()
    {
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->string('code'); // e.g., TM0001
            $table->date('harvest_date');
            $table->decimal('quantity', 8, 2); // in kg
            $table->timestamps();

            $table->foreign('code')
                ->references('code')
                ->on('trees')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('harvests');
    }
}
