<?php

namespace App\Enums;

enum StudentStatus: string
{
    case Active = 'active';
    case Locked = 'locked';
    case Graduated = 'graduated';
    case Dropped = 'dropped';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Locked => 'Trancado',
            self::Graduated => 'Formado',
            self::Dropped => 'Desistente',
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
