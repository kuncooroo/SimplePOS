<?php

declare(strict_types=1);

namespace Tests\Unit\Install;

use App\Support\Install\ExtensionChecker;
use App\Support\Install\PhpExtensionProbe;
use Tests\TestCase;

class ExtensionCheckerTest extends TestCase
{
    public function test_missing_required_extension_fails_the_report(): void
    {
        $loaded = [];

        foreach (ExtensionChecker::REQUIRED as $extension) {
            $loaded[$extension] = $extension !== 'pdo_mysql';
        }

        $report = (new ExtensionChecker(new FakePhpExtensionProbe($loaded)))->run();

        $this->assertFalse($report->allRequiredPassed());
        $this->assertSame(1, $report->requiredFailureCount());
    }

    public function test_all_required_extensions_pass_optional_warnings_do_not_block(): void
    {
        $loaded = array_fill_keys(ExtensionChecker::REQUIRED, true);

        $report = (new ExtensionChecker(new FakePhpExtensionProbe($loaded)))->run();

        $this->assertTrue($report->allRequiredPassed());
        $this->assertGreaterThanOrEqual(2, count(array_filter(
            $report->checks,
            static fn ($check): bool => $check->status === 'warn',
        )));
    }
}

final class FakePhpExtensionProbe extends PhpExtensionProbe
{
    /**
     * @param  array<string, bool>  $loaded
     */
    public function __construct(private array $loaded) {}

    public function loaded(string $extension): bool
    {
        return $this->loaded[$extension] ?? false;
    }
}
