<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Référentiel des modes de paiement acceptés par la Crèche Petite Lune :
     * Espèces, Flooz (Moov Money), TMoney (Togocom), Virement bancaire, Chèque.
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // cash, flooz, tmoney, bank_transfer, check
            $table->string('label'); // Espèces, Flooz, TMoney, Virement bancaire, Chèque
            $table->boolean('requires_reference')->default(false); // Vrai si un numéro de transaction/chèque est obligatoire
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
