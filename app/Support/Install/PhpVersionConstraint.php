<?php

declare(strict_types=1);

namespace App\Support\Install;

use JsonException;

final class PhpVersionConstraint
{
    public static function fromComposerJson(?string $composerJsonPath = null): string
    {
        $path = $composerJsonPath ?? base_path('composer.json');

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '^8.3';
        }

        return (string) ($data['require']['php'] ?? '^8.3');
    }

    public static function satisfies(string $phpVersion, string $constraint): bool
    {
        $constraint = trim($constraint);

        if ($constraint === '') {
            return true;
        }

        if (preg_match('/^\^(\d+)\.(\d+)(?:\.(\d+))?/', $constraint, $matches) === 1) {
            $major = (int) $matches[1];
            $minor = (int) $matches[2];
            $minimum = sprintf('%d.%d.0', $major, $minor);
            $exclusiveUpper = sprintf('%d.0.0', $major + 1);

            return version_compare($phpVersion, $minimum, '>=')
                && version_compare($phpVersion, $exclusiveUpper, '<');
        }

        if (preg_match('/^>=\s*([\d.]+)/', $constraint, $matches) === 1) {
            return version_compare($phpVersion, $matches[1], '>=');
        }

        if (preg_match('/^>\s*([\d.]+)/', $constraint, $matches) === 1) {
            return version_compare($phpVersion, $matches[1], '>');
        }

        return version_compare($phpVersion, ltrim($constraint, '='), '>=');
    }
}
