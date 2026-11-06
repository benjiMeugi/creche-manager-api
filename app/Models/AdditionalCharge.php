<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdditionalCharge extends Model implements IModel
{
    use HasFactory;

    public static array $TYPE_OPTIONS = [
        'daily_meal',        // Repas à 1 500 FCFA / jour
        'aftercare_hourly',   // Garderie du soir post-18h30 (2 000 FCFA / heure entamée)
        'weekly_package',    // Forfait hebdomadaire
        'other',
    ];

    public static array $STATUS_OPTIONS = [
        'pending',
        'partial',
        'paid',
        'cancelled',
    ];

    protected $fillable = [
        'child_id',
        'registration_id',
        'charge_type',
        'charge_date',
        'quantity',
        'unit_price',
        'total_amount',
        'paid_amount',
        'status',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'child_id' => 'integer',
        'registration_id' => 'integer',
        'charge_date' => 'date:Y-m-d',
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'recorded_by_user_id' => 'integer',
    ];

    protected $appends = ['remaining_amount'];

    public $relation_methods = ['child', 'registration', 'paymentAllocations'];

    public function getMigrateKey()
    {
        return 'additional_charge_id';
    }

    public function rules()
    {
        return [
            'child_id' => ['required', 'exists:children,id'],
            'registration_id' => ['nullable', 'exists:registrations,id'],
            'charge_type' => ['required', 'in:' . implode(',', self::$TYPE_OPTIONS)],
            'charge_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'integer', 'min:0'],
            'total_amount' => ['sometimes', 'integer', 'min:0'],
            'paid_amount' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'child_id' => ['sometimes', 'exists:children,id'],
            'registration_id' => ['sometimes', 'nullable', 'exists:registrations,id'],
            'charge_type' => ['sometimes', 'in:' . implode(',', self::$TYPE_OPTIONS)],
            'charge_date' => ['sometimes', 'date'],
            'quantity' => ['sometimes', 'integer', 'min:1'],
            'unit_price' => ['sometimes', 'integer', 'min:0'],
            'total_amount' => ['sometimes', 'integer', 'min:0'],
            'paid_amount' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, (int) $this->total_amount - (int) $this->paid_amount);
    }

    protected static function booted(): void
    {
        static::creating(function (AdditionalCharge $charge) {
            if (empty($charge->total_amount)) {
                $charge->total_amount = (int) ($charge->quantity ?? 1) * (int) ($charge->unit_price ?? 0);
            }
        });
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'additional_charge_id');
    }
}
