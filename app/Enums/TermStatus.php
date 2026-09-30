<?php

namespace App\Enums;

enum TermStatus: string
{
    case Planned = 'planned';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Planned => 'Planejado',
            self::Open => 'Aberto',
            self::Closed => 'Encerrado',
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
