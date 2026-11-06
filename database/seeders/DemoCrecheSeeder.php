<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\ChildEvent;
use App\Models\ChildEventType;
use App\Models\ChildGuardian;
use App\Models\ChildMedicalProfile;
use App\Models\Guardian;
use App\Models\Language;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentMethod;
use App\Models\Registration;
use App\Models\RelationshipType;
use App\Models\Section;
use App\Services\TuitionScheduleGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoCrecheSeeder extends Seeder
{
    /**
     * Crée des dossiers de démonstration complets sur le schéma normalisé (3NF).
     */
    public function run(): void
    {
        $babySection = Section::where('code', '3-12_months')->first();
        $toddlerSection = Section::where('code', '2-3_years')->first();

        $langFr = Language::where('code', 'fr')->first();
        $langEwe = Language::where('code', 'ewe')->first();

        $relFather = RelationshipType::where('code', 'father')->first();
        $relMother = RelationshipType::where('code', 'mother')->first();
        $relAunt = RelationshipType::where('code', 'uncle_aunt')->first();

        // 1. Création des parents (entités indépendantes permettant la gestion des fratries)
        $parentFather = Guardian::updateOrCreate(
            ['phone' => '+22890112233'],
            [
                'full_name' => 'Marc Kouassi',
                'profession' => 'Architecte',
                'email' => 'marc.kouassi@example.com',
                'address' => 'Quartier Résidentiel, Lomé',
            ]
        );

        $parentMother = Guardian::updateOrCreate(
            ['phone' => '+22891223344'],
            [
                'full_name' => 'Clarisse Kouassi',
                'profession' => 'Médecin',
                'email' => 'clarisse.kouassi@example.com',
                'address' => 'Quartier Résidentiel, Lomé',
            ]
        );

        $relativeAunt = Guardian::updateOrCreate(
            ['phone' => '+22892334455'],
            [
                'full_name' => 'Solange Mensah',
                'profession' => 'Commerçante',
                'email' => 'solange.mensah@example.com',
                'address' => 'Adidogomé, Lomé',
            ]
        );

        // 2. Premier enfant (Amina)
        $child1 = Child::updateOrCreate(
            ['registration_number' => 'ENF-2026-0001'],
            [
                'first_name' => 'Amina',
                'last_name' => 'Kouassi',
                'birth_date' => now()->subMonths(8)->toDateString(),
                'sex' => 'F',
                'birth_place' => 'Lomé',
                'dietary_habits' => 'Lait infantile 2ème âge (210 ml) + compote de fruits sans sucre ajouté.',
                'sleep_habits' => 'Sieste le matin (10h00-11h00) et après-midi (13h30-15h15) avec son doudou lapin.',
                'other_useful_info' => 'Enfant calme, adore les comptines.',
                'status' => 'active',
                'created_by_user_id' => 1,
            ]
        );

        if ($langFr && $langEwe) {
            $child1->languages()->sync([$langFr->id, $langEwe->id]);
        }

        // Rattachement des parents à Amina via le pivot child_guardian
        if ($relFather) {
            ChildGuardian::updateOrCreate(
                ['child_id' => $child1->id, 'guardian_id' => $parentFather->id],
                [
                    'relationship_type_id' => $relFather->id,
                    'is_primary_contact' => true,
                    'is_billing_contact' => true,
                    'is_emergency_contact' => false,
                    'is_authorized_pickup' => true,
                    'emergency_priority' => 1,
                ]
            );
        }

        if ($relMother) {
            ChildGuardian::updateOrCreate(
                ['child_id' => $child1->id, 'guardian_id' => $parentMother->id],
                [
                    'relationship_type_id' => $relMother->id,
                    'is_primary_contact' => true,
                    'is_billing_contact' => false,
                    'is_emergency_contact' => true,
                    'is_authorized_pickup' => true,
                    'emergency_priority' => 2,
                ]
            );
        }

        if ($relAunt) {
            ChildGuardian::updateOrCreate(
                ['child_id' => $child1->id, 'guardian_id' => $relativeAunt->id],
                [
                    'relationship_type_id' => $relAunt->id,
                    'is_primary_contact' => false,
                    'is_billing_contact' => false,
                    'is_emergency_contact' => true,
                    'is_authorized_pickup' => true,
                    'emergency_priority' => 3,
                ]
            );
        }

        ChildMedicalProfile::updateOrCreate(
            ['child_id' => $child1->id],
            [
                'doctor_name_contact' => 'Dr. Kossi Lawson (+228 90 55 66 77)',
                'vaccines_up_to_date' => true,
                'has_allergies' => false,
                'allergies_details' => null,
                'medical_conditions' => 'RAS',
                'has_ongoing_treatment' => false,
                'ongoing_treatment_details' => null,
                'blood_group' => 'O+',
            ]
        );

        $registration1 = Registration::updateOrCreate(
            ['registration_number' => 'PL-2026-0001'],
            [
                'academic_year' => '2026-2027',
                'child_id' => $child1->id,
                'section_id' => $babySection?->id,
                'status' => 'approved',
                'consent_first_aid' => true,
                'consent_photos_videos' => true,
                'consent_emergency_pickup' => true,
                'subscribe_daily_meal' => true,
                'subscribe_aftercare' => false,
                'subscribe_weekly_package' => false,
                'declarant_name' => 'Marc Kouassi',
                'signed_at_place' => 'Lomé',
                'signed_date' => now()->subDays(10)->toDateString(),
                'rules_accepted' => true,
                'admission_date' => now()->subDays(5)->toDateString(),
                'admin_observations' => 'Dossier complet et pièces vérifiées.',
                'decision_comment' => 'Admission validée en section Pouponnière.',
                'decision_datetime' => now()->subDays(5),
                'created_by_user_id' => 1,
                'processed_by_user_id' => 1,
            ]
        );

        $installments = (new TuitionScheduleGenerator())->generateForRegistration($registration1, 1);

        // Simulation d'un premier règlement (frais d'inscription réglés par le père)
        $firstInstallment = $installments->first();
        if ($firstInstallment && $firstInstallment->paid_amount === 0) {
            $methodFlooz = PaymentMethod::where('code', 'flooz')->first();

            $payment = Payment::create([
                'payer_guardian_id' => $parentFather->id,
                'child_id' => $child1->id,
                'payment_method_id' => $methodFlooz?->id,
                'receipt_number' => 'REC-2026-0001',
                'amount' => $firstInstallment->amount,
                'paid_at' => now()->subDays(5),
                'payment_reference' => 'FLOOZ-TXN-445566',
                'notes' => 'Règlement des frais d\'inscription par Flooz.',
                'recorded_by_user_id' => 1,
            ]);

            PaymentAllocation::create([
                'payment_id' => $payment->id,
                'tuition_installment_id' => $firstInstallment->id,
                'amount' => $firstInstallment->amount,
            ]);
        }

        // Pointage du jour avec dateTime natifs et clé étrangère picked_up_by_guardian_id
        $todayDate = now()->toDateString();
        Attendance::updateOrCreate(
            [
                'child_id' => $child1->id,
                'attendance_date' => $todayDate,
            ],
            [
                'status' => 'present',
                'check_in_at' => Carbon::parse($todayDate . ' 07:45:00'),
                'check_out_at' => Carbon::parse($todayDate . ' 18:15:00'),
                'dropped_off_by' => 'Clarisse Kouassi (Mère)',
                'picked_up_by_guardian_id' => $parentFather->id,
                'notes' => 'Bonne arrivée avec le sourire.',
                'recorded_by_user_id' => 1,
            ]
        );

        $typeMeal = ChildEventType::where('code', 'meal')->first();
        $typeNap = ChildEventType::where('code', 'nap')->first();
        $typeSupply = ChildEventType::where('code', 'supply_request')->first();

        // Événements du jour avec event_at en dateTime
        if ($typeMeal) {
            ChildEvent::updateOrCreate(
                [
                    'child_id' => $child1->id,
                    'child_event_type_id' => $typeMeal->id,
                    'title' => 'Repas de midi & Biberon',
                ],
                [
                    'severity' => 'info',
                    'description' => 'A tout mangé avec appétit (purée de carottes + biberon 180ml).',
                    'event_at' => Carbon::parse($todayDate . ' 12:15:00'),
                    'metadata' => [
                        'meal_type' => 'lunch',
                        'appetite' => 'finished',
                        'bottle_ml' => 180,
                    ],
                    'is_shared_with_parent' => true,
                    'recorded_by_user_id' => 1,
                ]
            );
        }

        if ($typeNap) {
            ChildEvent::updateOrCreate(
                [
                    'child_id' => $child1->id,
                    'child_event_type_id' => $typeNap->id,
                    'title' => "Sieste de l'après-midi",
                ],
                [
                    'severity' => 'info',
                    'description' => 'Sommeil paisible de 13h30 à 15h00.',
                    'event_at' => Carbon::parse($todayDate . ' 15:00:00'),
                    'metadata' => [
                        'start_time' => '13:30',
                        'end_time' => '15:00',
                        'duration_minutes' => 90,
                        'quality' => 'calm',
                    ],
                    'is_shared_with_parent' => true,
                    'recorded_by_user_id' => 1,
                ]
            );
        }

        if ($typeSupply) {
            ChildEvent::updateOrCreate(
                [
                    'child_id' => $child1->id,
                    'child_event_type_id' => $typeSupply->id,
                    'title' => 'Réapprovisionnement couches',
                ],
                [
                    'severity' => 'attention',
                    'description' => 'Il ne reste que 3 couches dans le casier. Merci de prévoir un nouveau paquet demain.',
                    'event_at' => Carbon::parse($todayDate . ' 16:30:00'),
                    'metadata' => [
                        'items' => ['couches'],
                        'supply_status' => 'requested',
                    ],
                    'is_shared_with_parent' => true,
                    'recorded_by_user_id' => 1,
                ]
            );
        }

        // 3. Deuxième enfant (Kofi Kouassi, grand frère d'Amina partageant les mêmes parents !)
        $child2 = Child::updateOrCreate(
            ['registration_number' => 'ENF-2026-0002'],
            [
                'first_name' => 'Kofi',
                'last_name' => 'Kouassi',
                'birth_date' => now()->subMonths(28)->toDateString(),
                'sex' => 'M',
                'birth_place' => 'Lomé',
                'dietary_habits' => 'Repas normal varié, pas d\'allergie.',
                'sleep_habits' => 'Sieste de 13h00 à 14h30.',
                'other_useful_info' => 'Acquisition de la propreté acquise.',
                'status' => 'active',
                'created_by_user_id' => 1,
            ]
        );

        if ($langFr) {
            $child2->languages()->sync([$langFr->id]);
        }

        // Même père et même mère réutilisés sans duplication de contact !
        if ($relFather) {
            ChildGuardian::updateOrCreate(
                ['child_id' => $child2->id, 'guardian_id' => $parentFather->id],
                [
                    'relationship_type_id' => $relFather->id,
                    'is_primary_contact' => true,
                    'is_billing_contact' => true,
                    'is_emergency_contact' => false,
                    'is_authorized_pickup' => true,
                    'emergency_priority' => 1,
                ]
            );
        }

        if ($relMother) {
            ChildGuardian::updateOrCreate(
                ['child_id' => $child2->id, 'guardian_id' => $parentMother->id],
                [
                    'relationship_type_id' => $relMother->id,
                    'is_primary_contact' => true,
                    'is_billing_contact' => false,
                    'is_emergency_contact' => true,
                    'is_authorized_pickup' => true,
                    'emergency_priority' => 2,
                ]
            );
        }

        ChildMedicalProfile::updateOrCreate(
            ['child_id' => $child2->id],
            [
                'doctor_name_contact' => 'Dr. Kossi Lawson (+228 90 55 66 77)',
                'vaccines_up_to_date' => true,
                'has_allergies' => false,
                'allergies_details' => null,
                'medical_conditions' => null,
                'has_ongoing_treatment' => false,
                'ongoing_treatment_details' => null,
                'blood_group' => 'A+',
            ]
        );

        Registration::updateOrCreate(
            ['registration_number' => 'PL-2026-0002'],
            [
                'academic_year' => '2026-2027',
                'child_id' => $child2->id,
                'section_id' => $toddlerSection?->id,
                'status' => 'approved',
                'consent_first_aid' => true,
                'consent_photos_videos' => true,
                'consent_emergency_pickup' => true,
                'subscribe_daily_meal' => true,
                'subscribe_aftercare' => true,
                'subscribe_weekly_package' => false,
                'declarant_name' => 'Marc Kouassi',
                'signed_at_place' => 'Lomé',
                'signed_date' => now()->toDateString(),
                'rules_accepted' => true,
                'admission_date' => now()->toDateString(),
                'created_by_user_id' => 1,
                'processed_by_user_id' => 1,
            ]
        );

        (new TuitionScheduleGenerator())->generateForRegistration(
            Registration::where('registration_number', 'PL-2026-0002')->first(),
            1
        );
    }
}
