<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Teacher;
use Database\Seeders\Concerns\CreatesSchoolPeople;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    use CreatesSchoolPeople;

    /**
     * @var list<array{registration_number: string, name: string, email: string, qualification: string}>
     */
    public const TEACHERS = [
        ['registration_number' => 'T01001', 'name' => 'Mariana Costa', 'email' => 'mariana.costa@aprendemais.com', 'qualification' => 'Doutorado em Ciência da Computação'],
        ['registration_number' => 'T01002', 'name' => 'Ricardo Almeida', 'email' => 'ricardo.almeida@aprendemais.com', 'qualification' => 'Mestrado em Engenharia de Software'],
        ['registration_number' => 'T01003', 'name' => 'Fernanda Lima', 'email' => 'fernanda.lima@aprendemais.com', 'qualification' => 'Mestrado em Administração'],
        ['registration_number' => 'T01004', 'name' => 'Paulo Henrique Souza', 'email' => 'paulo.souza@aprendemais.com', 'qualification' => 'Especialização em Banco de Dados'],
        ['registration_number' => 'T01005', 'name' => 'Juliana Rocha', 'email' => 'juliana.rocha@aprendemais.com', 'qualification' => 'Doutorado em Educação'],
        ['registration_number' => 'T01006', 'name' => 'Carlos Eduardo Martins', 'email' => 'carlos.martins@aprendemais.com', 'qualification' => 'Mestrado em Enfermagem'],
        ['registration_number' => 'T01007', 'name' => 'Beatriz Nogueira', 'email' => 'beatriz.nogueira@aprendemais.com', 'qualification' => 'Mestrado em Matemática Aplicada'],
    ];

    /**
     * Seed teachers with their access account and personal data.
     */
    public function run(): void
    {
        $this->callSilent(RolesAndPermissionsSeeder::class);

        $school = $this->defaultSchool();

        foreach (self::TEACHERS as $index => $teacher) {
            $user = $this->createUser($school, $teacher['name'], $teacher['email'], UserRole::Teacher->value);

            Teacher::firstOrCreate(['person_id' => $this->createPerson($user, 100 + $index)->id], [
                'registration_number' => $teacher['registration_number'],
                'qualification' => $teacher['qualification'],
            ]);
        }
    }
}
