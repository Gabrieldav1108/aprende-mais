<?php

namespace Database\Seeders;

use App\Models\KnowledgeArea;
use Database\Seeders\Concerns\CreatesSchoolPeople;
use Illuminate\Database\Seeder;

class KnowledgeAreaSeeder extends Seeder
{
    use CreatesSchoolPeople;

    /**
     * Ensure the default knowledge areas exist, since a school created with model events muted does not get them.
     */
    public function run(): void
    {
        $school = $this->defaultSchool();

        foreach (KnowledgeArea::DEFAULTS as $code => $name) {
            KnowledgeArea::firstOrCreate(['school_id' => $school->id, 'code' => $code], ['name' => $name]);
        }
    }
}
