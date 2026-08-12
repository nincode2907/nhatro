<?php

namespace App\Console\Commands;

use App\Services\Database\SqliteBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database
        {--retention= : Ghi đè số bản backup cần giữ}';

    protected $description = 'Tạo và kiểm tra một bản backup nhất quán của database SQLite';

    public function handle(SqliteBackupService $backupService): int
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('Lệnh này chỉ hỗ trợ database SQLite.');

            return self::FAILURE;
        }

        $database = (string) config('database.connections.sqlite.database');

        if ($database === ':memory:' || str_starts_with($database, 'file:')) {
            $this->error('Không thể backup SQLite in-memory.');

            return self::FAILURE;
        }

        $retention = $this->retention();

        if ($retention === null) {
            return self::FAILURE;
        }

        try {
            $result = $backupService->create(
                DB::connection('sqlite')->getPdo(),
                (string) config('backup.path'),
                $retention,
            );

            $this->info('Backup SQLite thành công.');
            $this->line('File: '.$result['path']);
            $this->line('Retention: giữ '.$retention.' bản gần nhất.');

            if ($result['deleted'] !== []) {
                $this->line('Đã xóa '.count($result['deleted']).' bản cũ vượt retention.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Backup thất bại: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function retention(): ?int
    {
        $value = $this->option('retention');
        $value = $value === null ? config('backup.retention') : $value;

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 1) {
            $this->error('Retention phải là số nguyên từ 1 trở lên.');

            return null;
        }

        return (int) $value;
    }
}
