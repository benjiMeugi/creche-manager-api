<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionTranche extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'tranche_number',
        'code',
        'label',
        'amount',
        'is_due_at_registration',
        'due_month',
        'due_day',
    ];

    protected $casts = [
        'section_id' => 'integer',
        'tranche_number' => 'integer',
        'amount' => 'integer',
        'is_due_at_registration' => 'boolean',
        'due_month' => 'integer',
        'due_day' => 'integer',
    ];

    public $relation_methods = ['section', 'tuitionInstallments'];

    public function getMigrateKey()
    {
        return 'section_tranche_id';
    }

    public function rules()
    {
        return [
            'section_id' => ['required', 'exists:sections,id'],
            'tranche_number' => ['required', 'integer', 'min:1'],
            'code' => ['required', 'string', 'max:50'],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'is_due_at_registration' => ['sometimes', 'boolean'],
            'due_month' => ['nullable', 'integer', 'between:1,12'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
        ];
    }

    public function update_rules()
    {
        return [
            'section_id' => ['sometimes', 'exists:sections,id'],
            'tranche_number' => ['sometimes', 'integer', 'min:1'],
            'code' => ['sometimes', 'string', 'max:50'],
            'label' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'integer', 'min:0'],
            'is_due_at_registration' => ['sometimes', 'boolean'],
            'due_month' => ['sometimes', 'nullable', 'integer', 'between:1,12'],
            'due_day' => ['sometimes', 'nullable', 'integer', 'between:1,31'],
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function tuitionInstallments(): HasMany
    {
        return $this->hasMany(TuitionInstallment::class, 'section_tranche_id');
    }
}
