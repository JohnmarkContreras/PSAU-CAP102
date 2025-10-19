<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuditColumnsToPendingGeotagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pending_geotags', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('status');
            $table->unsignedBigInteger('processed_by')->nullable()->after('processed_at');
            $table->text('rejection_reason')->nullable()->after('processed_by');
            
            // Add foreign key constraint
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pending_geotags', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->dropColumn(['processed_at', 'processed_by', 'rejection_reason']);
        });
    }
}