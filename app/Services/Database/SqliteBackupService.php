<?php

namespace App\Services\Database;

use PDO;
use RuntimeException;
use Throwable;

class SqliteBackupService
{
    /**
     * @return array{path: string, deleted: list<string>}
     */
    public function create(PDO $source, string $backupDirectory, int $retention): array
    {
        if ($retention < 1) {
            throw new RuntimeException('Số bản backup cần giữ phải từ 1 trở lên.');
        }

        $backupDirectory = $this->prepareDirectory($backupDirectory);
        $lock = fopen($backupDirectory.DIRECTORY_SEPARATOR.'.backup.lock', 'c');

        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new RuntimeException('Một tiến trình backup khác đang chạy.');
        }

        $temporaryPath = $backupDirectory.DIRECTORY_SEPARATOR.'.backup-'.bin2hex(random_bytes(8)).'.tmp';
        $backupPath = $backupDirectory.DIRECTORY_SEPARATOR.now()->format('Y-m-d_His_u').'.sqlite';

        try {
            $quotedPath = $source->quote($temporaryPath);

            if ($quotedPath === false) {
                throw new RuntimeException('Không thể chuẩn bị đường dẫn backup SQLite.');
            }

            $source->exec('VACUUM INTO '.$quotedPath);
            $this->assertHealthy($temporaryPath);

            if (! rename($temporaryPath, $backupPath)) {
                throw new RuntimeException('Không thể hoàn tất file backup.');
            }

            @chmod($backupPath, 0600);
            $deleted = $this->enforceRetention($backupDirectory, $retention);

            return ['path' => $backupPath, 'deleted' => $deleted];
        } catch (Throwable $exception) {
            if (is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }

            throw $exception;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function prepareDirectory(string $backupDirectory): string
    {
        if (! str_starts_with($backupDirectory, DIRECTORY_SEPARATOR)) {
            $backupDirectory = base_path($backupDirectory);
        }

        if (! is_dir($backupDirectory) && ! mkdir($backupDirectory, 0700, true) && ! is_dir($backupDirectory)) {
            throw new RuntimeException("Không thể tạo thư mục backup: {$backupDirectory}");
        }

        $resolved = realpath($backupDirectory);

        if ($resolved === false || ! is_writable($resolved)) {
            throw new RuntimeException("Thư mục backup không thể ghi: {$backupDirectory}");
        }

        $publicPath = realpath(public_path());

        if ($publicPath !== false && ($resolved === $publicPath || str_starts_with($resolved, $publicPath.DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('Thư mục backup không được nằm trong public/.');
        }

        return $resolved;
    }

    private function assertHealthy(string $backupPath): void
    {
        if (! is_file($backupPath) || filesize($backupPath) === 0) {
            throw new RuntimeException('SQLite tạo ra file backup rỗng.');
        }

        $backup = new PDO('sqlite:'.$backupPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $integrity = $backup->query('PRAGMA integrity_check')->fetchColumn();

        if ($integrity !== 'ok') {
            throw new RuntimeException('Kiểm tra toàn vẹn backup thất bại: '.(string) $integrity);
        }

        $foreignKeyViolation = $backup->query('PRAGMA foreign_key_check')->fetch();

        if ($foreignKeyViolation !== false) {
            throw new RuntimeException('Backup có vi phạm khóa ngoại.');
        }
    }

    /** @return list<string> */
    private function enforceRetention(string $backupDirectory, int $retention): array
    {
        $files = glob($backupDirectory.DIRECTORY_SEPARATOR.'????-??-??_??????_??????.sqlite') ?: [];
        rsort($files, SORT_STRING);
        $deleted = [];

        foreach (array_slice($files, $retention) as $file) {
            if (is_file($file) && unlink($file)) {
                $deleted[] = $file;
            }
        }

        return $deleted;
    }
}
