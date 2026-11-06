<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Repository\Repository;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private Payment $model;

    private Repository $repository;

    public function __construct()
    {
        $this->model = new Payment();
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

        $payment = $this->repository->store($request);

        return $this->respondOk($payment, 'Règlement enregistré avec succès.');
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
     * Ventile une part de ce paiement sur une échéance de scolarité ou une charge annexe.
     */
    public function allocate(Request $request, int $id)
    {
        $payment = Payment::with('allocations')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'tuition_installment_id' => ['nullable', 'exists:tuition_installments,id'],
            'additional_charge_id' => ['nullable', 'exists:additional_charges,id'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->messages());
        }

        if (! $request->filled('tuition_installment_id') && ! $request->filled('additional_charge_id')) {
            return $this->respondBadRequest(['target' => ['Veuillez spécifier une échéance de scolarité ou une charge annexe.']]);
        }

        $allocatedTotal = (int) $payment->allocations()->sum('amount');
        $newAmount = (int) $request->input('amount');

        if (($allocatedTotal + $newAmount) > (int) $payment->amount) {
            return $this->respondBadRequest([
                'amount' => [
                    sprintf(
                        'Le montant alloué dépasse le total du paiement. Disponible : %d FCFA.',
                        max(0, (int) $payment->amount - $allocatedTotal)
                    ),
                ],
            ]);
        }

        $allocation = PaymentAllocation::create([
            'payment_id' => $payment->id,
            'tuition_installment_id' => $request->input('tuition_installment_id'),
            'additional_charge_id' => $request->input('additional_charge_id'),
            'amount' => $newAmount,
        ]);

        return $this->respondOk(
            $allocation->load(['payment', 'tuitionInstallment', 'additionalCharge']),
            'Ventilation comptable enregistrée avec succès.'
        );
    }
}
