<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCodeAndTypeToPendingGeotagsTable extends Migration
{
    public function up()
    {
        Schema::table('pending_geotags', function (Blueprint $table) {
            // Add as nullable to avoid breaking existing rows; place them where you want
            $table->string('code', 50)->nullable()->after('id');
            $table->string('type', 20)->nullable()->after('code'); // sweet, sour, semi_sweet
        });
    }

    public function down()
    {
        Schema::table('pending_geotags', function (Blueprint $table) {
            $table->dropColumn(['code', 'type']);
        });
    }
}

