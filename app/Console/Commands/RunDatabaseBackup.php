<?php

namespace App\Console\Commands;

use App\Models\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RunDatabaseBackup extends Command
{
    protected $signature   = 'backup:run';
    protected $description = 'Create a MySQL database backup';

    public function handle(): int
    {
        $db       = config('database.connections.mysql.database');
        $user     = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host     = config('database.connections.mysql.host');

        $filename  = 'backup_' . now()->format('Y_m_d_His') . '.sql';
        $directory = storage_path('app/backups');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filePath = $directory . DIRECTORY_SEPARATOR . $filename;
        $pwOption = $password ? "-p{$password}" : '';
        $command  = "mysqldump -h {$host} -u {$user} {$pwOption} {$db} > \"{$filePath}\" 2>&1";

        exec($command, $output, $returnCode);

        $status = ($returnCode === 0) ? 'success' : 'failed';
        $sizeKb = ($returnCode === 0 && file_exists($filePath))
            ? (int) ceil(filesize($filePath) / 1024)
            : null;

        Backup::create([
            'filename'   => $filename,
            'size_kb'    => $sizeKb,
            'created_by' => auth()->id(),
            'status'     => $status,
        ]);

        if ($returnCode === 0) {
            $this->info("Backup created: {$filename} ({$sizeKb} KB)");
            return self::SUCCESS;
        }

        $this->error('Backup failed: ' . implode("\n", $output));
        return self::FAILURE;
    }
}
