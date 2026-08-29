<?php

declare(strict_types=1);

namespace App\Support\Install;

final class RequirementChecker
{
    private const DISK_WARNING_BYTES = 104_857_600;

    public function __construct(
        private ?string $composerJsonPath = null,
        private ?FilesystemProbe $filesystem = null,
    ) {
        $this->filesystem ??= new FilesystemProbe;
    }

    public function run(): InstallCheckReport
    {
        return new InstallCheckReport([
            $this->checkPhpVersion(),
            $this->checkPublicFrontController(),
            $this->checkDocumentRoot(),
            $this->checkStorageDiskSpace(),
        ]);
    }

    private function checkPhpVersion(): InstallCheck
    {
        $constraint = PhpVersionConstraint::fromComposerJson($this->composerJsonPath);
        $runtime = PHP_VERSION;

        if (PhpVersionConstraint::satisfies($runtime, $constraint)) {
            return InstallCheck::pass(
                key: 'php_version',
                label: 'PHP version',
                message: sprintf('PHP %s meets the required constraint (%s).', $runtime, $constraint),
                detail: $constraint,
            );
        }

        return InstallCheck::fail(
            key: 'php_version',
            label: 'PHP version',
            message: sprintf('PHP %s does not meet the required constraint (%s).', $runtime, $constraint),
            detail: $constraint,
        );
    }

    private function checkPublicFrontController(): InstallCheck
    {
        $indexPath = public_path('index.php');

        if (is_file($indexPath)) {
            return InstallCheck::pass(
                key: 'public_index',
                label: 'Public front controller',
                message: 'public/index.php is present.',
            );
        }

        return InstallCheck::fail(
            key: 'public_index',
            label: 'Public front controller',
            message: 'public/index.php is missing. Point the web server document root at the public/ folder.',
        );
    }

    private function checkDocumentRoot(): InstallCheck
    {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

        if ($documentRoot === '') {
            return InstallCheck::pass(
                key: 'document_root',
                label: 'Web document root',
                message: 'Document root could not be verified from the current request. Confirm the vhost targets public/.',
            );
        }

        $normalizedRoot = str_replace('\\', '/', rtrim($documentRoot, '/'));
        $normalizedPublic = str_replace('\\', '/', rtrim(public_path(), '/'));

        if ($normalizedRoot === $normalizedPublic || str_ends_with($normalizedRoot, '/public')) {
            return InstallCheck::pass(
                key: 'document_root',
                label: 'Web document root',
                message: 'The web server document root appears to target public/.',
                detail: $documentRoot,
            );
        }

        return InstallCheck::warn(
            key: 'document_root',
            label: 'Web document root',
            message: 'The document root may not point at public/. Set the vhost to the public/ folder before going live.',
            detail: $documentRoot,
        );
    }

    private function checkStorageDiskSpace(): InstallCheck
    {
        $freeBytes = $this->filesystem->freeBytes(storage_path());

        if ($freeBytes === false) {
            return InstallCheck::pass(
                key: 'disk_space',
                label: 'Free disk space',
                message: 'Free disk space could not be measured on this server.',
            );
        }

        if ($freeBytes < self::DISK_WARNING_BYTES) {
            return InstallCheck::warn(
                key: 'disk_space',
                label: 'Free disk space',
                message: 'Less than 100 MB is free under storage/. Consider freeing space before install.',
                detail: sprintf('%.1f MB free', $freeBytes / 1_048_576),
            );
        }

        return InstallCheck::pass(
            key: 'disk_space',
            label: 'Free disk space',
            message: 'Enough free disk space is available under storage/.',
            detail: sprintf('%.1f MB free', $freeBytes / 1_048_576),
        );
    }
}
