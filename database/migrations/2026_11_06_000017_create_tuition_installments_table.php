<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Échéancier de facturation de la scolarité (dettes uniquement).
     * Les règlements financiers effectifs sont gérés par `payments` et `payment_allocations`.
     */
    public function up(): void
    {
        Schema::create('tuition_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('registrations')->cascadeOnDelete();
            $table->foreignId('section_tranche_id')->nullable()->constrained('section_tranches')->nullOnDelete();
            $table->string('installment_code'); // registration_fee, tranche_1, tranche_2, tranche_3, tranche_4
            $table->string('label');
            $table->unsignedBigInteger('amount'); // Montant dû en FCFA
            $table->unsignedBigInteger('paid_amount')->default(0); // Montant cumulé réglé via allocations
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending, partial, paid, overdue
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['registration_id', 'status']);
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuition_installments');
    }
};
