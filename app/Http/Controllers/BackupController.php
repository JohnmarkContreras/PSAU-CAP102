<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Carbon\Carbon;

class BackupController extends Controller
{
    public function getBackupStatus()
{
    $backupPath = '/mnt/external_hdd/backups/';
    
    if (!is_dir($backupPath)) {
        return view('pages.backup', ['lastBackup' => null]);
    }

    // Get the most recent backup folder
    $folders = array_filter(glob($backupPath . '*'), 'is_dir');
    rsort($folders);
    
    $lastBackup = null;
    if (!empty($folders)) {
        $latestFolder = $folders[0];
        $timestamp = basename($latestFolder);
        
        try {
            // Parse the timestamp (format: 2025-10-18_07-21-06)
            $date = Carbon::createFromFormat('Y-m-d_H-i-s', $timestamp);
            $size = $this->formatBytes($this->getFolderSize($latestFolder));
            
            $lastBackup = [
                'date' => $date,
                'size' => $size,
                'path' => $latestFolder
            ];
        } catch (\Exception $e) {
            \Log::error('Backup parsing error: ' . $e->getMessage() . ' | Timestamp: ' . $timestamp);
        }
    }

    return view('pages.backup', ['lastBackup' => $lastBackup]);
}

    public function manualBackup(Request $request)
    {
        try {
            // Run the backup command
            $process = new Process(['php', 'artisan', 'backup:hdd']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(600); // 10 minutes timeout
            $process->run();

            if ($process->isSuccessful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup completed successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup failed: ' . $process->getErrorOutput()
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getFolderSize($path)
    {
        $size = 0;
        $files = glob($path . '/*', GLOB_BRACE);
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            } elseif (is_dir($file)) {
                $size += $this->getFolderSize($file);
            }
        }
        
        return $size;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}