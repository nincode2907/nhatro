<?php

namespace App\Services\Exports;

use RuntimeException;

class ExportBuffer
{
    /** @param callable(string): void $writer */
    public static function capture(callable $writer): string
    {
        $path = tempnam(sys_get_temp_dir(), 'nhatro-export-');

        if ($path === false) {
            throw new RuntimeException('Không thể tạo file tạm để xuất dữ liệu.');
        }

        try {
            $writer($path);
            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new RuntimeException('Không thể đọc file xuất đã tạo.');
            }

            return $contents;
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
