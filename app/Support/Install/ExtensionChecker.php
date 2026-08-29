<?php

declare(strict_types=1);

namespace App\Support\Install;

final class ExtensionChecker
{
    /**
     * @var list<string>
     */
    public const REQUIRED = [
        'bcmath',
        'ctype',
        'curl',
        'dom',
        'fileinfo',
        'filter',
        'hash',
        'mbstring',
        'openssl',
        'pcre',
        'pdo',
        'pdo_mysql',
        'session',
        'tokenizer',
        'xml',
        'json',
    ];

    /**
     * @var list<string>
     */
    public const OPTIONAL = [
        'gd',
        'exif',
    ];

    public function __construct(
        private ?PhpExtensionProbe $probe = null,
    ) {
        $this->probe ??= new PhpExtensionProbe;
    }

    public function run(): InstallCheckReport
    {
        $checks = [];

        foreach (self::REQUIRED as $extension) {
            $checks[] = $this->probe->loaded($extension)
                ? InstallCheck::pass(
                    key: 'ext_'.$extension,
                    label: $extension,
                    message: 'Loaded',
                )
                : InstallCheck::fail(
                    key: 'ext_'.$extension,
                    label: $extension,
                    message: 'Missing required PHP extension.',
                );
        }

        foreach (self::OPTIONAL as $extension) {
            $checks[] = $this->probe->loaded($extension)
                ? InstallCheck::pass(
                    key: 'ext_'.$extension,
                    label: $extension.' (optional)',
                    message: 'Loaded',
                )
                : InstallCheck::warn(
                    key: 'ext_'.$extension,
                    label: $extension.' (optional)',
                    message: 'Optional extension is not loaded. Logo processing may be limited.',
                );
        }

        return new InstallCheckReport($checks);
    }
}
