<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\TreeCode;
use App\TreeData;

class FixMissingTreeData extends Command
{
    protected $signature = 'fix:missing-tree-data {--dry-run : Show what would be done without making changes}';
    protected $description = 'Create TreeData records for TreeCodes that are missing them';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        // Find TreeCodes without TreeData
        $codesWithoutData = TreeCode::whereDoesntHave('treeData')->get();

        if ($codesWithoutData->isEmpty()) {
            $this->info(' All tree codes already have TreeData records!');
            return 0;
        }

        $this->info("Found {$codesWithoutData->count()} tree codes without TreeData:");
        
        $this->table(
            ['ID', 'Code', 'Tree Type ID', 'Image ID'],
            $codesWithoutData->map(fn($tc) => [
                $tc->id,
                $tc->code,
                $tc->tree_type_id,
                $tc->tree_image_id
            ])
        );

        if ($isDryRun) {
            $this->warn("\n⚠️  This was a dry run. Run without --dry-run to apply changes.");
            return 0;
        }

        if (!$this->confirm('Do you want to create TreeData records for these codes?')) {
            $this->warn('Operation cancelled.');
            return 1;
        }

        $created = 0;
        $errors = 0;

        foreach ($codesWithoutData as $treeCode) {
            try {
                TreeData::create([
                    'tree_code_id'    => $treeCode->id,
                    'dbh'             => null,
                    'height'          => null,
                    'age'             => null,
                    'stem_diameter'   => null,
                    'canopy_diameter' => null,
                ]);

                $this->line("✓ Created TreeData for: {$treeCode->code}");
                $created++;

            } catch (\Exception $e) {
                $this->error("✗ Failed for {$treeCode->code}: {$e->getMessage()}");
                $errors++;
            }
        }

        echo "\n";
        $this->info(" Summary:");
        $this->info("   - Created: {$created}");
        
        if ($errors > 0) {
            $this->error("   - Errors: {$errors}");
        }

        echo "\n";
        $this->info("   Users can now edit these trees and add measurements.");

        return $errors > 0 ? 1 : 0;
    }
}