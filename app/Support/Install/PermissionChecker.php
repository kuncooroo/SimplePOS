<?php

declare(strict_types=1);

namespace App\Support\Install;

final class PermissionChecker
{
    /**
     * @var array<string, string>|null
     */
    private ?array $paths = null;

    public function __construct(
        private ?FilesystemProbe $filesystem = null,
        ?array $paths = null,
    ) {
        $this->filesystem ??= new FilesystemProbe;
        $this->paths = $paths;
    }

    public function run(): InstallCheckReport
    {
        $checks = [];

        foreach ($this->paths() as $label => $path) {
            $checks[] = $this->filesystem->isWritable($path)
                ? InstallCheck::pass(
                    key: 'perm_'.str_replace(['/', '\\', ' '], '_', $label),
                    label: $label,
                    message: 'Writable by the PHP process.',
                )
                : InstallCheck::fail(
                    key: 'perm_'.str_replace(['/', '\\', ' '], '_', $label),
                    label: $label,
                    message: $this->failureMessage($label),
                );
        }

        return new InstallCheckReport($checks);
    }

    /**
     * @return array<string, string>
     */
    private function paths(): array
    {
        if ($this->paths !== null) {
            return $this->paths;
        }

        return [
            'bootstrap/cache' => base_path('bootstrap/cache'),
            'storage' => storage_path(),
            'storage/app' => storage_path('app'),
            'storage/app/public' => storage_path('app/public'),
            'storage/framework' => storage_path('framework'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/logs' => storage_path('logs'),
        ];
    }

    private function failureMessage(string $label): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return sprintf(
                '%s is not writable by the PHP process. Grant Modify permission to the IIS_IUSRS or application pool identity, or run the site under an account that can write to this folder.',
                $label,
            );
        }

        return sprintf(
            '%s is not writable by the PHP process. Ensure the web server user owns or can write to this folder (for example chmod 775 with the correct group).',
            $label,
        );
    }
}
