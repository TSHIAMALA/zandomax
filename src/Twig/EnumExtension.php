<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class EnumExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('enum_label', [$this, 'getEnumLabel']),
        ];
    }

    public function getEnumLabel($enum): string
    {
        if ($enum === null) {
            return '';
        }

        // Si l'enum a une méthode label(), l'utiliser
        if (method_exists($enum, 'label')) {
            return $enum->label();
        }

        // Sinon, retourner la valeur brute
        return $enum->value ?? (string) $enum;
    }
}
