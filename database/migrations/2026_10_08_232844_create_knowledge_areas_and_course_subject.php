<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Knowledge areas every school starts with (CNPq/CAPES major areas).
     *
     * @var array<string, string>
     */
    private const DEFAULT_AREAS = [
        'EXA' => 'Ciências Exatas e da Terra',
        'BIO' => 'Ciências Biológicas',
        'ENG' => 'Engenharias',
        'SAU' => 'Ciências da Saúde',
        'AGR' => 'Ciências Agrárias',
        'SOC' => 'Ciências Sociais Aplicadas',
        'HUM' => 'Ciências Humanas',
        'LLA' => 'Linguística, Letras e Artes',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('knowledge_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'name']);
        });

        Schema::create('course_subject', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->unsignedTinyInteger('suggested_term_number')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'subject_id']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignId('knowledge_area_id')->nullable()->constrained('knowledge_areas');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('knowledge_area_id')->nullable()->constrained('knowledge_areas');
        });

        $this->createDefaultAreas();
        $this->moveSubjectsToTheCatalog();

        Schema::table('subjects', function (Blueprint $table) {
            // Order matters on MySQL: dropping the column first would shrink the unique index to just code
            $table->dropForeign(['course_id']);
            $table->dropUnique(['course_id', 'code']);
            $table->dropColumn(['course_id', 'suggested_term_number']);
            $table->index(['school_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * Subjects merged by the migration stay merged; each subject goes back to the first course it was linked to.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropIndex(['school_id', 'code']);
            $table->foreignId('course_id')->nullable()->constrained('courses');
            $table->unsignedTinyInteger('suggested_term_number')->nullable();
        });

        foreach (DB::table('course_subject')->orderBy('id')->get() as $link) {
            DB::table('subjects')
                ->where('id', $link->subject_id)
                ->whereNull('course_id')
                ->update(['course_id' => $link->course_id, 'suggested_term_number' => $link->suggested_term_number]);
        }

        Schema::table('subjects', function (Blueprint $table) {
            $table->unique(['course_id', 'code']);
            $table->dropConstrainedForeignId('knowledge_area_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('knowledge_area_id');
        });

        Schema::dropIfExists('course_subject');
        Schema::dropIfExists('knowledge_areas');
    }

    /**
     * Give every existing school the default knowledge areas.
     */
    private function createDefaultAreas(): void
    {
        $now = now();

        foreach (DB::table('schools')->pluck('id') as $schoolId) {
            foreach (self::DEFAULT_AREAS as $code => $name) {
                DB::table('knowledge_areas')->insert([
                    'school_id' => $schoolId,
                    'name' => $name,
                    'code' => $code,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Link every subject to the course it belonged to and make codes unique per school.
     *
     * Subjects of the same school with the same code and name become a single subject linked to every course.
     * When the code is the same but the name differs, the course code is appended to the later subject's code.
     */
    private function moveSubjectsToTheCatalog(): void
    {
        $now = now();
        $kept = [];

        foreach (DB::table('subjects')->orderBy('id')->get() as $subject) {
            $subjectId = $subject->id;

            if ($subject->deleted_at === null) {
                $key = $subject->school_id.'|'.mb_strtolower($subject->code);

                if (isset($kept[$key])) {
                    if ($kept[$key]['name'] === mb_strtolower(trim($subject->name))) {
                        DB::table('classroom_subjects')->where('subject_id', $subject->id)->update(['subject_id' => $kept[$key]['id']]);
                        DB::table('subjects')->where('id', $subject->id)->update(['deleted_at' => $now]);
                        $subjectId = $kept[$key]['id'];
                    } else {
                        $newCode = $this->uniqueCode($subject, $kept);
                        DB::table('subjects')->where('id', $subject->id)->update(['code' => $newCode]);
                        $kept[$subject->school_id.'|'.mb_strtolower($newCode)] = ['id' => $subject->id, 'name' => mb_strtolower(trim($subject->name))];
                    }
                } else {
                    $kept[$key] = ['id' => $subject->id, 'name' => mb_strtolower(trim($subject->name))];
                }
            }

            $exists = DB::table('course_subject')
                ->where('course_id', $subject->course_id)
                ->where('subject_id', $subjectId)
                ->exists();

            if (! $exists) {
                DB::table('course_subject')->insert([
                    'course_id' => $subject->course_id,
                    'subject_id' => $subjectId,
                    'suggested_term_number' => $subject->suggested_term_number,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Build a code that is free in the school by appending the course code.
     *
     * @param  array<string, array{id: int, name: string}>  $kept
     */
    private function uniqueCode(object $subject, array $kept): string
    {
        $courseCode = DB::table('courses')->where('id', $subject->course_id)->value('code');
        $candidate = "{$subject->code}-{$courseCode}";
        $suffix = 2;

        while (isset($kept[$subject->school_id.'|'.mb_strtolower($candidate)])) {
            $candidate = "{$subject->code}-{$courseCode}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
};
