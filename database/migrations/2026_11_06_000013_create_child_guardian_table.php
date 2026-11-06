<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table pivot N..N reliant les enfants à leurs parents / tuteurs.
     * Précise le rôle juridique, le contact principal, la facturation, l'urgence et le retrait légal.
     */
    public function up(): void
    {
        Schema::create('child_guardian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained('guardians')->cascadeOnDelete();
            $table->foreignId('relationship_type_id')->constrained('relationship_types')->restrictOnDelete();
            $table->boolean('is_primary_contact')->default(true); // Contact principal
            $table->boolean('is_billing_contact')->default(false); // Destinataire des factures
            $table->boolean('is_emergency_contact')->default(false); // Contact en cas d'urgence
            $table->boolean('is_authorized_pickup')->default(true); // Autorisé à venir chercher l'enfant
            $table->unsignedTinyInteger('emergency_priority')->default(1); // 1 = premier à appeler, 2 = second...
            $table->timestamps();

            $table->unique(['child_id', 'guardian_id']);
            $table->index(['child_id', 'is_authorized_pickup']);
            $table->index(['child_id', 'is_emergency_contact']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_guardian');
    }
};
