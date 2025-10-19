<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexesToTreeImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('tree_images', function (Blueprint $table) {
            $table->index(['latitude', 'longitude']);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    
    public function down(): void
    {
        Schema::table('tree_images', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']); 
            // or use the auto-generated name if needed: $table->dropIndex('tree_images_latitude_longitude_index');
        });
    }

}
