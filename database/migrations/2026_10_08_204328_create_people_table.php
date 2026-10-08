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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();

            // Returned by BrasilAPI
            $table->char('zip_code', 8)->nullable();
            $table->string('street')->nullable();
            $table->string('neighborhood', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->char('state', 2)->nullable();
            $table->char('ibge_code', 7)->nullable();
            $table->char('ibge_state_code', 2)->nullable();

            // Filled in by the user
            $table->string('number', 20)->nullable();
            $table->string('complement', 100)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('zip_code');
            $table->index('ibge_code');
        });

        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools');
            $table->foreignId('user_id')->unique()->constrained('users');
            $table->foreignId('address_id')->nullable()->constrained('addresses');
            $table->string('name');
            $table->string('cpf_cnpj', 14)->nullable();
            $table->date('birth_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'name']);
            $table->index(['school_id', 'cpf_cnpj']);
        });

        Schema::create('phones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('number', 20);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('address');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
        Schema::dropIfExists('phones');
        Schema::dropIfExists('people');
        Schema::dropIfExists('addresses');
    }
};
