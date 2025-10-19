<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVoyagerColumnsToRolesTable extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
            if (!Schema::hasColumn('roles', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('description');
            }
            if (!Schema::hasColumn('roles', 'guard_name')) {
                $table->string('guard_name')->default(config('auth.defaults.guard'))->after('name');
            }
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'guard_name')) {
                $table->dropColumn('guard_name');
            }
            if (Schema::hasColumn('roles', 'created_by')) {
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('roles', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('roles', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });
    }
}