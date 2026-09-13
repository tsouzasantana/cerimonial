<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PDO;
use Tests\TestCase;

class BackupDatabaseTest extends TestCase
{
    private string $backupsDir;

    private ?string $tempDbPath = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backupsDir = storage_path('app/private/backups');
    }

    protected function tearDown(): void
    {
        foreach (glob("{$this->backupsDir}/backup_*") ?: [] as $file) {
            @unlink($file);
        }

        if ($this->tempDbPath && is_file($this->tempDbPath)) {
            @unlink($this->tempDbPath);
        }

        parent::tearDown();
    }

    public function test_backup_fails_gracefully_when_sqlite_database_file_is_missing(): void
    {
        // The test environment uses an in-memory sqlite database (no file on disk),
        // so this exercises the same "database file not found" path a misconfigured
        // production DB_DATABASE would hit.
        $exitCode = Artisan::call('db:backup');

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('não encontrado', Artisan::output());
    }

    public function test_backup_creates_a_restorable_gzip_copy_of_the_sqlite_database(): void
    {
        $stub = tempnam(sys_get_temp_dir(), 'cerimonial_backup_test_');
        unlink($stub);
        $this->tempDbPath = $stub.'.sqlite';

        $pdo = new PDO("sqlite:{$this->tempDbPath}");
        $pdo->exec('CREATE TABLE marker (value TEXT)');
        $pdo->exec("INSERT INTO marker (value) VALUES ('backup-restore-check')");
        unset($pdo);

        config(['database.connections.sqlite.database' => $this->tempDbPath]);
        DB::purge('sqlite');

        $exitCode = Artisan::call('db:backup');
        $this->assertSame(0, $exitCode);

        $files = glob("{$this->backupsDir}/backup_*.sqlite.gz");
        $this->assertCount(1, $files);

        $restoredPath = tempnam(sys_get_temp_dir(), 'cerimonial_backup_restored_');
        file_put_contents($restoredPath, gzdecode(file_get_contents($files[0])));

        $restored = new PDO("sqlite:{$restoredPath}");
        $value = $restored->query('SELECT value FROM marker')->fetchColumn();
        unset($restored);
        unlink($restoredPath);

        $this->assertSame('backup-restore-check', $value);
    }

    public function test_keep_days_option_prunes_only_backups_older_than_the_window(): void
    {
        if (! is_dir($this->backupsDir)) {
            mkdir($this->backupsDir, 0755, true);
        }

        $oldFile = "{$this->backupsDir}/backup_old_test.sqlite.gz";
        $recentFile = "{$this->backupsDir}/backup_recent_test.sqlite.gz";
        file_put_contents($oldFile, 'old');
        file_put_contents($recentFile, 'recent');
        touch($oldFile, now()->subDays(10)->getTimestamp());
        touch($recentFile, now()->subDays(2)->getTimestamp());

        $stub = tempnam(sys_get_temp_dir(), 'cerimonial_backup_test_');
        unlink($stub);
        $this->tempDbPath = $stub.'.sqlite';
        (new PDO("sqlite:{$this->tempDbPath}"))->exec('CREATE TABLE t (id INTEGER)');
        config(['database.connections.sqlite.database' => $this->tempDbPath]);
        DB::purge('sqlite');

        Artisan::call('db:backup', ['--keep-days' => 5]);

        $this->assertFileDoesNotExist($oldFile);
        $this->assertFileExists($recentFile);
    }
}
