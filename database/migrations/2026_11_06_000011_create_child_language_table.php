<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table de jointure N..N entre enfants et langues parlées à la maison (Section I).
     */
    public function up(): void
    {
        Schema::create('child_language', function (Blueprint $table) {
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['child_id', 'language_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_language');
    }
};
