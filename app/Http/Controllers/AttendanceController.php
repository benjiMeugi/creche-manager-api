<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Repository\Repository;
use App\Models\AdditionalCharge;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    private Attendance $model;

    private Repository $repository;

    public function __construct()
    {
        $this->model = new Attendance();
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
        if (! $request->filled('recorded_by_user_id') && $this->getAuthenticatedUserId($request)) {
            $request->merge(['recorded_by_user_id' => $this->getAuthenticatedUserId($request)]);
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
     * Pointage rapide d'arrivée d'un enfant (ouverture à 07h30).
     */
    public function checkIn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'child_id' => ['required', 'exists:children,id'],
            'attendance_date' => ['nullable', 'date'],
            'check_in_at' => ['nullable', 'date'],
            'dropped_off_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->messages());
        }

        $date = $request->input('attendance_date', now()->toDateString());
        $checkInAt = $request->input('check_in_at') ? Carbon::parse($request->input('check_in_at')) : now();

        $attendance = Attendance::updateOrCreate(
            [
                'child_id' => (int) $request->input('child_id'),
                'attendance_date' => $date,
            ],
            [
                'status' => 'present',
                'check_in_at' => $checkInAt,
                'dropped_off_by' => $request->input('dropped_off_by'),
                'notes' => $request->input('notes'),
                'recorded_by_user_id' => $this->getAuthenticatedUserId($request),
            ]
        );

        return $this->respondOk($attendance, "Arrivée de l'enfant pointée avec succès.");
    }

    /**
     * Pointage du départ d'un enfant :
     * Calcule automatiquement tout dépassement au-delà de 18h30 (Section IX de la fiche d'inscription)
     * et crée une ligne de facturation dans additional_charges si un dépassement est constaté.
     */
    public function checkOut(Request $request, int $id)
    {
        $attendance = Attendance::with('child.registrations')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'check_out_at' => ['nullable', 'date'],
            'picked_up_by_guardian_id' => ['nullable', 'exists:guardians,id'],
            'third_party_pickup_name' => ['nullable', 'string', 'max:255'],
            'third_party_id_card' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->messages());
        }

        if (! $request->filled('picked_up_by_guardian_id') && ! $request->filled('third_party_pickup_name')) {
            return $this->respondBadRequest(['pickup' => ['Veuillez renseigner un parent référent ou le nom du tiers mandaté.']]);
        }

        $attendance->check_out_at = $request->input('check_out_at')
            ? Carbon::parse($request->input('check_out_at'))
            : now();
        $attendance->picked_up_by_guardian_id = $request->input('picked_up_by_guardian_id');
        $attendance->third_party_pickup_name = $request->input('third_party_pickup_name');
        $attendance->third_party_id_card = $request->input('third_party_id_card');

        if ($request->has('notes')) {
            $attendance->notes = $request->input('notes');
        }
        $attendance->recorded_by_user_id = $this->getAuthenticatedUserId($request);
        $attendance->save();

        // Si dépassement au-delà de 18h30, génère automatiquement la facturation de garderie du soir
        if ($attendance->overtime_hours_billed > 0 && $attendance->overtime_amount > 0) {
            $activeRegistration = $attendance->child?->registrations()
                ->where('status', 'approved')
                ->latest('id')
                ->first();

            $pickupPerson = $attendance->pickedUpByGuardian
                ? $attendance->pickedUpByGuardian->first_name . ' ' . $attendance->pickedUpByGuardian->last_name
                : $attendance->third_party_pickup_name;

            AdditionalCharge::updateOrCreate(
                [
                    'child_id' => $attendance->child_id,
                    'charge_type' => 'aftercare_hourly',
                    'charge_date' => $attendance->attendance_date->toDateString(),
                ],
                [
                    'registration_id' => $activeRegistration?->id,
                    'quantity' => $attendance->overtime_hours_billed,
                    'unit_price' => (int) config('services.creche.overtime_hourly_rate', 2000),
                    'total_amount' => $attendance->overtime_amount,
                    'status' => 'pending',
                    'notes' => sprintf(
                        'Dépassement horaire après 18h30 (%d min, départ à %s, récupéré par %s)',
                        $attendance->overtime_minutes,
                        $attendance->check_out_at->format('H:i'),
                        $pickupPerson ?? 'N/C'
                    ),
                    'recorded_by_user_id' => $this->getAuthenticatedUserId($request),
                ]
            );
        }

        return $this->respondOk(
            $attendance->load(['child', 'pickedUpByGuardian']),
            'Départ de l\'enfant enregistré avec succès.'
        );
    }
}
