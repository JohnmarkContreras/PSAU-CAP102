<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCarbonRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carbon_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tree_id')->constrained()->onDelete('cascade');
            $table->float('estimated_biomass_kg')->nullable();
            $table->float('carbon_stock_kg')->nullable();
            $table->float('annual_sequestration_kg')->nullable();
            $table->timestamp('recorded_at')->nullable(); // for historical tracking
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('carbon_records');
    }
}
