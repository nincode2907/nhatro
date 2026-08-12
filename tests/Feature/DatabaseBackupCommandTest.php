<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use PDO;
use Tests\TestCase;

class DatabaseBackupCommandTest extends TestCase
{
    private string $temporaryDirectory;

    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryDirectory = sys_get_temp_dir().'/nhatro-backup-test-'.bin2hex(random_bytes(8));
        $this->databasePath = $this->temporaryDirectory.'/database.sqlite';
        File::makeDirectory($this->temporaryDirectory, 0700, true);
        File::put($this->databasePath, '');

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', $this->databasePath);
        config()->set('backup.path', $this->temporaryDirectory.'/backups');
        config()->set('backup.retention', 2);
        DB::purge('sqlite');
        DB::connection('sqlite')->statement('CREATE TABLE samples (id INTEGER PRIMARY KEY, value TEXT NOT NULL)');
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');
        File::deleteDirectory($this->temporaryDirectory);

        parent::tearDown();
    }

    public function test_command_creates_a_verified_backup_with_wal_data(): void
    {
        DB::connection('sqlite')->statement('PRAGMA wal_autocheckpoint = 0');
        DB::table('samples')->insert(['value' => 'chỉ số tháng 08']);

        $this->artisan('app:backup-database')
            ->expectsOutputToContain('Backup SQLite thành công.')
            ->assertSuccessful();

        $backups = File::glob($this->temporaryDirectory.'/backups/*.sqlite');
        $this->assertCount(1, $backups);

        $backup = new PDO('sqlite:'.$backups[0]);
        $this->assertSame('chỉ số tháng 08', $backup->query('SELECT value FROM samples')->fetchColumn());
        $this->assertSame('ok', $backup->query('PRAGMA integrity_check')->fetchColumn());
    }

    public function test_command_keeps_only_the_configured_number_of_its_own_backups(): void
    {
        File::makeDirectory($this->temporaryDirectory.'/backups', 0700, true);
        File::put($this->temporaryDirectory.'/backups/ghi-chu.txt', 'không được xóa');

        foreach (range(1, 3) as $index) {
            DB::table('samples')->insert(['value' => 'lần '.$index]);
            $this->artisan('app:backup-database')->assertSuccessful();
        }

        $this->assertCount(2, File::glob($this->temporaryDirectory.'/backups/*.sqlite'));
        $this->assertFileExists($this->temporaryDirectory.'/backups/ghi-chu.txt');
    }

    public function test_command_rejects_an_invalid_retention_without_creating_a_backup(): void
    {
        $this->artisan('app:backup-database', ['--retention' => 0])
            ->expectsOutputToContain('Retention phải là số nguyên từ 1 trở lên.')
            ->assertFailed();

        $this->assertSame([], File::glob($this->temporaryDirectory.'/backups/*.sqlite'));
    }
}
