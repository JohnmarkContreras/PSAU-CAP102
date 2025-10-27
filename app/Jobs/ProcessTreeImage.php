<?php
namespace App\Jobs;

use App\PendingGeotagTree;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ProcessTreeImage implements ShouldQueue
{
    use Dispatchable, Queueable;

    public $pending;

    public function __construct(PendingGeotagTree $pending){ $this->pending = $pending; }

    public function handle(): void
    {
        try {
            \Log::info('ProcessTreeImage start', ['pending_id' => $this->pending->id]);

            $localPath = ltrim($this->pending->image_path, '/');

            // Idempotency guards: skip if already processed or thumb exists
            if (
                str_starts_with($localPath, 'processed/') ||
                str_starts_with(basename($localPath), 'thumb_') ||
                !empty($this->pending->thumb_path) ||
                $this->pending->status === 'processed'
            ) {
                \Log::warning('Skipping reprocess - already processed or thumb exists', [
                    'pending_id' => $this->pending->id,
                    'image_path' => $localPath,
                    'thumb_path' => $this->pending->thumb_path,
                    'status' => $this->pending->status,
                ]);
                return;
            }

            $sourcePath = storage_path("app/public/{$localPath}");
            if (!file_exists($sourcePath)) {
                \Log::error('Source image missing', ['pending_id' => $this->pending->id, 'path' => $sourcePath]);
                $this->pending->status = 'error';
                $this->pending->rejection_reason = 'source image missing';
                $this->pending->save();
                return;
            }

            // Prepare thumb paths
            $thumbFilename = 'thumb_' . pathinfo($localPath, PATHINFO_BASENAME); // thumb_xyz.png
            $thumbPath = "processed/{$thumbFilename}";
            $thumbFullPath = storage_path("app/public/{$thumbPath}");

            if (!is_dir(dirname($thumbFullPath))) {
                mkdir(dirname($thumbFullPath), 0755, true);
            }

            // Create thumbnail using Intervention Image
            $img = \Intervention\Image\ImageManagerStatic::make($sourcePath);
            $img->orientate();
            $img->fit(800, 800, function ($constraint) {
                $constraint->upsize();
            });
            $img->save($thumbFullPath, 80);

            // Persist only thumb_path and metadata; never overwrite image_path
            $this->pending->thumb_path = $thumbFilename;
            $this->pending->processed_at = now();
            $this->pending->processed_by = auth()->id() ?? null;
            $this->pending->status = 'processed';
            $this->pending->rejection_reason = null;
            $this->pending->save();

            \Log::info('ProcessTreeImage success', ['pending_id' => $this->pending->id, 'thumb' => $thumbPath]);
        } catch (\Exception $e) {
            \Log::error('ProcessTreeImage failed', [
                'pending_id' => $this->pending->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->pending->status = 'error';
            $this->pending->rejection_reason = $e->getMessage();
            $this->pending->save();

            throw $e;
        }
    }
}