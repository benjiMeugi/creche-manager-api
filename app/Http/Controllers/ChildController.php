<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Repository\Repository;
use App\Models\Child;
use Illuminate\Http\Request;

class ChildController extends Controller
{
    private Child $model;

    private Repository $repository;

    public function __construct()
    {
        $this->model = new Child();
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

        $languageIds = $request->input('language_ids');
        $result = $this->repository->store($request);

        if (is_array($languageIds) && $result instanceof Child) {
            $result->languages()->sync($languageIds);
            $result->load('languages');
        }

        return $this->respondOk($result);
    }

    public function update(Request $request, int $id)
    {
        $validator = $this->repository->check($request, $this->repository->update_rules(), $id);
        if (true !== $validator) {
            return $validator;
        }

        $languageIds = $request->input('language_ids');
        $result = $this->repository->update($request, $id);

        if (is_array($languageIds) && $result instanceof Child) {
            $result->languages()->sync($languageIds);
            $result->load('languages');
        }

        return $this->respondOk($result);
    }

    public function delete(Request $request, int $id)
    {
        return $this->respondOk($this->repository->delete($request, $id));
    }

    /**
     * Retourne le dossier complet consolidé d'un enfant (identité, section active,
     * langues parlées, parents via le pivot child_guardian, fiche médicale,
     * inscriptions et pièces jointes).
     */
    public function dossier(Request $request, int $id)
    {
        $child = Child::with([
            'languages',
            'childGuardians.guardian',
            'childGuardians.relationshipType',
            'medicalProfile',
            'registrations.section.tranches',
            'registrations.tuitionInstallments.sectionTranche',
            'registrations.attachments.documentType',
            'attachments.documentType',
        ])->findOrFail($id);

        return $this->respondOk($child);
    }

    /**
     * Retourne la Timeline (suivi différé pour le parent ou l'équipe) d'un enfant :
     * - Journal des événements (repas, siestes, changes, santé, activités)
     * - Pointages d'arrivée/départ et dépassements post-18h30
     * - Alertes de réapprovisionnement en attente (couches, lingettes, habits)
     */
    public function timeline(Request $request, int $id)
    {
        $child = Child::with(['languages', 'medicalProfile'])->findOrFail($id);

        $eventsQuery = $child->childEvents()
            ->with(['eventType', 'attachments.documentType'])
            ->orderByDesc('event_at');

        $attendancesQuery = $child->attendances()
            ->with('pickedUpByGuardian')
            ->orderByDesc('attendance_date');

        if ($request->filled('date')) {
            $eventsQuery->whereDate('event_at', $request->input('date'));
            $attendancesQuery->whereDate('attendance_date', $request->input('date'));
        }

        if ($request->filled('from')) {
            $eventsQuery->whereDate('event_at', '>=', $request->input('from'));
            $attendancesQuery->whereDate('attendance_date', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $eventsQuery->whereDate('event_at', '<=', $request->input('to'));
            $attendancesQuery->whereDate('attendance_date', '<=', $request->input('to'));
        }

        if ($request->boolean('parent_view')) {
            $eventsQuery->where('is_shared_with_parent', true);
        }

        $pendingSupplies = $child->childEvents()
            ->whereHas('eventType', function ($q) {
                $q->where('code', 'supply_request');
            })
            ->whereNull('supply_fulfilled_at')
            ->orderByDesc('event_at')
            ->get();

        return $this->respondOk([
            'child' => $child,
            'attendances' => $attendancesQuery->limit(30)->get(),
            'events' => $eventsQuery->limit(50)->get(),
            'pending_supplies' => $pendingSupplies,
        ]);
    }
}
