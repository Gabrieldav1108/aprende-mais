<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Existing subjects take the teacher of their first classroom offer.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->foreignId('teacher_id')->nullable()->constrained('teachers');
        });

        foreach (DB::table('classroom_subjects')->orderBy('id')->get(['subject_id', 'teacher_id']) as $offer) {
            DB::table('subjects')
                ->where('id', $offer->subject_id)
                ->whereNull('teacher_id')
                ->update(['teacher_id' => $offer->teacher_id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('teacher_id');
        });
    }
};
