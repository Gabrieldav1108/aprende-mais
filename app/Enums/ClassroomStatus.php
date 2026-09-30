<?php

namespace App\Enums;

enum ClassroomStatus: string
{
    case Planned = 'planned';
    case Active = 'active';
    case Finished = 'finished';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planejada',
            self::Active => 'Em andamento',
            self::Finished => 'Encerrada',
            self::Cancelled => 'Cancelada',
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
