<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByToHarvestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::table('harvests', function (Blueprint $table) {
                // Clean up temp column if it exists
                if (Schema::hasColumn('harvests', 'created_by_temp')) {
                    $table->dropColumn('created_by_temp');
                }

                // Add the correct column
                $table->unsignedBigInteger('created_by')->nullable();

                // Add the foreign key
                $table->foreign('created_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }

        public function down()
        {
            Schema::table('harvests', function (Blueprint $table) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');

                // Restore the old mistake if you want rollback symmetry
                $table->dateTime('created_by');
            });
        }
}
