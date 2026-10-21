<?php

namespace Database\Seeders;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\KnowledgeArea;
use Database\Seeders\Concerns\CreatesSchoolPeople;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    use CreatesSchoolPeople;

    /**
     * @var list<array{code: string, name: string, area: string, level: CourseLevel, duration_terms: int, status: CourseStatus}>
     */
    public const COURSES = [
        ['code' => 'ADS', 'name' => 'Análise e Desenvolvimento de Sistemas', 'area' => 'EXA', 'level' => CourseLevel::Technologist, 'duration_terms' => 6, 'status' => CourseStatus::Active],
        ['code' => 'ESW', 'name' => 'Engenharia de Software', 'area' => 'ENG', 'level' => CourseLevel::Bachelor, 'duration_terms' => 8, 'status' => CourseStatus::Active],
        ['code' => 'ADM', 'name' => 'Administração', 'area' => 'SOC', 'level' => CourseLevel::Bachelor, 'duration_terms' => 8, 'status' => CourseStatus::Active],
        ['code' => 'PED', 'name' => 'Pedagogia', 'area' => 'HUM', 'level' => CourseLevel::Licentiate, 'duration_terms' => 8, 'status' => CourseStatus::Active],
        ['code' => 'ENF', 'name' => 'Enfermagem', 'area' => 'SAU', 'level' => CourseLevel::Bachelor, 'duration_terms' => 10, 'status' => CourseStatus::Active],
        ['code' => 'MBA-GP', 'name' => 'MBA em Gestão de Projetos', 'area' => 'SOC', 'level' => CourseLevel::Postgraduate, 'duration_terms' => 3, 'status' => CourseStatus::Inactive],
    ];

    /**
     * Seed the courses of the default school.
     */
    public function run(): void
    {
        $this->callSilent(KnowledgeAreaSeeder::class);

        $school = $this->defaultSchool();
        $areas = KnowledgeArea::where('school_id', $school->id)->pluck('id', 'code');

        foreach (self::COURSES as $course) {
            Course::firstOrCreate(['school_id' => $school->id, 'code' => $course['code']], [
                'knowledge_area_id' => $areas[$course['area']],
                'name' => $course['name'],
                'level' => $course['level'],
                'duration_terms' => $course['duration_terms'],
                'status' => $course['status'],
            ]);
        }
    }
}
