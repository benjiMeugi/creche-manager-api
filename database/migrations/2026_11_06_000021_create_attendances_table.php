<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pointages journaliers d'arrivée et de départ.
     * Utilise des `dateTime` pour les heures et sécurise juridiquement les retraits
     * via `picked_up_by_guardian_id` ou procuration tierce.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('child_id')->constrained('children')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('status')->default('present'); // present, absent, excused, late
            $table->dateTime('check_in_at')->nullable();
            $table->dateTime('check_out_at')->nullable();
            $table->foreignId('picked_up_by_guardian_id')->nullable()->constrained('guardians')->nullOnDelete();
            $table->string('third_party_pickup_name')->nullable();
            $table->string('third_party_id_card')->nullable();
            $table->string('dropped_off_by')->nullable();
            $table->unsignedInteger('overtime_minutes')->default(0);
            $table->unsignedInteger('overtime_hours_billed')->default(0);
            $table->unsignedBigInteger('overtime_amount')->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['child_id', 'attendance_date']);
            $table->index('attendance_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
