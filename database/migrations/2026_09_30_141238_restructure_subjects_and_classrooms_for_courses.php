<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->constrained('schools');
            $table->foreignId('course_id')->nullable()->constrained('courses');
            $table->unsignedTinyInteger('suggested_term_number')->nullable();
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->foreignId('school_id')->nullable()->constrained('schools');
            $table->foreignId('course_id')->nullable()->constrained('courses');
            $table->foreignId('academic_term_id')->nullable()->constrained('academic_terms');
            $table->string('code')->nullable();
            $table->unsignedTinyInteger('term_number')->default(1);
            $table->string('status')->default('active');
            $table->unsignedSmallInteger('capacity')->default(40);
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('teachers');
            $table->string('room')->nullable();
        });

        $this->backfillExistingData();

        Schema::table('subjects', function (Blueprint $table) {
            $table->unsignedSmallInteger('workload_hours')->change();
            $table->unsignedBigInteger('school_id')->nullable(false)->change();
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
            $table->unique(['course_id', 'code']);
        });

        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropColumn('school_year');
            $table->unsignedBigInteger('school_id')->nullable(false)->change();
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
            $table->unsignedBigInteger('academic_term_id')->nullable(false)->change();
            $table->string('code')->nullable(false)->change();
            $table->unique(['course_id', 'academic_term_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classrooms', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'academic_term_id', 'code']);
            $table->string('school_year')->default('');
            $table->dropConstrainedForeignId('homeroom_teacher_id');
            $table->dropConstrainedForeignId('academic_term_id');
            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn(['code', 'term_number', 'status', 'capacity', 'room']);
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'code']);
            $table->dropConstrainedForeignId('course_id');
            $table->dropConstrainedForeignId('school_id');
            $table->dropColumn('suggested_term_number');
            $table->string('workload_hours')->change();
        });
    }

    /**
     * Attach pre-existing rows to a default school, course and academic terms.
     */
    private function backfillExistingData(): void
    {
        if (! DB::table('classrooms')->exists() && ! DB::table('subjects')->exists()) {
            return;
        }

        $now = now();
        $schoolId = DB::table('schools')->insertGetId([
            'name' => 'Escola padrão', 'code' => 'DEFAULT', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $courseId = DB::table('courses')->insertGetId([
            'school_id' => $schoolId, 'name' => 'Geral', 'code' => 'GERAL', 'level' => 'bachelor',
            'duration_terms' => 8, 'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('users')->update(['school_id' => $schoolId]);
        DB::table('students')->update(['school_id' => $schoolId]);
        DB::table('teachers')->update(['school_id' => $schoolId]);
        DB::table('subjects')->update(['school_id' => $schoolId, 'course_id' => $courseId]);

        $termIds = [];
        foreach (DB::table('classrooms')->get() as $classroom) {
            $year = ctype_digit((string) $classroom->school_year) ? (int) $classroom->school_year : (int) $now->format('Y');

            $termIds[$year] ??= DB::table('academic_terms')->insertGetId([
                'school_id' => $schoolId, 'name' => "{$year}/1", 'year' => $year, 'number' => 1,
                'starts_at' => "{$year}-02-01", 'ends_at' => "{$year}-06-30", 'status' => 'open',
                'created_at' => $now, 'updated_at' => $now,
            ]);

            DB::table('classrooms')->where('id', $classroom->id)->update([
                'school_id' => $schoolId,
                'course_id' => $courseId,
                'academic_term_id' => $termIds[$year],
                'code' => 'T'.$classroom->id,
            ]);
        }
    }
};
