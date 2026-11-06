<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Repository\Repository;
use App\Models\Registration;
use App\Services\TuitionScheduleGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    private Registration $model;

    private Repository $repository;

    public function __construct()
    {
        $this->model = new Registration();
        $this->repository = new Repository($this->model);
    }

    public function index(Request $request)
    {
        $query = $this->repository->parse_filters($request);

        if ($request->has('page') && $request->has('per_page')) {
            return $this->respondOk($query->paginate($request->input('per_page')));
        }

        return $this->respondOk($query->get());
    }

    public function show(Request $request, int $id)
    {
        return $this->repository->show($request, $id);
    }

    public function store(Request $request)
    {
        if (! $request->filled('created_by_user_id') && $this->getAuthenticatedUserId($request)) {
            $request->merge(['created_by_user_id' => $this->getAuthenticatedUserId($request)]);
        }

        $validator = $this->repository->check($request, $this->repository->rules());
        if (true !== $validator) {
            return $validator;
        }

        return $this->respondOk($this->repository->store($request));
    }

    public function update(Request $request, int $id)
    {
        $validator = $this->repository->check($request, $this->repository->update_rules(), $id);
        if (true !== $validator) {
            return $validator;
        }

        return $this->respondOk($this->repository->update($request, $id));
    }

    public function delete(Request $request, int $id)
    {
        return $this->respondOk($this->repository->delete($request, $id));
    }

    /**
     * Approuve un dossier d'inscription (Section XI : Réservé à l'administration) :
     * - Assigne la section / groupe et la date d'admission
     * - Passe l'enfant au statut 'active'
     * - Génère automatiquement l'échéancier des 5 paiements (Frais d'inscription + 4 tranches)
     */
    public function approve(Request $request, int $id)
    {
        $registration = Registration::with(['child', 'section'])->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'section_id' => ['nullable', 'exists:sections,id'],
            'admission_date' => ['nullable', 'date'],
            'admin_observations' => ['nullable', 'string'],
            'decision_comment' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->messages());
        }

        $userId = $this->getAuthenticatedUserId($request);

        $updated = DB::transaction(function () use ($registration, $request, $userId) {
            if ($request->filled('section_id')) {
                $registration->section_id = (int) $request->input('section_id');
            }

            $registration->admission_date = $request->input('admission_date', $registration->admission_date ?? now()->toDateString());
            if ($request->has('admin_observations')) {
                $registration->admin_observations = $request->input('admin_observations');
            }
            if ($request->has('decision_comment')) {
                $registration->decision_comment = $request->input('decision_comment');
            }

            $registration->status = 'approved';
            $registration->decision_datetime = now();
            $registration->processed_by_user_id = $userId;
            $registration->save();

            // Active l'enfant
            if ($registration->child) {
                $registration->child->status = 'active';
                $registration->child->save();
            }

            // Génère automatiquement l'échéancier financier (Section VIII du PDF)
            $registration->load(['section', 'child']);
            (new TuitionScheduleGenerator())->generateForRegistration($registration, $userId);

            return $registration->fresh(['child', 'section', 'tuitionInstallments', 'attachments']);
        });

        return $this->respondOk($updated, "Dossier d'inscription approuvé et échéancier généré avec succès.");
    }

    /**
     * Rejette un dossier d'inscription avec motif administratif.
     */
    public function reject(Request $request, int $id)
    {
        $registration = Registration::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'decision_comment' => ['required', 'string'],
            'admin_observations' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->messages());
        }

        $registration->status = 'rejected';
        $registration->decision_comment = $request->input('decision_comment');
        if ($request->has('admin_observations')) {
            $registration->admin_observations = $request->input('admin_observations');
        }
        $registration->decision_datetime = now();
        $registration->processed_by_user_id = $this->getAuthenticatedUserId($request);
        $registration->save();

        return $this->respondOk($registration, "Dossier d'inscription rejeté.");
    }
}
