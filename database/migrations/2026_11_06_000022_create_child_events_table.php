<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cahier de vie / Timeline du séjour de l'enfant.
     * Utilise `event_at` en `dateTime` natif.
     */
    public function up(): void
    {
        Schema::create('child_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('child_event_type_id')->constrained('child_event_types')->restrictOnDelete();
            $table->string('severity')->default('info'); // info, attention, warning
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('event_at'); // Date et heure de l'événement
            $table->json('metadata')->nullable();
            $table->boolean('is_shared_with_parent')->default(true);
            $table->dateTime('supply_fulfilled_at')->nullable();
            $table->unsignedBigInteger('recorded_by_user_id')->nullable();
            $table->timestamps();

            $table->index(['child_id', 'event_at']);
            $table->index(['child_event_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_events');
    }
};
