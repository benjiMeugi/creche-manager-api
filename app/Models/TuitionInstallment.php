<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TuitionInstallment extends Model implements IModel
{
    use HasFactory;

    public static array $STATUS_OPTIONS = [
        'pending',
        'partial',
        'paid',
        'overdue',
    ];

    protected $fillable = [
        'registration_id',
        'section_tranche_id',
        'installment_code',
        'label',
        'amount',
        'paid_amount',
        'due_date',
        'status',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'registration_id' => 'integer',
        'section_tranche_id' => 'integer',
        'amount' => 'integer',
        'paid_amount' => 'integer',
        'due_date' => 'date:Y-m-d',
        'recorded_by_user_id' => 'integer',
    ];

    protected $appends = ['remaining_amount'];

    public $relation_methods = ['registration', 'sectionTranche', 'paymentAllocations'];

    public function getMigrateKey()
    {
        return 'tuition_installment_id';
    }

    public function rules()
    {
        return [
            'registration_id' => ['required', 'exists:registrations,id'],
            'section_tranche_id' => ['nullable', 'exists:section_tranches,id'],
            'installment_code' => ['required', 'string', 'max:50'],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'paid_amount' => ['sometimes', 'integer', 'min:0'],
            'due_date' => ['required', 'date'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'registration_id' => ['sometimes', 'exists:registrations,id'],
            'section_tranche_id' => ['sometimes', 'nullable', 'exists:section_tranches,id'],
            'installment_code' => ['sometimes', 'string', 'max:50'],
            'label' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'integer', 'min:0'],
            'paid_amount' => ['sometimes', 'integer', 'min:0'],
            'due_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function getRemainingAmountAttribute(): int
    {
        return max(0, (int) $this->amount - (int) $this->paid_amount);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function sectionTranche(): BelongsTo
    {
        return $this->belongsTo(SectionTranche::class, 'section_tranche_id');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class, 'tuition_installment_id');
    }
}
