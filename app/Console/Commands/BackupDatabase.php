<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Dumps the application database to a gzip-compressed file under
 * storage/app/private/backups and deletes backups older than the
 * configured retention window. Supports the sqlite connection used in
 * development and the mysql/mariadb connections used in production.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep-days=30 : Delete backups older than this many days}';

    protected $description = 'Gera um backup compactado do banco de dados e remove backups antigos';

    public function handle(): int
    {
        $directory = storage_path('app/private/backups');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $connection = config('database.default');
        $timestamp = now()->format('Y-m-d_His');

        try {
            $path = match ($connection) {
                'sqlite' => $this->backupSqlite($directory, $timestamp),
                'mysql', 'mariadb' => $this->backupMysql($directory, $timestamp, $connection),
                default => throw new \RuntimeException("Backup não suportado para a conexão \"{$connection}\"."),
            };
        } catch (\Throwable $e) {
            $this->error('Falha ao gerar o backup: '.$e->getMessage());
            Log::error('Falha ao gerar backup do banco de dados', ['exception' => $e]);

            return self::FAILURE;
        }

        $this->info("Backup criado em: {$path}");
        $this->pruneOldBackups($directory, (int) $this->option('keep-days'));

        return self::SUCCESS;
    }

    private function backupSqlite(string $directory, string $timestamp): string
    {
        $databasePath = config('database.connections.sqlite.database');

        if (! is_file($databasePath)) {
            throw new \RuntimeException("Arquivo do banco SQLite não encontrado em \"{$databasePath}\".");
        }

        $destination = "{$directory}/backup_{$timestamp}.sqlite.gz";
        $this->gzipFile($databasePath, $destination);

        return $destination;
    }

    private function backupMysql(string $directory, string $timestamp, string $connection): string
    {
        $config = config("database.connections.{$connection}");
        $destination = "{$directory}/backup_{$timestamp}.sql.gz";
        $sqlPath = "{$directory}/backup_{$timestamp}.sql";

        $command = [
            'mysqldump',
            '--host='.$config['host'],
            '--port='.$config['port'],
            '--user='.$config['username'],
            '--single-transaction',
            '--quick',
            '--result-file='.$sqlPath,
            $config['database'],
        ];

        $process = new Process($command, env: ['MYSQL_PWD' => $config['password']]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new \RuntimeException('mysqldump falhou: '.$process->getErrorOutput());
        }

        $this->gzipFile($sqlPath, $destination);
        unlink($sqlPath);

        return $destination;
    }

    private function gzipFile(string $source, string $destination): void
    {
        $sourceHandle = fopen($source, 'rb');
        $destinationHandle = gzopen($destination, 'wb9');

        while (! feof($sourceHandle)) {
            gzwrite($destinationHandle, fread($sourceHandle, 1024 * 512));
        }

        fclose($sourceHandle);
        gzclose($destinationHandle);
    }

    private function pruneOldBackups(string $directory, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays)->getTimestamp();
        $removed = 0;

        foreach (glob("{$directory}/backup_*") as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->info("{$removed} backup(s) antigo(s) removido(s).");
        }
    }
}
