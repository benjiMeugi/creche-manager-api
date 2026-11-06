<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Référentiel des liens de parenté (Père, Mère, Tuteur légal, Grand-parent, Oncle/Tante, etc.).
     */
    public function up(): void
    {
        Schema::create('relationship_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 'father', 'mother', 'legal_guardian', 'grandparent', 'uncle_aunt', 'other'
            $table->string('label'); // 'Père', 'Mère', 'Tuteur légal'
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('relationship_types');
    }
};
