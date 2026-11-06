<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renseignements médicaux confidentiels de l'enfant (Section V du formulaire).
     * Relation 1..1 stricte avec `children`.
     */
    public function up(): void
    {
        Schema::create('child_medical_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->unique()->constrained('children')->cascadeOnDelete();
            $table->string('doctor_name_contact')->nullable();
            $table->boolean('vaccines_up_to_date')->default(false);
            $table->boolean('has_allergies')->default(false);
            $table->text('allergies_details')->nullable();
            $table->text('medical_conditions')->nullable();
            $table->boolean('has_ongoing_treatment')->default(false);
            $table->text('ongoing_treatment_details')->nullable();
            $table->string('blood_group')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_medical_profiles');
    }
};
