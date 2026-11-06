<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table maîtresse des enfants inscrits ou pré-inscrits.
     * Utilise `sex` ('M' / 'F') et ne stocke pas `section_id` en dur
     * (la section active est déduite de l'inscription annuelle `registrations.section_id`).
     */
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique(); // ex: 'ENF-2026-0001'
            $table->string('first_name');
            $table->string('last_name');
            $table->date('birth_date');
            $table->enum('sex', ['M', 'F']);
            $table->string('birth_place')->nullable();
            $table->text('dietary_habits')->nullable();
            $table->text('sleep_habits')->nullable();
            $table->text('other_useful_info')->nullable();
            $table->string('status')->default('pending'); // pending, active, suspended, archived
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['last_name', 'first_name']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
