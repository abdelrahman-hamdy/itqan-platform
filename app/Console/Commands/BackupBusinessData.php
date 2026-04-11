<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Export subscriptions, sessions, attendance, reports, and teacher earnings
 * as a single mysqldump SQL file for easy backup and restore.
 */
class BackupBusinessData extends Command
{
    protected $signature = 'data:backup
                          {--no-compress : Save as .sql instead of .sql.gz}
                          {--path=storage/backups : Directory to save the backup file}';

    protected $description = 'Export business data (subscriptions, sessions, attendance, reports, earnings) as a SQL dump';

    private const TABLES = [
        // Subscriptions
        'quran_subscriptions',
        'academic_subscriptions',
        'course_subscriptions',
        // Sessions
        'quran_sessions',
        'academic_sessions',
        'interactive_course_sessions',
        // Attendance
        'meeting_attendances',
        'meeting_attendance_events',
        // Reports
        'student_session_reports',
        'academic_session_reports',
        'interactive_session_reports',
        // Earnings
        'teacher_earnings',
    ];

    public function handle(): int
    {
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        if (! in_array($dbConfig['driver'] ?? '', ['mysql', 'mariadb'])) {
            $this->error('This command requires a MySQL/MariaDB connection.');

            return self::FAILURE;
        }

        // Locate mysqldump binary
        $whichProcess = Process::fromShellCommandline('which mysqldump');
        $whichProcess->run();

        if (! $whichProcess->isSuccessful()) {
            $this->error('mysqldump binary not found. Please install mysql-client.');

            return self::FAILURE;
        }

        $mysqldump = trim($whichProcess->getOutput());

        // Prepare output directory
        $outputDir = base_path($this->option('path'));
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $compress = ! $this->option('no-compress');
        $timestamp = now()->format('Y-m-d_His');
        $filename = "itqan_business_data_{$timestamp}";
        $filePath = $outputDir.'/'.$filename.($compress ? '.sql.gz' : '.sql');

        $this->info('Starting business data backup...');
        $this->info('Tables: '.count(self::TABLES));

        // Build mysqldump command
        $command = [$mysqldump];

        $socket = $dbConfig['unix_socket'] ?? '';
        if ($socket) {
            $command[] = '--socket='.$socket;
        } else {
            $command[] = '--host='.$dbConfig['host'];
            $command[] = '--port='.($dbConfig['port'] ?? 3306);
        }

        $command[] = '--user='.$dbConfig['username'];

        if (! empty($dbConfig['password'])) {
            $command[] = '--password='.$dbConfig['password'];
        }

        $command[] = '--single-transaction';
        $command[] = '--set-gtid-purged=OFF';
        $command[] = '--column-statistics=0';
        $command[] = '--routines=false';
        $command[] = '--triggers';

        // Database + specific tables
        $command[] = $dbConfig['database'];
        $command = array_merge($command, self::TABLES);

        // Execute mysqldump
        $process = new Process($command);
        $process->setTimeout(600);

        $this->output->write('  Running mysqldump... ');
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Failed!');
            $this->error($process->getErrorOutput());

            return self::FAILURE;
        }

        $sqlContent = $process->getOutput();
        $this->info('Done.');

        // Write output file
        $this->output->write('  Writing file... ');

        if ($compress) {
            file_put_contents($filePath, gzencode($sqlContent, 9));
        } else {
            file_put_contents($filePath, $sqlContent);
        }

        $this->info('Done.');

        // Display results
        $size = filesize($filePath);

        $this->newLine();
        $this->info('Backup complete!');
        $this->table(
            ['Property', 'Value'],
            [
                ['File', $filePath],
                ['Size', $this->formatBytes($size)],
                ['Tables', count(self::TABLES)],
                ['Compressed', $compress ? 'Yes (gzip)' : 'No'],
                ['Timestamp', $timestamp],
            ]
        );

        $this->newLine();
        $this->line('<fg=gray>Restore with:</>');
        if ($compress) {
            $this->line("  gunzip < {$filePath} | mysql {$dbConfig['database']}");
        } else {
            $this->line("  mysql {$dbConfig['database']} < {$filePath}");
        }

        return self::SUCCESS;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
