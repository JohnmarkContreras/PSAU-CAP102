<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVoyagerPermissionsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('voyager_permissions')) {
            Schema::create('voyager_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('table_name')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('voyager_permissions');
    }
}
