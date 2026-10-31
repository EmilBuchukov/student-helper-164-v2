<?php

namespace App\Enums;

enum RsvpStatus: string
{
    case Going = 'going';
    case Interested = 'interested';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
