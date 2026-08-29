<?php

declare(strict_types=1);

namespace App\Support\Install;

final readonly class InstallCheck
{
    public const STATUS_PASS = 'pass';

    public const STATUS_FAIL = 'fail';

    public const STATUS_WARN = 'warn';

    public function __construct(
        public string $key,
        public string $label,
        public string $status,
        public string $message,
        public ?string $detail = null,
    ) {}

    public static function pass(string $key, string $label, string $message, ?string $detail = null): self
    {
        return new self($key, $label, self::STATUS_PASS, $message, $detail);
    }

    public static function fail(string $key, string $label, string $message, ?string $detail = null): self
    {
        return new self($key, $label, self::STATUS_FAIL, $message, $detail);
    }

    public static function warn(string $key, string $label, string $message, ?string $detail = null): self
    {
        return new self($key, $label, self::STATUS_WARN, $message, $detail);
    }

    public function isRequiredFailure(): bool
    {
        return $this->status === self::STATUS_FAIL;
    }
}
