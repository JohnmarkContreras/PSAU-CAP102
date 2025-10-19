<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserArchivesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        Schema::create('user_archives', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable()->index(); // no FK
            $table->json('payload');
            $table->string('email')->nullable()->index(); // denormalized for lookups
            $table->string('username')->nullable()->index();
            $table->string('archived_by')->nullable()->index();
            $table->string('archive_reason')->nullable();
            $table->string('schema_version')->default('v1');
            $table->timestamp('archived_at')->useCurrent()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_archives');
    }

}
