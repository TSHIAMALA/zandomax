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
}
