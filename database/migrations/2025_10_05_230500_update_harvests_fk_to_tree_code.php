<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateHarvestsFkToTreeCode extends Migration
{
    public function up()
    {
        Schema::table('harvests', function (Blueprint $table) {
            // Drop existing foreign key to trees.code if present
            try {
                $table->dropForeign('harvests_code_foreign');
            } catch (\Throwable $e) {
                // ignore if FK name differs or doesn't exist
            }
        });

        Schema::table('harvests', function (Blueprint $table) {
            // Ensure code column exists
            if (! Schema::hasColumn('harvests', 'code')) {
                $table->string('code');
            }
            // Add new FK to tree_code.code
            $table->foreign('code')->references('code')->on('tree_code')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('harvests', function (Blueprint $table) {
            try {
                $table->dropForeign(['code']);
            } catch (\Throwable $e) {}
        });

        Schema::table('harvests', function (Blueprint $table) {
            // revert to original FK to trees.code
            $table->foreign('code')->references('code')->on('trees')->onDelete('cascade');
        });
    }
}
