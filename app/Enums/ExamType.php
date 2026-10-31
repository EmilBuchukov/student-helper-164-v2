<?php

namespace App\Enums;

enum ExamType: string
{
    case Test = 'test';
    case Exam = 'exam';
    case Oral = 'oral';
    case Other = 'other';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return __('ui.exams.types.'.$this->value);
    }
}
