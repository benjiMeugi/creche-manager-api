<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Types d'événements du cahier de vie / séjour (Repas, Sieste, Hygiène, Santé, Activités, Fournitures).
     */
    public function up(): void
    {
        Schema::create('child_event_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // meal, nap, hygiene, health, medication, supply_request, activity, behavior, milestone, note
            $table->string('label');
            $table->string('default_severity')->default('info'); // info, attention, warning
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_event_types');
    }
};
