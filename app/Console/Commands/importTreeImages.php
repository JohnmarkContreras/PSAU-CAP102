<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\TreeImage;
use App\TreeCode;
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

    foreach ($files as $file) {
        $path = storage_path('app/' . $file);
        $exif = @exif_read_data($path);

        if (!$exif || !isset($exif['GPSLatitude'], $exif['GPSLongitude'])) {
            $this->warn("Skipping: " . basename($file));
            continue;
        }

        $latitude = $this->getGps($exif['GPSLatitude'], $exif['GPSLatitudeRef']);
        $longitude = $this->getGps($exif['GPSLongitude'], $exif['GPSLongitudeRef']);
        $accuracy = $exif['GPSDOP'] ?? null;
        $takenAt  = $exif['DateTimeOriginal'] ?? null;

        // ✅ Create TreeImage first
        $treeImage = TreeImage::create([
            'filename'    => basename($file),
            'latitude'    => $latitude,
            'longitude'   => $longitude,
            'accuracy'    => $accuracy,
            'taken_at'    => $takenAt ? Carbon::createFromFormat('Y:m:d H:i:s', $takenAt) : null,
            'source_type' => 'imported',
        ]);

        // ✅ Extract note/code from ImageDescription
        $rawDescription = $exif['ImageDescription'] ?? null;
        $note = null;

        if ($rawDescription) {
            preg_match('/Note:\s*(.+)/', $rawDescription, $matches);
            $note = $matches[1] ?? null;
        }

        if ($note === 'PS') {
        $note = "N/A"; //null; if you want to skip saving it
    }
        // ✅ Save TreeCode if note exists
        if ($note) {
            TreeCode::create([
                'tree_image_id' => $treeImage->id,
                'code'          => $note,
                'created_by'    => auth()->id(),
            ]);
        }

        $this->info("Imported: " . basename($file));
    }
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
