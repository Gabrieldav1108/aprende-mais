<?php

namespace App\Enums;

enum CourseLevel: string
{
    case Bachelor = 'bachelor';
    case Licentiate = 'licentiate';
    case Technologist = 'technologist';
    case Postgraduate = 'postgraduate';

    public function label(): string
    {
        return match ($this) {
            self::Bachelor => 'Bacharelado',
            self::Licentiate => 'Licenciatura',
            self::Technologist => 'Tecnólogo',
            self::Postgraduate => 'Pós-graduação',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_column(array_map(fn (self $case) => [$case->value, $case->label()], self::cases()), 1, 0);
    }
}
