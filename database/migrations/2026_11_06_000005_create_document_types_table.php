<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Types de pièces justificatives du dossier d'inscription (Section VII)
     * et documents du séjour (médias, ordonnances).
     */
    public function up(): void
    {
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // birth_certificate, vaccination_record, medical_certificate, id_photo, activity_photo, prescription, other
            $table->string('label');
            $table->boolean('is_mandatory_for_registration')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_types');
    }
};
