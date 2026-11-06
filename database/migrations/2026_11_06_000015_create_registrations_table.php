<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dossier d'inscription officiel (Sections VI, IX, X, XI du formulaire PDF).
     * Porte la clé étrangère active `section_id` pour chaque année scolaire.
     */
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique(); // ex: 'PL-2026-0001'
            $table->string('academic_year'); // ex: '2026-2027'
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->string('status')->default('draft'); // draft, submitted, under_review, approved, rejected
            $table->boolean('consent_first_aid')->default(false);
            $table->boolean('consent_photos_videos')->default(false);
            $table->boolean('consent_emergency_pickup')->default(false);
            $table->boolean('subscribe_daily_meal')->default(false);
            $table->boolean('subscribe_aftercare')->default(false);
            $table->boolean('subscribe_weekly_package')->default(false);
            $table->string('declarant_name')->nullable();
            $table->string('signed_at_place')->default('Lomé');
            $table->date('signed_date')->nullable();
            $table->boolean('rules_accepted')->default(false);
            $table->date('admission_date')->nullable();
            $table->text('admin_observations')->nullable();
            $table->text('decision_comment')->nullable();
            $table->dateTime('decision_datetime')->nullable();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->unsignedBigInteger('processed_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'academic_year']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};
