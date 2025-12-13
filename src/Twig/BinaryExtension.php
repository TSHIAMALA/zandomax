<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class BinaryExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('bin2hex', [$this, 'binaryToHex']),
        ];
    }

    public function binaryToHex(?string $binary): string
    {
        if ($binary === null) {
            return '';
        }
        return bin2hex($binary);
    }
}
