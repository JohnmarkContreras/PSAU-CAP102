<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateHarvestPredictionsFkToTreeCode extends Migration
{
    public function up()
    {
        // Ensure referenced column is indexable
        try { DB::statement('ALTER TABLE tree_code MODIFY code VARCHAR(191)'); } catch (\Throwable $e) {}
        try { DB::statement('CREATE UNIQUE INDEX tree_code_code_unique ON tree_code (code)'); } catch (\Throwable $e) {}

        // Drop existing FK on harvest_predictions.code (dynamic)
        try {
            $fk = DB::selectOne("SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'harvest_predictions' AND COLUMN_NAME = 'code' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1");
            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE harvest_predictions DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {}

        // Normalize existing prediction codes to canonical tree_code.code casing
        try { DB::statement("UPDATE harvest_predictions p JOIN tree_code t ON LOWER(p.code) = LOWER(t.code) SET p.code = t.code"); } catch (\Throwable $e) {}
        // Remove orphans
        try { DB::statement("DELETE p FROM harvest_predictions p LEFT JOIN tree_code t ON p.code = t.code WHERE t.code IS NULL"); } catch (\Throwable $e) {}

        Schema::table('harvest_predictions', function (Blueprint $table) {
            if (! Schema::hasColumn('harvest_predictions', 'code')) {
                $table->string('code');
            }
            try { DB::statement('CREATE INDEX harvest_predictions_code_index ON harvest_predictions (code)'); } catch (\Throwable $e) {}
            $table->foreign('code')->references('code')->on('tree_code')->onDelete('cascade');
        });
    }

    public function down()
    {
        // Drop FK to tree_code and restore FK to trees
        try { DB::statement("ALTER TABLE harvest_predictions DROP FOREIGN KEY `harvest_predictions_code_foreign`"); } catch (\Throwable $e) {}
        Schema::table('harvest_predictions', function (Blueprint $table) {
            $table->foreign('code')->references('code')->on('trees')->onDelete('cascade');
        });
    }
}
