<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SetUsersStatusDefaultAndCleanup extends Migration
{
    public function up()
    {
        DB::table('users')->whereNull('status')->update(['status' => 'active']);
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 50)->default('active')->change();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->change();
        });
    }
}