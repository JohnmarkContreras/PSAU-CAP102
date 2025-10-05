<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTreeTypeIdToTreeCodeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tree_code', function (Blueprint $table) {
            $table->unsignedBigInteger('tree_type_id')->nullable()->after('id');
            $table->foreign('tree_type_id')->references('id')->on('tree_types')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('tree_code', function (Blueprint $table) {
            $table->dropForeign(['tree_type_id']);
            $table->dropColumn('tree_type_id');
        });
    }
}
