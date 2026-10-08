<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Role tables whose rows become the role (student, teacher) of a person.
     *
     * @var list<string>
     */
    private const ROLE_TABLES = ['students', 'teachers'];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach (self::ROLE_TABLES as $roleTable) {
            Schema::table($roleTable, function (Blueprint $table) {
                $table->foreignId('person_id')->nullable()->constrained('people');
            });

            $this->createPeopleFor($roleTable);

            Schema::table($roleTable, function (Blueprint $table) {
                $table->dropConstrainedForeignId('school_id');
                $table->dropConstrainedForeignId('user_id');
                $table->unsignedBigInteger('person_id')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (self::ROLE_TABLES as $roleTable) {
            Schema::table($roleTable, function (Blueprint $table) {
                $table->foreignId('school_id')->nullable()->constrained('schools');
                $table->foreignId('user_id')->nullable()->constrained('users');
            });

            foreach (DB::table($roleTable)->get() as $row) {
                $person = DB::table('people')->find($row->person_id);

                DB::table($roleTable)->where('id', $row->id)->update([
                    'school_id' => $person->school_id,
                    'user_id' => $person->user_id,
                ]);
            }

            Schema::table($roleTable, function (Blueprint $table) {
                $table->dropConstrainedForeignId('person_id');
            });
        }
    }

    /**
     * Give every existing row of the role table a person, reusing the person of a user that holds several roles.
     */
    private function createPeopleFor(string $roleTable): void
    {
        $fallbackSchoolId = DB::table('schools')->orderBy('id')->value('id');

        foreach (DB::table($roleTable)->get() as $row) {
            $user = DB::table('users')->find($row->user_id);
            $schoolId = $row->school_id ?? $user->school_id ?? $fallbackSchoolId;

            if ($schoolId === null) {
                throw new RuntimeException("Cannot create a person for {$roleTable} #{$row->id}: no school exists.");
            }

            $personId = DB::table('people')->where('user_id', $user->id)->value('id')
                ?? DB::table('people')->insertGetId([
                    'school_id' => $schoolId,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::table($roleTable)->where('id', $row->id)->update(['person_id' => $personId]);
        }
    }
};
