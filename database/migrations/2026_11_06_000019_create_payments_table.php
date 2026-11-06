<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table des encaissements réels (reçus de paiement).
     * Référence le mode de règlement normalisé via `payment_method_id`.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
            $table->foreignId('child_id')->nullable()->constrained('children')->nullOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('payment_method')->nullable(); // Code textuel rétrocompatible
            $table->string('receipt_number')->unique(); // ex: REC-2026-0001
            $table->unsignedBigInteger('amount'); // Montant total encaissé en FCFA
            $table->dateTime('paid_at'); // Date et heure précises du règlement
            $table->string('payment_reference')->nullable(); // Référence virement ou ID transaction Flooz/TMoney/Chèque
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by_user_id')->nullable();
            $table->timestamps();

            $table->index('paid_at');
            $table->index('receipt_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
