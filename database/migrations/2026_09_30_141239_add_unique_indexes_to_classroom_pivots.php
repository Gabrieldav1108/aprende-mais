<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('classroom_subjects', function (Blueprint $table) {
            $table->unique(['classroom_id', 'subject_id']);
        });

        Schema::table('classroom_students', function (Blueprint $table) {
            $table->unique(['classroom_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('classroom_subjects', function (Blueprint $table) {
            $table->dropUnique(['classroom_id', 'subject_id']);
        });

        Schema::table('classroom_students', function (Blueprint $table) {
            $table->dropUnique(['classroom_id', 'student_id']);
        });
    }
};
