<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\KnowledgeArea;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Seeders\Concerns\CreatesSchoolPeople;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    use CreatesSchoolPeople;

    /**
     * Catalog subjects, their teacher and the term each course suggests for them.
     *
     * @var list<array{code: string, name: string, area: string, workload_hours: int, teacher: string, courses: array<string, int>}>
     */
    public const SUBJECTS = [
        ['code' => 'LOG-101', 'name' => 'Lógica de Programação', 'area' => 'EXA', 'workload_hours' => 80, 'teacher' => 'T01001', 'courses' => ['ADS' => 1, 'ESW' => 1]],
        ['code' => 'MAT-101', 'name' => 'Matemática Discreta', 'area' => 'EXA', 'workload_hours' => 60, 'teacher' => 'T01007', 'courses' => ['ADS' => 1, 'ESW' => 1]],
        ['code' => 'ARQ-101', 'name' => 'Arquitetura de Computadores', 'area' => 'EXA', 'workload_hours' => 60, 'teacher' => 'T01002', 'courses' => ['ADS' => 1, 'ESW' => 2]],
        ['code' => 'POO-201', 'name' => 'Programação Orientada a Objetos', 'area' => 'EXA', 'workload_hours' => 80, 'teacher' => 'T01001', 'courses' => ['ADS' => 2, 'ESW' => 2]],
        ['code' => 'BD-201', 'name' => 'Banco de Dados', 'area' => 'EXA', 'workload_hours' => 80, 'teacher' => 'T01004', 'courses' => ['ADS' => 2, 'ESW' => 3]],
        ['code' => 'ESW-201', 'name' => 'Engenharia de Requisitos', 'area' => 'EXA', 'workload_hours' => 60, 'teacher' => 'T01002', 'courses' => ['ADS' => 2, 'ESW' => 2]],
        ['code' => 'WEB-301', 'name' => 'Desenvolvimento Web', 'area' => 'EXA', 'workload_hours' => 80, 'teacher' => 'T01001', 'courses' => ['ADS' => 3, 'ESW' => 4]],
        ['code' => 'EST-201', 'name' => 'Estatística Aplicada', 'area' => 'EXA', 'workload_hours' => 60, 'teacher' => 'T01007', 'courses' => ['ESW' => 3, 'ADM' => 3]],
        ['code' => 'ADM-101', 'name' => 'Teoria Geral da Administração', 'area' => 'SOC', 'workload_hours' => 60, 'teacher' => 'T01003', 'courses' => ['ADM' => 1]],
        ['code' => 'CON-101', 'name' => 'Contabilidade Básica', 'area' => 'SOC', 'workload_hours' => 60, 'teacher' => 'T01003', 'courses' => ['ADM' => 1]],
        ['code' => 'MKT-301', 'name' => 'Marketing', 'area' => 'SOC', 'workload_hours' => 60, 'teacher' => 'T01003', 'courses' => ['ADM' => 3]],
        ['code' => 'GPR-301', 'name' => 'Gestão de Projetos', 'area' => 'SOC', 'workload_hours' => 60, 'teacher' => 'T01002', 'courses' => ['ADM' => 3, 'ESW' => 5, 'MBA-GP' => 1]],
        ['code' => 'PED-101', 'name' => 'Fundamentos da Educação', 'area' => 'HUM', 'workload_hours' => 60, 'teacher' => 'T01005', 'courses' => ['PED' => 1]],
        ['code' => 'PSI-101', 'name' => 'Psicologia da Educação', 'area' => 'HUM', 'workload_hours' => 60, 'teacher' => 'T01005', 'courses' => ['PED' => 1]],
        ['code' => 'COM-101', 'name' => 'Comunicação e Expressão', 'area' => 'HUM', 'workload_hours' => 40, 'teacher' => 'T01005', 'courses' => ['PED' => 1, 'ADM' => 1, 'ENF' => 1]],
        ['code' => 'ANA-101', 'name' => 'Anatomia Humana', 'area' => 'SAU', 'workload_hours' => 80, 'teacher' => 'T01006', 'courses' => ['ENF' => 1]],
        ['code' => 'ENF-201', 'name' => 'Fundamentos de Enfermagem', 'area' => 'SAU', 'workload_hours' => 80, 'teacher' => 'T01006', 'courses' => ['ENF' => 2]],
    ];

    /**
     * Seed the subject catalog and link each subject to the courses that offer it.
     */
    public function run(): void
    {
        $this->callSilent([CourseSeeder::class, TeacherSeeder::class]);

        $school = $this->defaultSchool();
        $areas = KnowledgeArea::where('school_id', $school->id)->pluck('id', 'code');
        $courses = Course::where('school_id', $school->id)->pluck('id', 'code');
        $teachers = Teacher::pluck('id', 'registration_number');

        foreach (self::SUBJECTS as $subject) {
            $model = Subject::firstOrCreate(['school_id' => $school->id, 'code' => $subject['code']], [
                'knowledge_area_id' => $areas[$subject['area']],
                'teacher_id' => $teachers[$subject['teacher']],
                'name' => $subject['name'],
                'workload_hours' => $subject['workload_hours'],
            ]);

            $model->courses()->syncWithoutDetaching(collect($subject['courses'])->mapWithKeys(
                fn (int $term, string $course): array => [$courses[$course] => ['suggested_term_number' => $term]],
            )->all());
        }
    }
}
