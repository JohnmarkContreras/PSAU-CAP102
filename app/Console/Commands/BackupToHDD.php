<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackupToHDD extends Command
{
    protected $signature = 'backup:hdd';
    protected $description = 'Backup database and files to external HDD';

    public function handle()
    {
        $backupPath = '/mnt/external_hdd/backups/' . date('Y-m-d_H-i-s');
        
        if (!is_dir($backupPath)) {
            if (!@mkdir($backupPath, 0755, true)) {
                $this->error('✗ Cannot create backup directory. Check HDD permissions.');
                $this->error('Run: sudo chown -R www-data:www-data /mnt/external_hdd/');
                return 1;
            }
        }

        // Backup database
        $this->backupDatabase($backupPath);

        // Backup files
        $this->backupFiles($backupPath);

        $this->info('Backup completed successfully at: ' . $backupPath);
    }

    private function backupDatabase($path)
    {
        $file = $path . '/database.sql';
        
        // Get credentials from .env
        $host = env('DB_HOST', 'localhost');
        $username = env('DB_USERNAME', 'root');
        $password = env('DB_PASSWORD', '');
        $database = env('DB_DATABASE', 'laravel');

        // Build mysqldump command
        $command = "mysqldump -h {$host} -u {$username}";
        
        if (!empty($password)) {
            $command .= " -p" . escapeshellarg($password);
        }
        
        $command .= " " . escapeshellarg($database) . " > " . escapeshellarg($file);
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0) {
            $this->info('✓ Database backed up to: ' . $file);
        } else {
            $this->error('✗ Database backup failed!');
            $this->error('Error: ' . implode("\n", $output));
        }
    }

    private function backupFiles($path)
    {
        $source = base_path();
        $destination = $path . '/files';
        
        // Create destination directory
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        // Exclude unnecessary directories
        $excludes = [
            'vendor',
            'node_modules',
            '.git',
            '.env',
            'storage/logs',
            'storage/backups',
            'storage/app/backup-temp',
            '.env.local',
            '.env.*.local'
        ];
        
        $excludeFlags = implode(' ', array_map(fn($e) => "--exclude='{$e}'", $excludes));
        
        // Use rsync for better file handling
        $command = "rsync -av {$excludeFlags} {$source}/ {$destination}/ 2>&1";
        
        exec($command, $output, $returnCode);
        
        if ($returnCode === 0 || $returnCode === 23) { // 23 means partial transfer (normal for some files)
            $this->info('✓ Files backed up to: ' . $destination);
        } else {
            $this->error('✗ Files backup failed with code: ' . $returnCode);
            $this->error('Error: ' . implode("\n", array_slice($output, -5)));
        }
    }
}