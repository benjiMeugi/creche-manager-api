<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grille tarifaire dynamique et configurable de la crèche :
     * Repas journalier (1 500 F), Taux horaire garderie du soir (2 000 F/h), Frais de dossier, etc.
     */
    public function up(): void
    {
        Schema::create('pricing_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // daily_meal, overtime_hourly, registration_fee_base, weekly_package_base
            $table->string('label'); // Libellé en clair
            $table->unsignedBigInteger('amount'); // Montant officiel en FCFA
            $table->string('currency')->default('XOF'); // Devise (FCFA / XOF)
            $table->string('billing_unit')->default('fixed'); // per_day, per_hour, fixed, per_week, per_year
            $table->date('effective_date')->nullable(); // Date de mise en application
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_configurations');
    }
};
