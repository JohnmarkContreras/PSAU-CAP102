<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateHarvestsFkToTreeCode extends Migration
{
    public function up()
    {
        // Ensure tree_code.code is indexable (MySQL requires indexed referenced column)
        try {
            DB::statement('ALTER TABLE tree_code MODIFY code VARCHAR(191)');
        } catch (\Throwable $e) {
            // ignore if already varchar
        }
        try {
            DB::statement('CREATE UNIQUE INDEX tree_code_code_unique ON tree_code (code)');
        } catch (\Throwable $e) {
            // ignore if index already exists
        }

        // Drop existing foreign key to trees.code if present (discover constraint name dynamically)
        try {
            $fk = DB::selectOne("SELECT CONSTRAINT_NAME AS name FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'harvests' AND COLUMN_NAME = 'code' AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1");
            if ($fk && isset($fk->name)) {
                DB::statement("ALTER TABLE harvests DROP FOREIGN KEY `{$fk->name}`");
            }
        } catch (\Throwable $e) {
            // ignore if cannot drop
        }

        Schema::table('harvests', function (Blueprint $table) {
            // Ensure code column exists
            if (! Schema::hasColumn('harvests', 'code')) {
                $table->string('code');
            }
            // Ensure index on referencing column (MySQL requirement)
            try { DB::statement('CREATE INDEX harvests_code_index ON harvests (code)'); } catch (\Throwable $e) {}
            // Add new FK to tree_code.code
            $table->foreign('code')->references('code')->on('tree_code')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('harvests', function (Blueprint $table) {
            try {
                $table->dropForeign(['code']);
            } catch (\Throwable $e) {}
        });

        Schema::table('harvests', function (Blueprint $table) {
            // revert to original FK to trees.code
            $table->foreign('code')->references('code')->on('trees')->onDelete('cascade');
        });
    }
}
