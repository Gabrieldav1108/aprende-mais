<?php

namespace Database\Seeders;

use App\Enums\TermStatus;
use App\Models\AcademicTerm;
use Database\Seeders\Concerns\CreatesSchoolPeople;
use Illuminate\Database\Seeder;

class AcademicTermSeeder extends Seeder
{
    use CreatesSchoolPeople;

    /**
     * @var list<array{year: int, number: int, starts_at: string, ends_at: string, status: TermStatus}>
     */
    public const TERMS = [
        ['year' => 2026, 'number' => 1, 'starts_at' => '2026-02-02', 'ends_at' => '2026-06-30', 'status' => TermStatus::Closed],
        ['year' => 2026, 'number' => 2, 'starts_at' => '2026-08-03', 'ends_at' => '2026-12-18', 'status' => TermStatus::Open],
        ['year' => 2027, 'number' => 1, 'starts_at' => '2027-02-01', 'ends_at' => '2027-06-30', 'status' => TermStatus::Planned],
    ];

    /**
     * Seed a closed, an open and a planned academic term.
     */
    public function run(): void
    {
        $school = $this->defaultSchool();

        foreach (self::TERMS as $term) {
            AcademicTerm::firstOrCreate(['school_id' => $school->id, 'year' => $term['year'], 'number' => $term['number']], [
                'name' => "{$term['year']}/{$term['number']}",
                'starts_at' => $term['starts_at'],
                'ends_at' => $term['ends_at'],
                'status' => $term['status'],
            ]);
        }
    }
}
