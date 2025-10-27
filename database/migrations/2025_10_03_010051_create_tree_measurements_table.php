<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreeMeasurementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
public function up()
{
    Schema::create('tree_measurements', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('tree_code_id'); // FK to tree_code
        $table->integer('age')->nullable();
        $table->decimal('height', 5, 2)->nullable(); // meters
        $table->decimal('canopy_diameter', 5, 2)->nullable(); // meters
        $table->decimal('stem_diameter', 5, 2)->nullable(); // cm
        $table->timestamps();

        $table->foreign('tree_code_id')->references('id')->on('tree_code')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tree_measurements');
    }
}
