<?php

declare(strict_types=1);

namespace Tests\Unit\Install;

use App\Support\Install\PhpVersionConstraint;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhpVersionConstraintTest extends TestCase
{
    #[DataProvider('constraintProvider')]
    public function test_constraint_evaluation(string $runtime, string $constraint, bool $expected): void
    {
        $this->assertSame($expected, PhpVersionConstraint::satisfies($runtime, $constraint));
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function constraintProvider(): array
    {
        return [
            'php 8.3 satisfies ^8.3' => ['8.3.12', '^8.3', true],
            'php 8.4 satisfies ^8.3' => ['8.4.0', '^8.3', true],
            'php 8.2 fails ^8.3' => ['8.2.29', '^8.3', false],
            'php 9.0 fails ^8.3' => ['9.0.0', '^8.3', false],
            'greater than or equal constraint' => ['8.3.0', '>=8.3', true],
        ];
    }

    public function test_constraint_is_read_from_composer_json(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'composer-');
        file_put_contents($path, json_encode([
            'require' => ['php' => '^8.3'],
        ], JSON_THROW_ON_ERROR));

        $this->assertSame('^8.3', PhpVersionConstraint::fromComposerJson($path));

        unlink($path);
    }
}
