<?php

use App\Http\Controllers\AdditionalChargeController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ChildController;
use App\Http\Controllers\ChildEventController;
use App\Http\Controllers\ChildEventTypeController;
use App\Http\Controllers\ChildGuardianController;
use App\Http\Controllers\ChildMedicalProfileController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\GuardianController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\OperatingScheduleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\PricingConfigurationController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RelationshipTypeController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\SectionTrancheController;
use App\Http\Controllers\TuitionInstallmentController;
use App\Http\Middleware\VerifyAuthApiToken;
use Illuminate\Support\Facades\Route;

/**
 * Génère le nom d'habileté attendu par CheckAbility (ex: "child-list"),
 * préfixé par "ability:" pour que le middleware CheckAbility vérifie bien
 * la permission correspondante dans auth_user.permissions.
 */
if (! function_exists('resolveAbility')) {
    function resolveAbility(string $abilityName, string $action): string
    {
        return 'ability:' . $abilityName . '-' . $action;
    }
}

/**
 * Helper pour enregistrer rapidement les 5 routes standard d'un CRUD API.
 */
if (! function_exists('registerApiCrud')) {
    function registerApiCrud(string $prefix, string $controller): void
    {
        Route::prefix($prefix)->group(function () use ($controller, $prefix) {
            $abilityName = $prefix;

            Route::get('/', [$controller, 'index'])->middleware(resolveAbility($abilityName, 'list'));
            Route::get('/{id}', [$controller, 'show'])->middleware(resolveAbility($abilityName, 'show'));
            Route::post('/', [$controller, 'store'])->middleware(resolveAbility($abilityName, 'create'));
            Route::put('/{id}', [$controller, 'update'])->middleware(resolveAbility($abilityName, 'update'));
            Route::delete('/{id}', [$controller, 'delete'])->middleware(resolveAbility($abilityName, 'delete'));
        });
    }
}

Route::middleware([VerifyAuthApiToken::class])->group(function () {

    // Permissions et profil de l'employé connecté via authentication-api
    Route::get('/permissions', [PermissionController::class, 'show']);
    Route::get('/me', [PermissionController::class, 'me']);

    // Actions métier spécifiques sur child (Dossier complet & Timeline différée)
    Route::prefix('child')->group(function () {
        Route::get('/{id}/dossier', [ChildController::class, 'dossier'])->middleware(resolveAbility('child', 'show'));
        Route::get('/{id}/timeline', [ChildController::class, 'timeline'])->middleware(resolveAbility('child', 'timeline'));
    });

    // Actions métier spécifiques sur registration (Approbation & Rejet par l'administration)
    Route::prefix('registration')->group(function () {
        Route::put('/{id}/approve', [RegistrationController::class, 'approve'])->middleware(resolveAbility('registration', 'approve'));
        Route::put('/{id}/reject', [RegistrationController::class, 'reject'])->middleware(resolveAbility('registration', 'reject'));
    });

    // Actions métier spécifiques sur attendance (Pointages d'arrivée et de départ post-18h30)
    Route::prefix('attendance')->group(function () {
        Route::post('/check_in', [AttendanceController::class, 'checkIn'])->middleware(resolveAbility('attendance', 'check_in'));
        Route::put('/{id}/check_out', [AttendanceController::class, 'checkOut'])->middleware(resolveAbility('attendance', 'check_out'));
    });

    // Action métier spécifique sur child_event (Réception des fournitures demandées)
    Route::prefix('child_event')->group(function () {
        Route::put('/{id}/fulfill_supply', [ChildEventController::class, 'fulfillSupply'])->middleware(resolveAbility('child_event', 'update'));
    });

    // Action métier spécifique sur tuition_installment (Règlement d'une tranche)
    Route::prefix('tuition_installment')->group(function () {
        Route::put('/{id}/pay', [TuitionInstallmentController::class, 'pay'])->middleware(resolveAbility('tuition_installment', 'pay'));
    });

    // Action métier spécifique sur additional_charge (Règlement d'un service optionnel / garderie)
    Route::prefix('additional_charge')->group(function () {
        Route::put('/{id}/pay', [AdditionalChargeController::class, 'pay'])->middleware(resolveAbility('additional_charge', 'pay'));
    });

    // Action métier spécifique sur payment (Ventilation comptable / Imputation)
    Route::prefix('payment')->group(function () {
        Route::post('/{id}/allocate', [PaymentController::class, 'allocate'])->middleware(resolveAbility('payment', 'update'));
    });

    // Pièces jointes polymorphiques (Section VII du PDF + médias événements)
    Route::prefix('attachment')->group(function () {
        $controller = AttachmentController::class;
        $startAbility = 'attachment';

        Route::get('/', [$controller, 'index'])->middleware(resolveAbility($startAbility, 'list'));
        Route::get('/{id}', [$controller, 'show'])->middleware(resolveAbility($startAbility, 'show'));
        Route::put('/{id}', [$controller, 'update'])->middleware(resolveAbility($startAbility, 'update'));
        Route::delete('/{id}', [$controller, 'delete'])->middleware(resolveAbility($startAbility, 'delete'));
        Route::get('/{id}/download', [$controller, 'download'])->middleware(resolveAbility($startAbility, 'download'));
        Route::post('/registration/{id}', [$controller, 'storeForRegistration'])->middleware(resolveAbility($startAbility, 'create'));
        Route::post('/child_event/{id}', [$controller, 'storeForChildEvent'])->middleware(resolveAbility($startAbility, 'create'));
        Route::post('/child/{id}', [$controller, 'storeForChild'])->middleware(resolveAbility($startAbility, 'create'));
    });

    // CRUDs des référentiels normalisés & configurations dynamiques
    registerApiCrud('section', SectionController::class);
    registerApiCrud('section_tranche', SectionTrancheController::class);
    registerApiCrud('language', LanguageController::class);
    registerApiCrud('relationship_type', RelationshipTypeController::class);
    registerApiCrud('document_type', DocumentTypeController::class);
    registerApiCrud('child_event_type', ChildEventTypeController::class);
    registerApiCrud('payment_method', PaymentMethodController::class);
    registerApiCrud('pricing_configuration', PricingConfigurationController::class);
    registerApiCrud('operating_schedule', OperatingScheduleController::class);

    // CRUDs métiers principaux
    registerApiCrud('child', ChildController::class);
    registerApiCrud('guardian', GuardianController::class);
    registerApiCrud('child_guardian', ChildGuardianController::class);
    registerApiCrud('child_medical_profile', ChildMedicalProfileController::class);
    registerApiCrud('registration', RegistrationController::class);
    registerApiCrud('attendance', AttendanceController::class);
    registerApiCrud('child_event', ChildEventController::class);
    registerApiCrud('tuition_installment', TuitionInstallmentController::class);
    registerApiCrud('additional_charge', AdditionalChargeController::class);
    registerApiCrud('payment', PaymentController::class);
});
