<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileGeotagMetadataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_geotag_metadata', function (Blueprint $table) {
        $table->id();
        $table->foreignId('pending_geotag_id')->constrained()->onDelete('cascade');
        $table->foreignId('tree_id')->nullable()->constrained()->onDelete('set null');
        $table->text('image')->nullable(); // base64 or file path
        $table->string('device_id')->nullable();
        $table->string('source')->default('mobile-react');
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
        Schema::dropIfExists('mobile_geotag_metadata');
    }
}
