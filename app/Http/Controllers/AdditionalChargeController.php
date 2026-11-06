<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Repository\Repository;
use App\Models\AdditionalCharge;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdditionalChargeController extends Controller
{
    private AdditionalCharge $model;

    private Repository $repository;

    public function __construct()
    {
        $this->model = new AdditionalCharge();
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

        if (! $request->filled('total_amount') && $request->filled('quantity') && $request->filled('unit_price')) {
            $request->merge(['total_amount' => (int) $request->input('quantity') * (int) $request->input('unit_price')]);
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
     * Marque un frais additionnel (repas journalier, garderie du soir, forfait semaine) comme payé
     * via la table des règlements et sa ventilation.
     */
    public function pay(Request $request, int $id)
    {
        $charge = AdditionalCharge::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount_paid' => ['nullable', 'integer', 'min:1'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'payment_method' => ['nullable', 'string'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payer_guardian_id' => ['nullable', 'exists:guardians,id'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->messages());
        }

        $amount = (int) ($request->input('amount_paid') ?? max(0, $charge->total_amount - $charge->paid_amount));

        $payment = Payment::create([
            'payer_guardian_id' => $request->input('payer_guardian_id'),
            'child_id' => $charge->child_id,
            'payment_method_id' => $request->input('payment_method_id'),
            'payment_method' => $request->input('payment_method'),
            'receipt_number' => $request->input('receipt_number'),
            'amount' => $amount,
            'paid_at' => now(),
            'payment_reference' => $request->input('payment_reference'),
            'notes' => $request->input('notes'),
            'recorded_by_user_id' => $this->getAuthenticatedUserId($request),
        ]);

        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'additional_charge_id' => $charge->id,
            'amount' => $amount,
        ]);

        return $this->respondOk(
            $charge->fresh(['paymentAllocations.payment']),
            'Frais additionnel réglé avec succès.'
        );
    }
}
