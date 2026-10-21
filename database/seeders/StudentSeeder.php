<?php

namespace Database\Seeders;

use App\Enums\StudentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Student;
use Database\Seeders\Concerns\CreatesSchoolPeople;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    use CreatesSchoolPeople;

    /**
     * @var list<array{enrollment_number: string, name: string, course: string, status: StudentStatus}>
     */
    public const STUDENTS = [
        ['enrollment_number' => 'A26001', 'name' => 'Lucas Ferreira', 'course' => 'ADS', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26002', 'name' => 'Ana Clara Ribeiro', 'course' => 'ADS', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26003', 'name' => 'Pedro Santos', 'course' => 'ADS', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26004', 'name' => 'Isabela Gomes', 'course' => 'ADS', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26005', 'name' => 'Matheus Oliveira', 'course' => 'ADS', 'status' => StudentStatus::Locked],
        ['enrollment_number' => 'A26006', 'name' => 'Laura Mendes', 'course' => 'ESW', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26007', 'name' => 'Gustavo Barbosa', 'course' => 'ESW', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26008', 'name' => 'Sofia Carvalho', 'course' => 'ESW', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26009', 'name' => 'Rafael Teixeira', 'course' => 'ESW', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26010', 'name' => 'Valentina Araújo', 'course' => 'ADM', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26011', 'name' => 'Enzo Cardoso', 'course' => 'ADM', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26012', 'name' => 'Helena Pereira', 'course' => 'ADM', 'status' => StudentStatus::Dropped],
        ['enrollment_number' => 'A26013', 'name' => 'Davi Moreira', 'course' => 'PED', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26014', 'name' => 'Alice Freitas', 'course' => 'PED', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26015', 'name' => 'Heitor Castro', 'course' => 'ENF', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26016', 'name' => 'Manuela Dias', 'course' => 'ENF', 'status' => StudentStatus::Active],
        ['enrollment_number' => 'A26017', 'name' => 'Bernardo Lopes', 'course' => 'ENF', 'status' => StudentStatus::Graduated],
    ];

    /**
     * Seed students with their access account, personal data and course.
     */
    public function run(): void
    {
        $this->callSilent([RolesAndPermissionsSeeder::class, CourseSeeder::class]);

        $school = $this->defaultSchool();
        $courses = Course::where('school_id', $school->id)->pluck('id', 'code');

        foreach (self::STUDENTS as $index => $student) {
            $user = $this->createUser($school, $student['name'], $this->emailFor($student['name']), UserRole::Student->value);

            Student::firstOrCreate(['person_id' => $this->createPerson($user, 200 + $index)->id], [
                'course_id' => $courses[$student['course']],
                'enrollment_number' => $student['enrollment_number'],
                'status' => $student['status'],
            ]);
        }
    }

    /**
     * Build an e-mail such as "ana.ribeiro@aluno.aprendemais.com" from the first and last names.
     */
    private function emailFor(string $name): string
    {
        $names = explode(' ', strtolower(str($name)->ascii()->toString()));

        return $names[0].'.'.end($names).'@aluno.aprendemais.com';
    }
}
