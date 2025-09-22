<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSyncFieldsToMobileGeotagMetadata extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mobile_geotag_metadata', function (Blueprint $table) {
        $table->timestamp('synced_at')->nullable()->after('source');
        $table->string('sync_status')->default('queued')->after('synced_at');
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mobile_geotag_metadata', function (Blueprint $table) {
        $table->dropColumn(['synced_at', 'sync_status']);
    });
    }
}
