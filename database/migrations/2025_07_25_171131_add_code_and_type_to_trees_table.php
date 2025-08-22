<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCodeAndTypeToTreesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trees', function (Blueprint $table) {
    if (!Schema::hasColumn('trees', 'code')) {
        $table->string('code')->unique()->after('id');
    }

    if (!Schema::hasColumn('trees', 'type')) {
        $table->enum('type', ['sweet', 'semi_sweet', 'sour'])->after('code');
    }
});

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trees', function (Blueprint $table) {
            $table->dropColumn(['code', 'type']);
        });
    }
}
