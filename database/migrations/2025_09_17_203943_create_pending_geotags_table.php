<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingGeotagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_geotags', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id'); // who submitted
        $table->decimal('latitude', 10, 7);
        $table->decimal('longitude', 10, 7);
        $table->integer('age')->nullable();
        $table->decimal('height', 8, 2)->nullable();
        $table->decimal('diameter', 8, 2)->nullable();
        $table->string('status')->default('pending'); // pending, approved, rejected
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
    });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pending_geotags');
    }
}
