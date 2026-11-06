<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\ChildEventType;
use App\Models\ChildGuardian;
use App\Models\DocumentType;
use App\Models\Guardian;
use App\Models\Language;
use App\Models\OperatingSchedule;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\PaymentMethod;
use App\Models\PricingConfiguration;
use App\Models\RelationshipType;
use App\Models\TuitionInstallment;
use Database\Seeders\DemoCrecheSeeder;
use Database\Seeders\SectionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrecheManagerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SectionSeeder::class);
    }

    /**
     * Simule la validation du token auprès de authentication-api.
     */
    private function fakeAuthApi(array $permissions = ['*'], array $roles = ['admin'], int $userId = 10): array
    {
        Http::fake([
            '*/api/validate-token' => Http::response([
                'success' => true,
                'code' => 200,
                'message' => 'Token valide.',
                'body' => [
                    'error_message' => null,
                    'errors' => null,
                    'response_data' => [
                        'id' => $userId,
                        'username' => 'educatrice.petitelune',
                        'email' => 'educatrice@petitelune.tg',
                        'roles' => $roles,
                        'permissions' => $permissions,
                    ],
                ],
            ], 200),
        ]);

        return [
            'Authorization' => 'Bearer fake-valid-jwt-token',
            'Accept' => 'application/json',
        ];
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/section');

        $response->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 401);
    }

    public function test_unauthorized_ability_returns_403(): void
    {
        $headers = $this->fakeAuthApi(['section-list'], ['staff']);

        // L'employé n'a que 'section-list', pas 'section-create'
        $response = $this->postJson('/api/section', [
            'code' => 'custom',
            'label' => 'Custom',
        ], $headers);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 403);
    }

    public function test_permissions_endpoint_returns_authenticated_employee_rights(): void
    {
        $headers = $this->fakeAuthApi(['child-list', 'child_event-create'], ['educatrice'], 42);

        $response = $this->getJson('/api/permissions', $headers);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('body.response_data.user.id', 42)
            ->assertJsonPath('body.response_data.roles.0', 'educatrice')
            ->assertJsonPath('body.response_data.permissions.0', 'child-list');
    }

    public function test_sections_and_normalized_tranches_seeded_with_official_pdf_pricing(): void
    {
        $headers = $this->fakeAuthApi(['section-list', 'section_tranche-list']);

        $queryPayload = json_encode([
            'where' => ['code' => '3-12_months'],
        ]);

        $response = $this->getJson('/api/section?query_=' . urlencode($queryPayload), $headers);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('body.response_data.0.code', '3-12_months')
            ->assertJsonPath('body.response_data.0.registration_fee', 50000)
            ->assertJsonPath('body.response_data.0.total_tuition', 1800000)
            ->assertJsonPath('body.response_data.0.weekly_rate', 50000);

        $sectionId = $response->json('body.response_data.0.id');

        $trancheQuery = json_encode([
            'where' => ['section_id' => $sectionId],
        ]);
        $tranchesResponse = $this->getJson('/api/section_tranche?query_=' . urlencode($trancheQuery), $headers);
        $tranchesResponse->assertStatus(200)
            ->assertJsonCount(4, 'body.response_data')
            ->assertJsonPath('body.response_data.0.amount', 450000);
    }

    public function test_full_child_registration_workflow_with_normalized_relations_and_installments_generation(): void
    {
        $headers = $this->fakeAuthApi([
            'child-create',
            'child-show',
            'guardian-create',
            'child_guardian-create',
            'attachment-create',
            'attachment-download',
            'registration-approve',
            'tuition_installment-pay',
        ]);

        $langFr = Language::where('code', 'fr')->firstOrFail();
        $langEwe = Language::where('code', 'ewe')->firstOrFail();

        $relFather = RelationshipType::where('code', 'father')->firstOrFail();
        $relMother = RelationshipType::where('code', 'mother')->firstOrFail();

        $birthCertType = DocumentType::where('code', 'birth_certificate')->firstOrFail();

        // 1. Création des parents indépendants (réutilisables pour les frères et sœurs)
        $parentFather = Guardian::create([
            'full_name' => 'Kodjo Mensah',
            'profession' => 'Ingénieur',
            'phone' => '+22890001122',
            'address' => 'Tokoin, Lomé',
        ]);

        $parentMother = Guardian::create([
            'full_name' => 'Afi Mensah',
            'profession' => 'Pharmacienne',
            'phone' => '+22891003344',
            'address' => 'Tokoin, Lomé',
        ]);

        // 2. Création de l'enfant avec sex, langues (N..N), profil médical et inscription
        $createResponse = $this->postJson('/api/child', [
            'first_name' => 'Koffi',
            'last_name' => 'Mensah',
            'birth_date' => now()->subMonths(6)->toDateString(),
            'sex' => 'M',
            'birth_place' => 'Lomé',
            'language_ids' => [$langFr->id, $langEwe->id],
            'dietary_habits' => 'Biberon 180ml toutes les 3h',
            'sleep_habits' => 'Dort avec veilleuse',
            'other_useful_info' => 'Calme et souriant',
            'medicalProfile' => [
                'doctor_name_contact' => 'Dr. Agbeko (+228 90 11 22 33)',
                'vaccines_up_to_date' => true,
                'has_allergies' => false,
                'has_ongoing_treatment' => false,
            ],
            'registrations' => [
                [
                    'academic_year' => '2026-2027',
                    'consent_first_aid' => true,
                    'consent_photos_videos' => true,
                    'consent_emergency_pickup' => true,
                    'subscribe_daily_meal' => true,
                    'declarant_name' => 'Kodjo Mensah',
                    'signed_at_place' => 'Lomé',
                ],
            ],
        ], $headers);

        $createResponse->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('body.response_data.sex', 'M');

        $childId = $createResponse->json('body.response_data.id');
        $this->assertNotNull($childId);

        // Rattachement des parents via le pivot child_guardian
        ChildGuardian::create([
            'child_id' => $childId,
            'guardian_id' => $parentFather->id,
            'relationship_type_id' => $relFather->id,
            'is_primary_contact' => true,
            'is_billing_contact' => true,
            'is_authorized_pickup' => true,
        ]);

        ChildGuardian::create([
            'child_id' => $childId,
            'guardian_id' => $parentMother->id,
            'relationship_type_id' => $relMother->id,
            'is_primary_contact' => true,
            'is_emergency_contact' => true,
            'is_authorized_pickup' => true,
        ]);

        // 3. Vérification du dossier consolidé
        $dossierResponse = $this->getJson("/api/child/{$childId}/dossier", $headers);
        $dossierResponse->assertStatus(200)
            ->assertJsonCount(2, 'body.response_data.languages')
            ->assertJsonCount(2, 'body.response_data.child_guardians')
            ->assertJsonPath('body.response_data.medical_profile.vaccines_up_to_date', true);

        $registrationId = $dossierResponse->json('body.response_data.registrations.0.id');
        $this->assertNotNull($registrationId);

        // 4. Upload d'une pièce justificative avec document_type_id et file_path
        Storage::fake('local');
        $file = UploadedFile::fake()->create('acte-naissance-koffi.pdf', 250, 'application/pdf');

        $uploadResponse = $this->postJson("/api/attachment/registration/{$registrationId}", [
            'file' => $file,
            'document_type_id' => $birthCertType->id,
        ], $headers);

        $uploadResponse->assertStatus(200)
            ->assertJsonPath('body.response_data.document_type_id', $birthCertType->id)
            ->assertJsonPath('body.response_data.document_type.code', 'birth_certificate')
            ->assertJsonPath('body.response_data.attachable_type', 'registration');

        $this->assertNotEmpty($uploadResponse->json('body.response_data.file_path'));

        // 5. Approbation administrative du dossier d'inscription
        $approveResponse = $this->putJson("/api/registration/{$registrationId}/approve", [
            'admission_date' => '2026-11-10',
            'admin_observations' => 'Toutes les pièces sont conformes.',
            'decision_comment' => 'Admis en section 3-12 mois.',
        ], $headers);

        $approveResponse->assertStatus(200)
            ->assertJsonPath('body.response_data.status', 'approved')
            ->assertJsonPath('body.response_data.child.status', 'active')
            ->assertJsonCount(5, 'body.response_data.tuition_installments');

        // 6. Paiement des frais d'inscription (50 000 FCFA) délégué au système de paiements réels
        $methodFlooz = PaymentMethod::where('code', 'flooz')->firstOrFail();
        $firstInstallmentId = $approveResponse->json('body.response_data.tuition_installments.0.id');
        $payResponse = $this->putJson("/api/tuition_installment/{$firstInstallmentId}/pay", [
            'amount_paid' => 50000,
            'payment_method_id' => $methodFlooz->id,
            'payer_guardian_id' => $parentFather->id,
            'payment_reference' => 'FLOOZ-TXN-998877',
            'notes' => 'Règlement Flooz',
        ], $headers);

        $payResponse->assertStatus(200)
            ->assertJsonPath('body.response_data.status', 'paid')
            ->assertJsonPath('body.response_data.paid_amount', 50000)
            ->assertJsonPath('body.response_data.remaining_amount', 0);

        // Vérifie qu'un paiement réel et son allocation comptable ont bien été enregistrés
        $this->assertDatabaseHas('payments', [
            'amount' => 50000,
            'child_id' => $childId,
            'payer_guardian_id' => $parentFather->id,
        ]);
        $this->assertDatabaseHas('payment_allocations', [
            'tuition_installment_id' => $firstInstallmentId,
            'amount' => 50000,
        ]);
    }

    public function test_siblings_can_share_guardians_via_child_guardian_pivot(): void
    {
        $headers = $this->fakeAuthApi(['*']);

        $relFather = RelationshipType::where('code', 'father')->firstOrFail();

        // Création du père
        $father = Guardian::create([
            'full_name' => 'Paul Mensah',
            'phone' => '+22899887766',
        ]);

        // Premier enfant
        $child1 = Child::create([
            'first_name' => 'Fofo',
            'last_name' => 'Mensah',
            'birth_date' => '2024-01-15',
            'sex' => 'M',
        ]);

        // Deuxième enfant (frère cadet)
        $child2 = Child::create([
            'first_name' => 'Koko',
            'last_name' => 'Mensah',
            'birth_date' => '2025-06-20',
            'sex' => 'M',
        ]);

        // Le même père est associé aux deux enfants
        ChildGuardian::create([
            'child_id' => $child1->id,
            'guardian_id' => $father->id,
            'relationship_type_id' => $relFather->id,
            'is_primary_contact' => true,
        ]);

        ChildGuardian::create([
            'child_id' => $child2->id,
            'guardian_id' => $father->id,
            'relationship_type_id' => $relFather->id,
            'is_primary_contact' => true,
        ]);

        // Vérifications
        $this->assertEquals(2, $father->children()->count());
        $this->assertEquals(1, $child1->guardians()->count());
        $this->assertEquals(1, $child2->guardians()->count());
        $this->assertEquals('Paul Mensah', $child1->guardians->first()->full_name);
        $this->assertEquals('Paul Mensah', $child2->guardians->first()->full_name);
    }

    public function test_payments_and_allocations_support_partial_payments_and_bulk_settlement(): void
    {
        $headers = $this->fakeAuthApi(['*']);

        $child = Child::create([
            'first_name' => 'Aya',
            'last_name' => 'Koffi',
            'birth_date' => '2024-05-10',
            'sex' => 'F',
        ]);

        $guardian = Guardian::create([
            'full_name' => 'Serge Koffi',
            'phone' => '+22890998877',
        ]);

        $registration = \App\Models\Registration::create([
            'child_id' => $child->id,
            'academic_year' => '2026-2027',
            'status' => 'approved',
        ]);

        // Créance : une tranche de 400 000 FCFA
        $installment = TuitionInstallment::create([
            'registration_id' => $registration->id,
            'installment_code' => 'tranche_1',
            'label' => 'Tranche 1',
            'amount' => 400000,
            'paid_amount' => 0,
            'due_date' => '2026-11-30',
            'status' => 'pending',
        ]);

        // 1. Premier acompte de 150 000 FCFA
        $payment1Response = $this->postJson('/api/payment', [
            'payer_guardian_id' => $guardian->id,
            'child_id' => $child->id,
            'amount' => 150000,
            'paid_at' => '2026-11-06 10:00:00',
            'payment_method' => 'bank_transfer',
            'allocations' => [
                ['tuition_installment_id' => $installment->id, 'amount' => 150000],
            ],
        ], $headers);

        $payment1Response->assertStatus(200);
        $installment->refresh();
        $this->assertEquals(150000, $installment->paid_amount);
        $this->assertEquals('partial', $installment->status);
        $this->assertEquals(250000, $installment->remaining_amount);

        // 2. Deuxième versement de 250 000 FCFA pour solder la tranche
        $payment2Response = $this->postJson('/api/payment', [
            'payer_guardian_id' => $guardian->id,
            'child_id' => $child->id,
            'amount' => 250000,
            'paid_at' => '2026-11-06 14:00:00',
            'payment_method' => 'cash',
            'allocations' => [
                ['tuition_installment_id' => $installment->id, 'amount' => 250000],
            ],
        ], $headers);

        $payment2Response->assertStatus(200);
        $installment->refresh();
        $this->assertEquals(400000, $installment->paid_amount);
        $this->assertEquals('paid', $installment->status);
        $this->assertEquals(0, $installment->remaining_amount);
    }

    public function test_attendance_check_out_after_18h30_automatically_calculates_overtime_and_creates_charge(): void
    {
        $headers = $this->fakeAuthApi([
            'child-create',
            'attendance-check_in',
            'attendance-check_out',
            'additional_charge-list',
        ]);

        $childResponse = $this->postJson('/api/child', [
            'first_name' => 'Yasmine',
            'last_name' => 'Tchalla',
            'birth_date' => now()->subMonths(18)->toDateString(),
            'sex' => 'F',
            'birth_place' => 'Lomé',
        ], $headers);

        $childId = $childResponse->json('body.response_data.id');

        // Pointage d'arrivée avec dateTime
        $checkInResponse = $this->postJson('/api/attendance/check_in', [
            'child_id' => $childId,
            'attendance_date' => '2026-11-06',
            'check_in_at' => '2026-11-06 07:40:00',
            'dropped_off_by' => 'Maman',
        ], $headers);

        $checkInResponse->assertStatus(200);
        $attendanceId = $checkInResponse->json('body.response_data.id');

        // Pointage de départ à 19h40 (soit 70 minutes après 18h30 -> 2 heures entamées = 4 000 FCFA)
        $checkOutResponse = $this->putJson("/api/attendance/{$attendanceId}/check_out", [
            'check_out_at' => '2026-11-06 19:40:00',
            'third_party_pickup_name' => 'Tante Marie',
            'third_party_id_card' => 'TG-12345678',
        ], $headers);

        $checkOutResponse->assertStatus(200)
            ->assertJsonPath('body.response_data.overtime_minutes', 70)
            ->assertJsonPath('body.response_data.overtime_hours_billed', 2)
            ->assertJsonPath('body.response_data.overtime_amount', 4000);

        // Vérifie que la facturation automatique de garderie du soir a bien été créée
        $chargesResponse = $this->getJson('/api/additional_charge', $headers);
        $chargesResponse->assertStatus(200)
            ->assertJsonPath('body.response_data.0.charge_type', 'aftercare_hourly')
            ->assertJsonPath('body.response_data.0.total_amount', 4000);
    }

    public function test_employee_can_record_child_events_and_parent_timeline_tracks_supplies(): void
    {
        $headers = $this->fakeAuthApi([
            'child-create',
            'child-timeline',
            'child_event-create',
            'child_event-update',
        ]);

        $childResponse = $this->postJson('/api/child', [
            'first_name' => 'Noé',
            'last_name' => 'Amegan',
            'birth_date' => now()->subMonths(10)->toDateString(),
            'sex' => 'M',
            'birth_place' => 'Lomé',
        ], $headers);

        $childId = $childResponse->json('body.response_data.id');

        $typeMeal = ChildEventType::where('code', 'meal')->firstOrFail();
        $typeSupply = ChildEventType::where('code', 'supply_request')->firstOrFail();

        // 1. L'employé enregistre un repas avec event_at en dateTime
        $this->postJson('/api/child_event', [
            'child_id' => $childId,
            'child_event_type_id' => $typeMeal->id,
            'title' => 'Déjeuner complet',
            'description' => 'Purée de patate douce et poisson, tout mangé.',
            'event_at' => '2026-11-06 12:00:00',
            'metadata' => ['appetite' => 'all'],
        ], $headers)->assertStatus(200);

        // 2. L'employé signale un besoin de couches avec event_at en dateTime
        $supplyResponse = $this->postJson('/api/child_event', [
            'child_id' => $childId,
            'child_event_type_id' => $typeSupply->id,
            'severity' => 'attention',
            'title' => 'Besoin de couches et lingettes',
            'description' => 'Merci de ramener un paquet de couches demain matin.',
            'event_at' => '2026-11-06 16:00:00',
            'metadata' => ['items' => ['diapers', 'wipes']],
        ], $headers);

        $supplyEventId = $supplyResponse->json('body.response_data.id');

        // 3. Consultation de la Timeline différée de l'enfant
        $timelineResponse = $this->getJson("/api/child/{$childId}/timeline?date=2026-11-06", $headers);
        $timelineResponse->assertStatus(200)
            ->assertJsonCount(2, 'body.response_data.events')
            ->assertJsonCount(1, 'body.response_data.pending_supplies');

        // 4. Le parent apporte les couches le lendemain -> l'employé marque la demande comme complétée
        $this->putJson("/api/child_event/{$supplyEventId}/fulfill_supply", [], $headers)
            ->assertStatus(200);

        $updatedTimeline = $this->getJson("/api/child/{$childId}/timeline", $headers);
        $updatedTimeline->assertStatus(200)
            ->assertJsonCount(0, 'body.response_data.pending_supplies');
    }

    public function test_registration_rejection_and_optional_meal_charges_payment(): void
    {
        $headers = $this->fakeAuthApi(['*']);

        $childResponse = $this->postJson('/api/child', [
            'first_name' => 'Sami',
            'last_name' => 'Ouro',
            'birth_date' => now()->subMonths(30)->toDateString(),
            'sex' => 'M',
            'birth_place' => 'Kara',
            'registrations' => [
                [
                    'academic_year' => '2026-2027',
                    'consent_first_aid' => true,
                    'consent_photos_videos' => false,
                    'consent_emergency_pickup' => true,
                    'declarant_name' => 'Issa Ouro',
                ],
            ],
        ], $headers);

        $childId = $childResponse->json('body.response_data.id');
        $dossier = $this->getJson("/api/child/{$childId}/dossier", $headers);
        $registrationId = $dossier->json('body.response_data.registrations.0.id');

        // Rejet du dossier
        $rejectResponse = $this->putJson("/api/registration/{$registrationId}/reject", [
            'decision_comment' => 'Carnet de vaccination manquant.',
            'admin_observations' => 'Relancer le parent.',
        ], $headers);

        $rejectResponse->assertStatus(200)
            ->assertJsonPath('body.response_data.status', 'rejected')
            ->assertJsonPath('body.response_data.decision_comment', 'Carnet de vaccination manquant.');

        // Facturation de 5 jours de repas à 1 500 FCFA / jour (7 500 FCFA)
        $chargeResponse = $this->postJson('/api/additional_charge', [
            'child_id' => $childId,
            'registration_id' => $registrationId,
            'charge_type' => 'daily_meal',
            'charge_date' => '2026-11-06',
            'quantity' => 5,
            'unit_price' => 1500,
        ], $headers);

        $chargeResponse->assertStatus(200)
            ->assertJsonPath('body.response_data.total_amount', 7500);

        $chargeId = $chargeResponse->json('body.response_data.id');

        // Paiement des repas via la ventilation de paiement
        $payChargeResponse = $this->putJson("/api/additional_charge/{$chargeId}/pay", [
            'payment_method' => 'cash',
        ], $headers);

        $payChargeResponse->assertStatus(200)
            ->assertJsonPath('body.response_data.status', 'paid');
    }

    public function test_demo_creche_seeder_populates_complete_sample_data(): void
    {
        $this->seed(DemoCrecheSeeder::class);

        $headers = $this->fakeAuthApi(['*']);
        $response = $this->getJson('/api/child', $headers);

        $response->assertStatus(200)
            ->assertJsonCount(2, 'body.response_data');
    }

    public function test_configuration_tables_are_seeded_and_allow_dynamic_schedule_and_pricing_overtime_calculation(): void
    {
        $headers = $this->fakeAuthApi([
            'payment_method-list',
            'pricing_configuration-list',
            'operating_schedule-list',
        ]);

        // 1. Vérification des 5 méthodes de paiement normalisées
        $pmResponse = $this->getJson('/api/payment_method', $headers);
        $pmResponse->assertStatus(200)
            ->assertJsonCount(5, 'body.response_data');

        // 2. Vérification de la grille tarifaire configurable
        $pricingResponse = $this->getJson('/api/pricing_configuration', $headers);
        $pricingResponse->assertStatus(200)
            ->assertJsonPath('body.response_data.0.currency', 'XOF');

        // 3. Vérification des horaires des 7 jours de la semaine
        $scheduleResponse = $this->getJson('/api/operating_schedule', $headers);
        $scheduleResponse->assertStatus(200)
            ->assertJsonCount(7, 'body.response_data');

        // 4. Test du calcul dynamique d'heures sup : départ un vendredi à 19h30 (fermeture standard 18h30, taux standard 2000 F)
        // 2026-11-06 est un Vendredi (day_of_week = 5)
        $overtime1 = Attendance::calculateOvertime('2026-11-06 19:30:00');
        $this->assertEquals(60, $overtime1['overtime_minutes']);
        $this->assertEquals(1, $overtime1['overtime_hours_billed']);
        $this->assertEquals(2000, $overtime1['overtime_amount']);

        // 5. Modification dynamique du tarif de garderie du soir -> 2 500 FCFA
        PricingConfiguration::where('code', 'overtime_hourly')->update(['amount' => 2500]);

        $overtime2 = Attendance::calculateOvertime('2026-11-06 19:30:00');
        $this->assertEquals(60, $overtime2['overtime_minutes']);
        $this->assertEquals(1, $overtime2['overtime_hours_billed']);
        $this->assertEquals(2500, $overtime2['overtime_amount']);

        // 6. Modification dynamique de l'horaire de fermeture du vendredi -> 19h00
        OperatingSchedule::where('day_of_week', 5)->update(['closing_time' => '19:00:00']);

        // Départ à 19h30 (soit 30 min après 19h00 -> 1h entamée à 2 500 FCFA)
        $overtime3 = Attendance::calculateOvertime('2026-11-06 19:30:00');
        $this->assertEquals(30, $overtime3['overtime_minutes']);
        $this->assertEquals(1, $overtime3['overtime_hours_billed']);
        $this->assertEquals(2500, $overtime3['overtime_amount']);

        // Départ à 18h50 (avant 19h00 -> 0 minute d'overtime)
        $overtime4 = Attendance::calculateOvertime('2026-11-06 18:50:00');
        $this->assertEquals(0, $overtime4['overtime_minutes']);
        $this->assertEquals(0, $overtime4['overtime_amount']);
    }
}
