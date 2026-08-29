<?php

declare(strict_types=1);

namespace App\Support\Install;

final readonly class InstallCheckReport
{
    /**
     * @param  list<InstallCheck>  $checks
     */
    public function __construct(public array $checks) {}

    public function allRequiredPassed(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->isRequiredFailure()) {
                return false;
            }
        }

        return true;
    }

    public function requiredFailureCount(): int
    {
        return count(array_filter(
            $this->checks,
            static fn (InstallCheck $check): bool => $check->isRequiredFailure(),
        ));
    }
}
