<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AllowNullMeasurementsInTreeDataV2 extends Migration  // ← Add V2 here
{
    public function up()
    {
        // Use raw SQL to avoid Doctrine DBAL issues with double type
        DB::statement('ALTER TABLE tree_data MODIFY dbh DOUBLE(8,2) NULL');
        DB::statement('ALTER TABLE tree_data MODIFY height DOUBLE(8,2) NULL');
    }

    public function down()
    {
        // Revert to NOT NULL (only if safe to do so)
        DB::statement('ALTER TABLE tree_data MODIFY dbh DOUBLE(8,2) NOT NULL');
        DB::statement('ALTER TABLE tree_data MODIFY height DOUBLE(8,2) NOT NULL');
    }
}