<?php

namespace App\Enums;

enum Role: string
{
    case Student = 'student';
    case Moderator = 'moderator';
    case Admin = 'admin';
    case Teacher = 'teacher';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __('ui.roles.'.$this->value);
    }
}
