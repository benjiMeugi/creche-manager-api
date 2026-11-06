<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Configuration des horaires d'ouverture et de fermeture réglementaires par jour de la semaine.
     * Sert au calcul automatisé des dépassements au-delà de l'heure légale (18h30).
     */
    public function up(): void
    {
        Schema::create('operating_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week')->unique(); // 1 (Lundi) à 7 (Dimanche) selon ISO-8601
            $table->string('day_name'); // Lundi, Mardi, Mercredi...
            $table->time('opening_time')->default('07:30:00'); // Heure d'ouverture officielle
            $table->time('closing_time')->default('18:30:00'); // Heure de fermeture officielle
            $table->unsignedInteger('overtime_grace_minutes')->default(0); // Période de grâce en minutes
            $table->boolean('is_open')->default(true); // Ouvert ou fermé ce jour-là
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operating_schedules');
    }
};
