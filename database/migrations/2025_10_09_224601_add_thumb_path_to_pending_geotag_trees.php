<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddThumbPathToPendingGeotagTrees extends Migration
{
public function up(): void
    {
        Schema::table('pending_geotag_trees', function (Blueprint $table) {
            $table->string('thumb_path')->nullable()->after('image_path')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pending_geotag_trees', function (Blueprint $table) {
            $table->dropIndex(['thumb_path']);
            $table->dropColumn('thumb_path');
        });
    }

}
