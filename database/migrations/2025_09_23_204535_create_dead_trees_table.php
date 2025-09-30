<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeadTreesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dead_trees', function (Blueprint $table) {
            $table->id();
            $table->string('tree_code');
            $table->foreign('tree_code')->references('code')->on('trees')->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('reported_at')->useCurrent();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dead_trees');
    }
}
