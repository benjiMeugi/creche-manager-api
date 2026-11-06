<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grille des 4 sections d'âge officielles de la Crèche Petite Lune :
     * 3-12 mois, 1-2 ans, 2-3 ans, 3-4 ans.
     */
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ex: '3-12_months', '1-2_years', '2-3_years', '3-4_years'
            $table->string('label'); // ex: '3–12 mois (Pouponnière)'
            $table->unsignedInteger('min_age_months');
            $table->unsignedInteger('max_age_months');
            $table->unsignedBigInteger('registration_fee')->default(50000); // Frais d'inscription en FCFA
            $table->unsignedBigInteger('total_tuition'); // Frais de scolarité annuels globaux en FCFA
            $table->unsignedBigInteger('daily_meal_fee')->default(1500); // 1 500 FCFA / jour
            $table->unsignedBigInteger('hourly_aftercare_fee')->default(2000); // 2 000 FCFA / heure après 18h30
            $table->unsignedBigInteger('weekly_rate')->nullable(); // Forfait semaine (50k, 40k, 35k)
            $table->unsignedInteger('capacity')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
