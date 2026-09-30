<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Ativa',
            self::OnHold => 'Trancada',
            self::Completed => 'Concluída',
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
