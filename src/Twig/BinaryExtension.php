<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class BinaryExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('bin2hex', [$this, 'bin2hex']),
        ];
    }

    public function bin2hex(?string $binary): string
    {
        if ($binary === null) {
            return '';
        }
        return bin2hex($binary);
    }
}
