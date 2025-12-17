<?php

namespace App\Enum;

enum Periodicity: string
{
    case DAY = 'day';
    case WEEK = 'week';
    case MONTH = 'month';
    case QUARTER = 'quarter';
    case SEMESTER = 'semester';
    case YEAR = 'year';

    public function label(): string
    {
        return match($this) {
            self::DAY => 'Jour',
            self::WEEK => 'Semaine',
            self::MONTH => 'Mois',
            self::QUARTER => 'Trimestre',
            self::SEMESTER => 'Semestre',
            self::YEAR => 'Année',
        };
    }

    public function getLabel(): string
    {
        return $this->label();
    }
}
