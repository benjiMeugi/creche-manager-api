<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAllocation extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'tuition_installment_id',
        'additional_charge_id',
        'amount',
    ];

    protected $casts = [
        'payment_id' => 'integer',
        'tuition_installment_id' => 'integer',
        'additional_charge_id' => 'integer',
        'amount' => 'integer',
    ];

    public $relation_methods = ['payment', 'tuitionInstallment', 'additionalCharge'];

    public function getMigrateKey()
    {
        return 'payment_allocation_id';
    }

    public function rules()
    {
        return [
            'payment_id' => ['required', 'exists:payments,id'],
            'tuition_installment_id' => ['nullable', 'exists:tuition_installments,id'],
            'additional_charge_id' => ['nullable', 'exists:additional_charges,id'],
            'amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function update_rules()
    {
        return [
            'payment_id' => ['sometimes', 'exists:payments,id'],
            'tuition_installment_id' => ['sometimes', 'nullable', 'exists:tuition_installments,id'],
            'additional_charge_id' => ['sometimes', 'nullable', 'exists:additional_charges,id'],
            'amount' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function tuitionInstallment(): BelongsTo
    {
        return $this->belongsTo(TuitionInstallment::class, 'tuition_installment_id');
    }

    public function additionalCharge(): BelongsTo
    {
        return $this->belongsTo(AdditionalCharge::class, 'additional_charge_id');
    }

    /**
     * Recalcule automatiquement les soldes et statuts des créances impactées.
     */
    public function syncDebtBalances(): void
    {
        if ($this->tuition_installment_id && $installment = $this->tuitionInstallment) {
            $totalPaid = (int) static::where('tuition_installment_id', $installment->id)->sum('amount');
            $installment->paid_amount = $totalPaid;
            if ($totalPaid >= $installment->amount) {
                $installment->status = 'paid';
            } elseif ($totalPaid > 0) {
                $installment->status = 'partial';
            } else {
                $installment->status = 'pending';
            }
            $installment->saveQuietly();
        }

        if ($this->additional_charge_id && $charge = $this->additionalCharge) {
            $totalPaid = (int) static::where('additional_charge_id', $charge->id)->sum('amount');
            $charge->paid_amount = $totalPaid;
            if ($totalPaid >= $charge->amount) {
                $charge->status = 'paid';
            } elseif ($totalPaid > 0) {
                $charge->status = 'partial';
            } else {
                $charge->status = 'pending';
            }
            $charge->saveQuietly();
        }
    }

    protected static function booted(): void
    {
        static::saved(function (PaymentAllocation $allocation) {
            $allocation->syncDebtBalances();
        });

        static::deleted(function (PaymentAllocation $allocation) {
            $allocation->syncDebtBalances();
        });
    }
}
