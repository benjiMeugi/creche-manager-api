<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Repository\Repository;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\TuitionInstallment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TuitionInstallmentController extends Controller
{
    private TuitionInstallment $model;

    private Repository $repository;

    public function __construct()
    {
        $this->model = new TuitionInstallment();
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
     * Enregistre le paiement (total ou partiel) d'une tranche de scolarité ou des frais d'inscription
     * via la table des paiements réels et sa ventilation comptable.
     */
    public function pay(Request $request, int $id)
    {
        $installment = TuitionInstallment::with('registration.child')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount_paid' => ['required', 'integer', 'min:1'],
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

        $amount = (int) $request->input('amount_paid');
        $childId = $installment->registration?->child_id;

        $payment = Payment::create([
            'payer_guardian_id' => $request->input('payer_guardian_id'),
            'child_id' => $childId,
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
            'tuition_installment_id' => $installment->id,
            'amount' => $amount,
        ]);

        return $this->respondOk(
            $installment->fresh(['paymentAllocations.payment']),
            'Règlement enregistré et ventilé avec succès.'
        );
    }
}
