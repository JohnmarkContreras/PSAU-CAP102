<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTreeDataTable extends Migration
{
    public function up()
    {
        Schema::create('tree_data', function (Blueprint $table) {
            $table->id();

            // Foreign key to tree_code table
            $table->foreignId('tree_code_id')->constrained('tree_code')->onDelete('cascade');

            // Required fields for carbon computation
            $table->float('dbh'); // Diameter at Breast Height (in inches)
            $table->float('height'); // Tree height (in feet)

            // Optional historical data
            $table->integer('age')->nullable(); // Tree age (in years)
            $table->float('stem_diameter')->nullable(); // Diameter at base or mid-stem
            $table->float('canopy_diameter')->nullable(); // Canopy spread (in meters or feet)

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tree_data');
    }
}