<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\TreeImage;
use App\TreeCode;
use App\TreeData;
use Carbon\Carbon;

class importTreeImages extends Command
{
    protected $signature = 'import:tree-images';
    protected $description = 'Import tree images and extract GPS metadata';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $files = Storage::files('public/tree_images');

        // Define type-to-ID mapping based on your tree_types table
        $typeMap = [
            'SOUR' => 1,
            'SWEET' => 2,
            'SEMI_SWEET' => 3,
        ];

        foreach ($files as $file) {
            $path = storage_path('app/' . $file);
            $basename = basename($file);

            //  Skip if image already imported
            if (TreeImage::where('filename', $basename)->exists()) {
                $this->warn("Skipping duplicate image: {$basename}");
                continue;
            }
            $filename = pathinfo($basename, PATHINFO_FILENAME); // e.g., "Semi_sweet (100)"

            //  Match only valid patterns (Sour, Sweet, Semi, or Semi_sweet)
            if (!preg_match('/^(Sour|Sweet|Semi|Semi_sweet)\s*\(\s*\d+\s*\)$/i', $filename)) {
                $this->warn("Skipping invalid filename format: {$basename}");
                continue;
            }

            $exif = @exif_read_data($path);

            if (!$exif || !isset($exif['GPSLatitude'], $exif['GPSLongitude'])) {
                $this->warn("Skipping (no GPS): {$basename}");
                continue;
            }

            $latitude = $this->getGps($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
            $longitude = $this->getGps($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
            $accuracy = $exif['GPSDOP'] ?? null;
            $takenAt  = $exif['DateTimeOriginal'] ?? null;

            // Create TreeImage record
            $treeImage = TreeImage::create([
                'filename'    => $basename,
                'latitude'    => $latitude,
                'longitude'   => $longitude,
                'accuracy'    => $accuracy,
                'taken_at'    => $takenAt ? Carbon::createFromFormat('Y:m:d H:i:s', $takenAt) : null,
                'source_type' => 'imported',
            ]);

            //Extract type name from filename
            preg_match('/^(Sour|Sweet|Semi|Semi_sweet)/i', $filename, $typeMatch);
            $treeType = strtoupper(str_replace(' ', '_', $typeMatch[1] ?? 'UNKNOWN'));

            // Normalize "SEMI" to "SEMI_SWEET"
            if ($treeType === 'SEMI') {
                $treeType = 'SEMI_SWEET';
            }

            // Map to tree_type_id
            $treeTypeId = $typeMap[$treeType] ?? null;
            if (!$treeTypeId) {
                $this->warn("Skipping: Unknown tree type in {$basename}");
                continue;
            }

            // Extract numeric part (e.g., 100)
            preg_match('/\(\s*(\d+)\s*\)/', $filename, $numMatch);
            $treeNumber = $numMatch[1] ?? null;

            if (!$treeNumber) {
                $this->warn("Skipping: No number found in {$basename}");
                continue;
            }

            // Create full code (e.g., SEMI_SWEET100)
            $formattedCode = $treeType . $treeNumber;

            // Skip if code already exists
            if (TreeCode::where('code', $formattedCode)->exists()) {
                $this->warn("Skipping duplicate code: {$formattedCode}");
                continue;
            }

            // Save TreeCode
            $treeCode = TreeCode::create([
                'tree_image_id' => $treeImage->id,
                'tree_type_id'  => $treeTypeId,
                'code'          => $formattedCode,
                'created_by'    => auth()->id() ?? 1,
            ]);

            // ✅ NEW: Create TreeData record for this tree
            TreeData::create([
                'tree_code_id'    => $treeCode->id,
                'dbh'             => null,
                'height'          => null,
                'age'             => null,
                'stem_diameter'   => null,
                'canopy_diameter' => null,
            ]);

            $this->info("Imported: {$basename} → Type ID: {$treeTypeId}, Code: {$formattedCode}");
        }

        $this->info("\n✅ Import complete!");
    }

    protected function getGps($coord, $hemisphere)
    {
        $degrees = $this->gps2Num($coord[0]);
        $minutes = $this->gps2Num($coord[1]);
        $seconds = $this->gps2Num($coord[2]);

        $flip = ($hemisphere == 'W' || $hemisphere == 'S') ? -1 : 1;

        return $flip * ($degrees + ($minutes / 60) + ($seconds / 3600));
    }

    protected function gps2Num($coordPart)
    {
        $parts = explode('/', $coordPart);
        if (count($parts) <= 0) return 0;
        if (count($parts) == 1) return $parts[0];
        return floatval($parts[0]) / floatval($parts[1]);
    }
}