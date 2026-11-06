<?php

namespace Database\Seeders;

use App\Models\ChildEventType;
use App\Models\DocumentType;
use App\Models\Language;
use App\Models\OperatingSchedule;
use App\Models\PaymentMethod;
use App\Models\PricingConfiguration;
use App\Models\RelationshipType;
use App\Models\Section;
use App\Models\SectionTranche;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Initialise les référentiels normalisés et configurations de la Crèche Petite Lune :
     * - Les 4 sections d'âge et leurs tranches de scolarité (`sections` & `section_tranches`)
     * - Les langues parlées à la maison (`languages`)
     * - Les types de liens de parenté (`relationship_types`)
     * - Les types de documents justificatifs (`document_types`)
     * - Les types d'événements du séjour (`child_event_types`)
     * - Les modes de paiement acceptés (`payment_methods`)
     * - La grille tarifaire configurable (`pricing_configurations`)
     * - Les horaires d'ouverture et de fermeture réglementaires (`operating_schedules`)
     */
    public function run(): void
    {
        $sections = [
            [
                'code' => '3-12_months',
                'label' => '3–12 mois',
                'min_age_months' => 3,
                'max_age_months' => 12,
                'registration_fee' => 50000,
                'total_tuition' => 1800000,
                'daily_meal_fee' => 1500,
                'hourly_aftercare_fee' => 2000,
                'weekly_rate' => 50000,
                'capacity' => 15,
                'description' => 'Section Pouponnière / Bébés (de 3 à 12 mois)',
                'tranche_amount' => 450000,
            ],
            [
                'code' => '1-2_years',
                'label' => '1–2 ans',
                'min_age_months' => 12,
                'max_age_months' => 24,
                'registration_fee' => 50000,
                'total_tuition' => 1560000,
                'daily_meal_fee' => 1500,
                'hourly_aftercare_fee' => 2000,
                'weekly_rate' => 40000,
                'capacity' => 18,
                'description' => 'Section Petits Marcheurs (de 1 à 2 ans)',
                'tranche_amount' => 390000,
            ],
            [
                'code' => '2-3_years',
                'label' => '2–3 ans',
                'min_age_months' => 24,
                'max_age_months' => 36,
                'registration_fee' => 50000,
                'total_tuition' => 1500000,
                'daily_meal_fee' => 1500,
                'hourly_aftercare_fee' => 2000,
                'weekly_rate' => 35000,
                'capacity' => 20,
                'description' => 'Section Moyens (de 2 à 3 ans)',
                'tranche_amount' => 375000,
            ],
            [
                'code' => '3-4_years',
                'label' => '3–4 ans',
                'min_age_months' => 36,
                'max_age_months' => 48,
                'registration_fee' => 50000,
                'total_tuition' => 1440000,
                'daily_meal_fee' => 1500,
                'hourly_aftercare_fee' => 2000,
                'weekly_rate' => 35000,
                'capacity' => 22,
                'description' => 'Section Grands / Pré-maternelle (de 3 à 4 ans)',
                'tranche_amount' => 360000,
            ],
        ];

        foreach ($sections as $sectionData) {
            $trancheAmount = $sectionData['tranche_amount'];
            unset($sectionData['tranche_amount']);

            $section = Section::updateOrCreate(
                ['code' => $sectionData['code']],
                $sectionData
            );

            $tranches = [
                [
                    'tranche_number' => 1,
                    'code' => 'tranche_1',
                    'label' => "Première tranche (exigible à l'inscription)",
                    'amount' => $trancheAmount,
                    'is_due_at_registration' => true,
                    'due_month' => null,
                    'due_day' => null,
                ],
                [
                    'tranche_number' => 2,
                    'code' => 'tranche_2',
                    'label' => 'Deuxième tranche (30 Avril)',
                    'amount' => $trancheAmount,
                    'is_due_at_registration' => false,
                    'due_month' => 4,
                    'due_day' => 30,
                ],
                [
                    'tranche_number' => 3,
                    'code' => 'tranche_3',
                    'label' => 'Troisième tranche (31 Juillet)',
                    'amount' => $trancheAmount,
                    'is_due_at_registration' => false,
                    'due_month' => 7,
                    'due_day' => 31,
                ],
                [
                    'tranche_number' => 4,
                    'code' => 'tranche_4',
                    'label' => 'Quatrième tranche (31 Octobre)',
                    'amount' => $trancheAmount,
                    'is_due_at_registration' => false,
                    'due_month' => 10,
                    'due_day' => 31,
                ],
            ];

            foreach ($tranches as $tranche) {
                SectionTranche::updateOrCreate(
                    [
                        'section_id' => $section->id,
                        'tranche_number' => $tranche['tranche_number'],
                    ],
                    $tranche
                );
            }
        }

        // Référentiel des langues parlées à la maison (Section I)
        $languages = [
            ['code' => 'fr', 'label' => 'Français'],
            ['code' => 'en', 'label' => 'Anglais'],
            ['code' => 'ewe', 'label' => 'Éwé'],
            ['code' => 'kye', 'label' => 'Kabyè'],
            ['code' => 'dua', 'label' => 'Douala'],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(['code' => $language['code']], $language);
        }

        // Référentiel des liens de parenté / relation avec l'enfant (Sections II & III)
        $relationshipTypes = [
            ['code' => 'father', 'label' => 'Père', 'description' => 'Père biologique ou légal'],
            ['code' => 'mother', 'label' => 'Mère', 'description' => 'Mère biologique ou légale'],
            ['code' => 'legal_guardian', 'label' => 'Tuteur légal', 'description' => 'Représentant légal désigné'],
            ['code' => 'grandparent', 'label' => 'Grand-parent', 'description' => 'Grand-père ou grand-mère'],
            ['code' => 'uncle_aunt', 'label' => 'Oncle / Tante', 'description' => 'Oncle ou tante de l’enfant'],
            ['code' => 'sibling', 'label' => 'Frère / Sœur', 'description' => 'Frère majeur ou sœur majeure'],
            ['code' => 'other', 'label' => 'Autre proche', 'description' => 'Proche autorisé ou personne de confiance'],
        ];

        foreach ($relationshipTypes as $relationshipType) {
            RelationshipType::updateOrCreate(['code' => $relationshipType['code']], $relationshipType);
        }

        // Référentiel des types de documents (Section VII + médias)
        $documentTypes = [
            [
                'code' => 'birth_certificate',
                'label' => "Photocopie de l'acte de naissance",
                'is_mandatory_for_registration' => true,
                'description' => 'Pièce obligatoire du dossier d’inscription (Section VII).',
            ],
            [
                'code' => 'vaccination_record',
                'label' => 'Photocopie du carnet de vaccination',
                'is_mandatory_for_registration' => true,
                'description' => 'Pièce obligatoire attestant que les vaccins sont à jour (Section VII).',
            ],
            [
                'code' => 'medical_certificate',
                'label' => "Certificat médical d'aptitude à la vie en collectivité",
                'is_mandatory_for_registration' => true,
                'description' => 'Certificat médical délivré par un médecin (Section VII).',
            ],
            [
                'code' => 'id_photo',
                'label' => "Deux photos d'identité",
                'is_mandatory_for_registration' => true,
                'description' => 'Photos d’identité récentes de l’enfant (Section VII).',
            ],
            [
                'code' => 'activity_photo',
                'label' => "Photo ou vidéo d'activité pédagogique",
                'is_mandatory_for_registration' => false,
                'description' => 'Média partagé dans le cahier de vie de l’enfant.',
            ],
            [
                'code' => 'prescription',
                'label' => 'Ordonnance médicale',
                'is_mandatory_for_registration' => false,
                'description' => 'Ordonnance jointe pour un traitement en cours à la crèche.',
            ],
            [
                'code' => 'other',
                'label' => 'Autre document',
                'is_mandatory_for_registration' => false,
                'description' => 'Document complémentaire.',
            ],
        ];

        foreach ($documentTypes as $documentType) {
            DocumentType::updateOrCreate(['code' => $documentType['code']], $documentType);
        }

        // Référentiel des types d'événements du séjour (Cahier de vie / Timeline)
        $eventTypes = [
            ['code' => 'meal', 'label' => 'Repas & Biberon', 'default_severity' => 'info', 'icon' => 'utensils', 'description' => 'Déjeuner, goûter ou biberon'],
            ['code' => 'nap', 'label' => 'Sieste & Sommeil', 'default_severity' => 'info', 'icon' => 'bed', 'description' => 'Temps de repos et sommeil'],
            ['code' => 'hygiene', 'label' => 'Hygiène & Change', 'default_severity' => 'info', 'icon' => 'shower', 'description' => 'Change de couche, passage aux toilettes'],
            ['code' => 'health', 'label' => 'Santé & Petit bobo', 'default_severity' => 'attention', 'icon' => 'heart-pulse', 'description' => 'Prise de température, égratignure, soins'],
            ['code' => 'medication', 'label' => 'Administration Médicament', 'default_severity' => 'attention', 'icon' => 'pills', 'description' => 'Prise de traitement sur ordonnance'],
            ['code' => 'supply_request', 'label' => 'Demande de fournitures', 'default_severity' => 'attention', 'icon' => 'box', 'description' => 'Couches, lingettes, tenue de rechange'],
            ['code' => 'activity', 'label' => 'Activité d’éveil & Atelier', 'default_severity' => 'info', 'icon' => 'palette', 'description' => 'Peinture, motricité, comptines'],
            ['code' => 'behavior', 'label' => 'Humeur & Comportement', 'default_severity' => 'info', 'icon' => 'smile', 'description' => 'Humeur générale, interactions'],
            ['code' => 'milestone', 'label' => 'Étape & Progrès marquant', 'default_severity' => 'info', 'icon' => 'star', 'description' => 'Premiers pas, motricité fine'],
            ['code' => 'note', 'label' => 'Note & Observation générale', 'default_severity' => 'info', 'icon' => 'note-sticky', 'description' => 'Message de l’éducatrice'],
        ];

        foreach ($eventTypes as $eventType) {
            ChildEventType::updateOrCreate(['code' => $eventType['code']], $eventType);
        }

        // 1. Référentiel des modes de paiement (payment_methods)
        $paymentMethods = [
            [
                'code' => 'cash',
                'label' => 'Espèces',
                'requires_reference' => false,
                'is_active' => true,
                'description' => 'Règlement en numéraire au secrétariat de la crèche.',
            ],
            [
                'code' => 'flooz',
                'label' => 'Flooz (Moov Money)',
                'requires_reference' => true,
                'is_active' => true,
                'description' => 'Paiement mobile Moov Money Togo avec ID de transaction.',
            ],
            [
                'code' => 'tmoney',
                'label' => 'TMoney (Togocom)',
                'requires_reference' => true,
                'is_active' => true,
                'description' => 'Paiement mobile Togocom avec référence de transaction.',
            ],
            [
                'code' => 'bank_transfer',
                'label' => 'Virement bancaire',
                'requires_reference' => true,
                'is_active' => true,
                'description' => 'Virement sur le compte bancaire de la crèche.',
            ],
            [
                'code' => 'check',
                'label' => 'Chèque bancaire',
                'requires_reference' => true,
                'is_active' => true,
                'description' => 'Chèque certifié à l’ordre de la Crèche Petite Lune.',
            ],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::updateOrCreate(['code' => $paymentMethod['code']], $paymentMethod);
        }

        // 2. Grille tarifaire dynamique (pricing_configurations)
        $pricingConfigs = [
            [
                'code' => 'daily_meal',
                'label' => 'Repas journalier de midi',
                'amount' => 1500,
                'currency' => 'XOF',
                'billing_unit' => 'per_day',
                'effective_date' => '2026-01-01',
                'is_active' => true,
                'description' => 'Tarif forfaitaire d’un repas équilibré préparé à la crèche (1 500 FCFA / jour).',
            ],
            [
                'code' => 'overtime_hourly',
                'label' => 'Garderie du soir (dépassement après 18h30)',
                'amount' => 2000,
                'currency' => 'XOF',
                'billing_unit' => 'per_hour',
                'effective_date' => '2026-01-01',
                'is_active' => true,
                'description' => 'Facturation de 2 000 FCFA par heure entamée au-delà de 18h30 (Section IX).',
            ],
            [
                'code' => 'registration_fee_base',
                'label' => 'Frais d’inscription socle',
                'amount' => 50000,
                'currency' => 'XOF',
                'billing_unit' => 'fixed',
                'effective_date' => '2026-01-01',
                'is_active' => true,
                'description' => 'Frais de dossier et inscription exigibles à l’admission.',
            ],
            [
                'code' => 'weekly_package_3_12m',
                'label' => 'Forfait semaine — Section 3–12 mois',
                'amount' => 50000,
                'currency' => 'XOF',
                'billing_unit' => 'per_week',
                'effective_date' => '2026-01-01',
                'is_active' => true,
                'description' => 'Forfait d’accueil hebdomadaire pour les nourrissons.',
            ],
            [
                'code' => 'weekly_package_1_2y',
                'label' => 'Forfait semaine — Section 1–2 ans',
                'amount' => 40000,
                'currency' => 'XOF',
                'billing_unit' => 'per_week',
                'effective_date' => '2026-01-01',
                'is_active' => true,
                'description' => 'Forfait d’accueil hebdomadaire pour les petits marcheurs.',
            ],
            [
                'code' => 'weekly_package_2_4y',
                'label' => 'Forfait semaine — Sections 2–4 ans',
                'amount' => 35000,
                'currency' => 'XOF',
                'billing_unit' => 'per_week',
                'effective_date' => '2026-01-01',
                'is_active' => true,
                'description' => 'Forfait d’accueil hebdomadaire pour les moyens et grands.',
            ],
        ];

        foreach ($pricingConfigs as $config) {
            PricingConfiguration::updateOrCreate(['code' => $config['code']], $config);
        }

        // 3. Horaires réglementaires d'ouverture / fermeture (operating_schedules)
        $schedules = [
            ['day_of_week' => 1, 'day_name' => 'Lundi', 'opening_time' => '07:30:00', 'closing_time' => '18:30:00', 'overtime_grace_minutes' => 0, 'is_open' => true, 'description' => 'Journée d’accueil réglementaire'],
            ['day_of_week' => 2, 'day_name' => 'Mardi', 'opening_time' => '07:30:00', 'closing_time' => '18:30:00', 'overtime_grace_minutes' => 0, 'is_open' => true, 'description' => 'Journée d’accueil réglementaire'],
            ['day_of_week' => 3, 'day_name' => 'Mercredi', 'opening_time' => '07:30:00', 'closing_time' => '18:30:00', 'overtime_grace_minutes' => 0, 'is_open' => true, 'description' => 'Journée d’accueil réglementaire'],
            ['day_of_week' => 4, 'day_name' => 'Jeudi', 'opening_time' => '07:30:00', 'closing_time' => '18:30:00', 'overtime_grace_minutes' => 0, 'is_open' => true, 'description' => 'Journée d’accueil réglementaire'],
            ['day_of_week' => 5, 'day_name' => 'Vendredi', 'opening_time' => '07:30:00', 'closing_time' => '18:30:00', 'overtime_grace_minutes' => 0, 'is_open' => true, 'description' => 'Journée d’accueil réglementaire'],
            ['day_of_week' => 6, 'day_name' => 'Samedi', 'opening_time' => '08:00:00', 'closing_time' => '13:00:00', 'overtime_grace_minutes' => 0, 'is_open' => false, 'description' => 'Fermé le week-end (accueil sur dérogation spéciale)'],
            ['day_of_week' => 7, 'day_name' => 'Dimanche', 'opening_time' => '08:00:00', 'closing_time' => '13:00:00', 'overtime_grace_minutes' => 0, 'is_open' => false, 'description' => 'Fermeture hebdomadaire'],
        ];

        foreach ($schedules as $schedule) {
            OperatingSchedule::updateOrCreate(['day_of_week' => $schedule['day_of_week']], $schedule);
        }
    }
}
