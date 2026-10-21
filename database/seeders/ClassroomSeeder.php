<?php

namespace Database\Seeders;

use App\Enums\ClassroomStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\Shift;
use App\Enums\StudentStatus;
use App\Models\AcademicTerm;
use App\Models\Classroom;
use App\Models\ClassroomStudent;
use App\Models\ClassroomSubject;
use App\Models\Course;
use App\Models\Student;
use App\Models\Teacher;
use Database\Seeders\Concerns\CreatesSchoolPeople;
use Illuminate\Database\Seeder;

class ClassroomSeeder extends Seeder
{
    use CreatesSchoolPeople;

    /**
     * Classrooms with the subjects their course suggests for the term number and the course's students.
     *
     * @var list<array{code: string, name: string, course: string, term: string, term_number: int, shift: Shift, status: ClassroomStatus, capacity: int, room: string, homeroom_teacher: string}>
     */
    public const CLASSROOMS = [
        ['code' => 'ADS-1N-261', 'name' => 'ADS 1º período - Noite', 'course' => 'ADS', 'term' => '2026/1', 'term_number' => 1, 'shift' => Shift::Evening, 'status' => ClassroomStatus::Finished, 'capacity' => 40, 'room' => 'Lab 01', 'homeroom_teacher' => 'T01001'],
        ['code' => 'ADS-2N-262', 'name' => 'ADS 2º período - Noite', 'course' => 'ADS', 'term' => '2026/2', 'term_number' => 2, 'shift' => Shift::Evening, 'status' => ClassroomStatus::Active, 'capacity' => 40, 'room' => 'Lab 02', 'homeroom_teacher' => 'T01001'],
        ['code' => 'ESW-1M-262', 'name' => 'ESW 1º período - Manhã', 'course' => 'ESW', 'term' => '2026/2', 'term_number' => 1, 'shift' => Shift::Morning, 'status' => ClassroomStatus::Active, 'capacity' => 35, 'room' => 'Sala 101', 'homeroom_teacher' => 'T01002'],
        ['code' => 'ADM-1N-262', 'name' => 'ADM 1º período - Noite', 'course' => 'ADM', 'term' => '2026/2', 'term_number' => 1, 'shift' => Shift::Evening, 'status' => ClassroomStatus::Active, 'capacity' => 50, 'room' => 'Sala 204', 'homeroom_teacher' => 'T01003'],
        ['code' => 'PED-1T-262', 'name' => 'PED 1º período - Tarde', 'course' => 'PED', 'term' => '2026/2', 'term_number' => 1, 'shift' => Shift::Afternoon, 'status' => ClassroomStatus::Active, 'capacity' => 45, 'room' => 'Sala 105', 'homeroom_teacher' => 'T01005'],
        ['code' => 'ENF-1M-262', 'name' => 'ENF 1º período - Manhã', 'course' => 'ENF', 'term' => '2026/2', 'term_number' => 1, 'shift' => Shift::Morning, 'status' => ClassroomStatus::Active, 'capacity' => 30, 'room' => 'Lab Saúde', 'homeroom_teacher' => 'T01006'],
        ['code' => 'ADS-3N-271', 'name' => 'ADS 3º período - Noite', 'course' => 'ADS', 'term' => '2027/1', 'term_number' => 3, 'shift' => Shift::Evening, 'status' => ClassroomStatus::Planned, 'capacity' => 40, 'room' => 'Lab 02', 'homeroom_teacher' => 'T01004'],
    ];

    /**
     * Seed classrooms with their offered subjects and enrolled students.
     */
    public function run(): void
    {
        $this->callSilent([AcademicTermSeeder::class, SubjectSeeder::class, StudentSeeder::class]);

        $school = $this->defaultSchool();
        $courses = Course::where('school_id', $school->id)->get()->keyBy('code');
        $terms = AcademicTerm::where('school_id', $school->id)->pluck('id', 'name');
        $teachers = Teacher::pluck('id', 'registration_number');

        foreach (self::CLASSROOMS as $data) {
            $course = $courses[$data['course']];

            $classroom = Classroom::firstOrCreate([
                'course_id' => $course->id,
                'academic_term_id' => $terms[$data['term']],
                'code' => $data['code'],
            ], [
                'school_id' => $school->id,
                'name' => $data['name'],
                'term_number' => $data['term_number'],
                'shift' => $data['shift'],
                'status' => $data['status'],
                'capacity' => $data['capacity'],
                'room' => $data['room'],
                'homeroom_teacher_id' => $teachers[$data['homeroom_teacher']],
            ]);

            $this->offerSubjects($classroom, $course, $teachers->first());

            if ($data['status'] !== ClassroomStatus::Planned) {
                $this->enrollStudents($classroom, $course);
            }
        }
    }

    /**
     * Offer the subjects the course suggests for the classroom's term, each taught by the subject's teacher.
     */
    private function offerSubjects(Classroom $classroom, Course $course, int $fallbackTeacherId): void
    {
        $subjects = $course->subjects()->wherePivot('suggested_term_number', $classroom->term_number)->get();

        foreach ($subjects as $subject) {
            ClassroomSubject::firstOrCreate(
                ['classroom_id' => $classroom->id, 'subject_id' => $subject->id],
                ['teacher_id' => $subject->teacher_id ?? $fallbackTeacherId],
            );
        }
    }

    /**
     * Enroll the course's active students, completing the enrollment when the classroom is finished.
     */
    private function enrollStudents(Classroom $classroom, Course $course): void
    {
        $students = Student::where('course_id', $course->id)
            ->where('status', StudentStatus::Active)
            ->limit($classroom->capacity)
            ->get();

        foreach ($students as $student) {
            ClassroomStudent::firstOrCreate(['classroom_id' => $classroom->id, 'student_id' => $student->id], [
                'enrolled_at' => $classroom->academicTerm->starts_at,
                'status' => $classroom->status === ClassroomStatus::Finished ? EnrollmentStatus::Completed : EnrollmentStatus::Active,
            ]);
        }
    }
}
