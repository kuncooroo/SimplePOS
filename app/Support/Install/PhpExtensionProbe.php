<?php

declare(strict_types=1);

namespace App\Support\Install;

class PhpExtensionProbe
{
    public function loaded(string $extension): bool
    {
        return extension_loaded($extension);
    }
}
