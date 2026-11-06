<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model implements IModel
{
    use HasFactory;

    /**
     * Codes historiques des moyens de paiement supportés par défaut.
     */
    public static array $PAYMENT_METHODS = [
        'cash',
        'flooz',
        'tmoney',
        'bank_transfer',
        'check',
    ];

    protected $fillable = [
        'payer_guardian_id',
        'child_id',
        'payment_method_id',
        'payment_method', // Attribut virtuel rétrocompatible
        'receipt_number',
        'amount',
        'paid_at',
        'payment_reference',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'payer_guardian_id' => 'integer',
        'child_id' => 'integer',
        'payment_method_id' => 'integer',
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'recorded_by_user_id' => 'integer',
    ];

    public $relation_methods = ['payerGuardian', 'child', 'paymentMethod', 'allocations'];

    public function getMigrateKey()
    {
        return 'payment_id';
    }

    public function rules()
    {
        return [
            'payer_guardian_id' => ['nullable', 'exists:guardians,id'],
            'child_id' => ['nullable', 'exists:children,id'],
            'payment_method_id' => ['nullable', 'exists:payment_methods,id'],
            'payment_method' => ['sometimes', 'nullable', 'string'],
            'receipt_number' => ['nullable', 'string', 'max:50', 'unique:payments,receipt_number'],
            'amount' => ['required', 'integer', 'min:1'],
            'paid_at' => ['required', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'payer_guardian_id' => ['sometimes', 'nullable', 'exists:guardians,id'],
            'child_id' => ['sometimes', 'nullable', 'exists:children,id'],
            'payment_method_id' => ['sometimes', 'nullable', 'exists:payment_methods,id'],
            'payment_method' => ['sometimes', 'nullable', 'string'],
            'receipt_number' => ['sometimes', 'string', 'max:50', 'unique:payments,receipt_number,' . $this->id],
            'amount' => ['sometimes', 'integer', 'min:1'],
            'paid_at' => ['sometimes', 'date'],
            'payment_reference' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (empty($payment->receipt_number)) {
                $payment->receipt_number = 'REC-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
            }

            // Résolution automatique si un code textuel est fourni au lieu d'un ID
            if (empty($payment->payment_method_id) && ! empty($payment->payment_method)) {
                $method = PaymentMethod::where('code', $payment->payment_method)->first();
                if ($method) {
                    $payment->payment_method_id = $method->id;
                }
            }

            // Fallback par défaut sur le premier moyen actif
            if (empty($payment->payment_method_id)) {
                $defaultMethod = PaymentMethod::where('is_active', true)->first();
                if ($defaultMethod) {
                    $payment->payment_method_id = $defaultMethod->id;
                }
            }

            // Synchronisation du code textuel si seul l'ID a été spécifié
            if (! empty($payment->payment_method_id) && empty($payment->payment_method)) {
                $payment->payment_method = PaymentMethod::find($payment->payment_method_id)?->code;
            }
        });
    }

    public function payerGuardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'payer_guardian_id');
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'payment_id');
    }
}
