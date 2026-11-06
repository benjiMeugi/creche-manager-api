<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tranches de scolarité par section (Section VIII du formulaire d'inscription).
     * 4 tranches trimestrielles par section.
     */
    public function up(): void
    {
        Schema::create('section_tranches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('sections')->cascadeOnDelete();
            $table->unsignedTinyInteger('tranche_number'); // 1, 2, 3, 4
            $table->string('code'); // ex: 'tranche_1', 'tranche_2'
            $table->string('label'); // ex: '1ère tranche (exigible à l'inscription)'
            $table->unsignedBigInteger('amount'); // Montant officiel en FCFA
            $table->boolean('is_due_at_registration')->default(false);
            $table->unsignedTinyInteger('due_month')->nullable(); // 4 (Avril), 7 (Juillet), 10 (Octobre)
            $table->unsignedTinyInteger('due_day')->nullable(); // 30, 31
            $table->timestamps();

            $table->unique(['section_id', 'tranche_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('section_tranches');
    }
};
