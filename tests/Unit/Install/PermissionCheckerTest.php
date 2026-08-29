<?php

declare(strict_types=1);

namespace Tests\Unit\Install;

use App\Support\Install\FilesystemProbe;
use App\Support\Install\PermissionChecker;
use Tests\TestCase;

class PermissionCheckerTest extends TestCase
{
    public function test_unwritable_path_fails_the_report(): void
    {
        $blockedPath = storage_path('logs');

        $report = (new PermissionChecker(
            new FakeFilesystemProbe(writable: [
                $blockedPath => false,
            ]),
            paths: [
                'storage/logs' => $blockedPath,
            ],
        ))->run();

        $this->assertFalse($report->allRequiredPassed());
        $this->assertSame(1, $report->requiredFailureCount());
    }

    public function test_writable_paths_pass(): void
    {
        $path = storage_path('framework/cache');

        $report = (new PermissionChecker(
            new FakeFilesystemProbe(writable: [
                $path => true,
            ]),
            paths: [
                'storage/framework/cache' => $path,
            ],
        ))->run();

        $this->assertTrue($report->allRequiredPassed());
    }
}

final class FakeFilesystemProbe extends FilesystemProbe
{
    /**
     * @param  array<string, bool>  $writable
     */
    public function __construct(
        private array $writable = [],
        private int|false $freeBytes = false,
    ) {}

    public function isWritable(string $path): bool
    {
        return $this->writable[$path] ?? true;
    }

    public function freeBytes(string $path): int|false
    {
        return $this->freeBytes;
    }
}
