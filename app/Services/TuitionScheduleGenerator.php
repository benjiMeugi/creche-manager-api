<?php

namespace App\Services;

use App\Models\Registration;
use App\Models\Section;
use App\Models\TuitionInstallment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Service de génération de l'échéancier financier d'une inscription
 * à partir de la table normalisée `section_tranches` (Section VIII de la fiche Petite Lune) :
 * - Frais d'inscription : exigibles à l'inscription (50 000 FCFA par défaut)
 * - N tranches dynamiques définies dans `section_tranches` pour la section de l'enfant.
 */
class TuitionScheduleGenerator
{
    /**
     * Génère les lignes d'échéancier pour un dossier d'inscription s'il n'en possède pas encore.
     *
     * @return Collection<int, TuitionInstallment>
     */
    public function generateForRegistration(Registration $registration, ?int $userId = null): Collection
    {
        $existing = $registration->tuitionInstallments()->get();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $section = $registration->section ?? $registration->child?->section ?? Section::with('tranches')->orderBy('min_age_months')->first();
        if ($section) {
            $section->loadMissing('tranches');
        }

        $registrationFee = $section ? (int) $section->registration_fee : 50000;

        $baseDate = $registration->admission_date
            ? Carbon::parse($registration->admission_date)
            : now();

        $year = (int) $baseDate->format('Y');
        $aprilDue = Carbon::create($year, 4, 30);
        if ($aprilDue->lt($baseDate)) {
            $year++;
        }

        $created = collect();

        // 1. Frais d'inscription
        $created->push(TuitionInstallment::create([
            'registration_id' => $registration->id,
            'section_tranche_id' => null,
            'installment_code' => 'registration_fee',
            'label' => "Frais d'inscription (exigible à l'inscription)",
            'amount' => $registrationFee,
            'paid_amount' => 0,
            'due_date' => $baseDate->toDateString(),
            'status' => 'pending',
            'recorded_by_user_id' => $userId,
        ]));

        // 2. Tranches de scolarité dynamiques depuis la table `section_tranches`
        if ($section && $section->tranches->isNotEmpty()) {
            foreach ($section->tranches as $tranche) {
                $dueDate = $baseDate->toDateString();
                if (! $tranche->is_due_at_registration && $tranche->due_month && $tranche->due_day) {
                    $dueDate = Carbon::create($year, (int) $tranche->due_month, (int) $tranche->due_day)->toDateString();
                }

                $created->push(TuitionInstallment::create([
                    'registration_id' => $registration->id,
                    'section_tranche_id' => $tranche->id,
                    'installment_code' => $tranche->code,
                    'label' => $tranche->label,
                    'amount' => (int) $tranche->amount,
                    'paid_amount' => 0,
                    'due_date' => $dueDate,
                    'status' => 'pending',
                    'recorded_by_user_id' => $userId,
                ]));
            }
        }

        return $created;
    }
}
