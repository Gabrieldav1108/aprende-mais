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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_subject_id')->constrained('classroom_subjects')->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users');
            $table->string('title', 150);
            $table->text('description');
            $table->timestamp('due_at')->nullable();
            $table->boolean('sent_by_email')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
