<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de ventilation / imputation comptable.
     * Associe une portion ou la totalité d'un paiement à une échéance ou à une charge.
     */
    public function up(): void
    {
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('tuition_installment_id')->nullable()->constrained('tuition_installments')->nullOnDelete();
            $table->foreignId('additional_charge_id')->nullable()->constrained('additional_charges')->nullOnDelete();
            $table->unsignedBigInteger('amount'); // Montant alloué à cette créance
            $table->timestamps();

            $table->index('payment_id');
            $table->index('tuition_installment_id');
            $table->index('additional_charge_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
    }
};
