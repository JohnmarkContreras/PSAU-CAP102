<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BackupDeviceController extends Controller
{
    /**
     * Show the backup-to-device page (replaces the old closure in routes).
     */
    public function index()
    {
        return view('pages.backup-device');
    }

    /**
     * Return a list of writable mounted devices.
     */
    public function getDevices()
    {
        $devices = $this->detectDevices();

        return response()->json([
            'success' => true,
            'devices' => $devices,
        ]);
    }

    /**
     * Run a backup to a selected device path.
     */
    public function backupToDevice(Request $request)
    {
        $validated = $request->validate([
            'device_path' => 'required|string',
            'backup_name' => 'nullable|string',
        ]);

        $devicePath = rtrim($validated['device_path'], '/');
        $backupName = $validated['backup_name'] ?? ('backup_' . date('Y-m-d_H-i-s'));

        // --- safety: allowlist + realpath ---
        $allowedRoots = ['/mnt', '/media'];
        $real = @realpath($devicePath);

        if (!$real) {
            return response()->json([
                'success' => false,
                'message' => 'Device path not found or not mounted.',
            ], 400);
        }

        $underAllowedRoot = false;
        foreach ($allowedRoots as $root) {
            $root = rtrim($root, '/') . '/';
            // PHP 7.4-safe starts_with
            if (strpos($real . '/', $root) === 0) {
                $underAllowedRoot = true;
                break;
            }
        }

        if (!$underAllowedRoot) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device path.',
            ], 400);
        }

        if (!is_dir($real)) {
            return response()->json([
                'success' => false,
                'message' => 'Path exists but is not a directory.',
            ], 400);
        }

        if (!is_writable($real)) {
            return response()->json([
                'success' => false,
                'message' => 'Device is not writable. Check mount permissions.',
            ], 403);
        }

        try {
            $backupPath = $real . '/' . $backupName;

            if (!is_dir($backupPath) && !@mkdir($backupPath, 0775, true)) {
                throw new \RuntimeException('Cannot create backup directory at: ' . $backupPath);
            }

            // Optional permission adjustment (uncomment if needed)
            // @chown($backupPath, 'www-data');
            // @chgrp($backupPath, 'www-data');

            // Run backup
            $this->runBackup($backupPath);

            return response()->json([
                'success'     => true,
                'message'     => 'Backup completed successfully!',
                'backup_path' => $backupPath,
            ]);
        } catch (\Throwable $e) {
            Log::error('Backup failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detect available/writable devices under /mnt, /media, and via lsblk.
     */
    private function detectDevices(): array
    {
        $devices = [];

        // /mnt manual mounts
        if (is_dir('/mnt')) {
            $entries = @scandir('/mnt') ?: [];
            foreach ($entries as $mount) {
                if ($mount === '.' || $mount === '..') continue;
                $path = '/mnt/' . $mount;
                if (is_dir($path)) {
                    $devices[] = [
                        'name'  => $mount,
                        'path'  => $path,
                        'type'  => 'manual_mount',
                        'space' => $this->getDeviceSpace($path),
                    ];
                }
            }
        }

        // /media auto-mounts (scan all user subfolders)
        if (is_dir('/media')) {
            $users = @scandir('/media') ?: [];
            foreach ($users as $userDir) {
                if ($userDir === '.' || $userDir === '..') continue;
                $mediaPath = '/media/' . $userDir;
                if (!is_dir($mediaPath)) continue;

                $devicesMedia = @scandir($mediaPath) ?: [];
                foreach ($devicesMedia as $dev) {
                    if ($dev === '.' || $dev === '..') continue;
                    $path = $mediaPath . '/' . $dev;
                    if (is_dir($path)) {
                        $devices[] = [
                            'name'  => $dev,
                            'path'  => $path,
                            'type'  => 'auto_mount',
                            'space' => $this->getDeviceSpace($path),
                        ];
                    }
                }
            }
        }

        // lsblk discovery
        $devices = array_merge($devices, $this->getLsblkDevices());

        // unique by path
        $unique = [];
        foreach ($devices as $d) {
            if (!isset($unique[$d['path']])) {
                $unique[$d['path']] = $d;
            }
        }

        return array_values($unique);
    }

    /**
     * Discover mounted/writable block devices via lsblk JSON.
     */
    private function getLsblkDevices(): array
    {
        $devices = [];

        try {
            $process = new Process(['lsblk', '-Jo', 'NAME,SIZE,MOUNTPOINT,TYPE']);
            $process->run();

            if ($process->isSuccessful()) {
                $output = json_decode($process->getOutput(), true);

                foreach (($output['blockdevices'] ?? []) as $dev) {
                    $type = $dev['type'] ?? '';
                    if ($type !== 'disk' && $type !== 'part') continue;

                    $mountpoint = $dev['mountpoint'] ?? null;
                    if ($mountpoint && is_dir($mountpoint) && is_writable($mountpoint)) {
                        $name = isset($dev['name']) ? $dev['name'] : 'device';
                        $size = isset($dev['size']) ? $dev['size'] : 'n/a';

                        $devices[] = [
                            'name'  => $name . ' (' . $size . ')',
                            'path'  => $mountpoint,
                            'type'  => 'block_device',
                            'space' => $this->getDeviceSpace($mountpoint),
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('lsblk detection failed: ' . $e->getMessage());
        }

        return $devices;
    }

    /**
     * Get disk space info for a path.
     */
    private function getDeviceSpace(string $path): ?array
    {
        try {
            $total = @disk_total_space($path);
            $free  = @disk_free_space($path);
            if (!$total || !$free) return null;

            $used = $total - $free;

            return [
                'total'   => $this->formatBytes($total),
                'used'    => $this->formatBytes($used),
                'free'    => $this->formatBytes($free),
                'percent' => $total > 0 ? round(($used / $total) * 100, 2) : null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Space check failed for ' . $path . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Format bytes into human-readable units.
     */
    private function formatBytes($bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max((float)$bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = (int)min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Run the console backup command (synchronously).
     * Consider moving to a queued Job for long runs.
     */
    private function runBackup(string $backupPath): void
    {
        $php = '/usr/bin/php'; // full path; FPM PATH may be minimal

        $process = new Process([$php, 'artisan', 'backup:hdd', '--path=' . $backupPath]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(300); // 5 minutes cap while under web request
        $process->run();

        if (!$process->isSuccessful()) {
            $err = trim($process->getErrorOutput() ?: $process->getOutput());
            throw new \RuntimeException($err ?: 'Unknown backup failure');
        }
    }
}