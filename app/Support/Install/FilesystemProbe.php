<?php

declare(strict_types=1);

namespace App\Support\Install;

class FilesystemProbe
{
    public function isWritable(string $path): bool
    {
        if (! file_exists($path)) {
            return is_writable(dirname($path));
        }

        return is_writable($path);
    }

    public function freeBytes(string $path): int|false
    {
        $free = disk_free_space($path);

        if ($free === false) {
            return false;
        }

        return (int) $free;
    }
}
