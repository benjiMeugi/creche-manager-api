<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildMedicalProfile extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'child_id',
        'doctor_name_contact',
        'vaccines_up_to_date',
        'has_allergies',
        'allergies_details',
        'medical_conditions',
        'has_ongoing_treatment',
        'ongoing_treatment_details',
        'blood_group',
    ];

    protected $casts = [
        'child_id' => 'integer',
        'vaccines_up_to_date' => 'boolean',
        'has_allergies' => 'boolean',
        'has_ongoing_treatment' => 'boolean',
    ];

    public $relation_methods = ['child'];

    public function getMigrateKey()
    {
        return 'child_medical_profile_id';
    }

    public function rules()
    {
        return [
            'child_id' => ['required', 'exists:children,id', 'unique:' . $this->getTable() . ',child_id'],
            'doctor_name_contact' => ['nullable', 'string', 'max:255'],
            'vaccines_up_to_date' => ['required', 'boolean'],
            'has_allergies' => ['required', 'boolean'],
            'allergies_details' => ['nullable', 'string'],
            'medical_conditions' => ['nullable', 'string'],
            'has_ongoing_treatment' => ['required', 'boolean'],
            'ongoing_treatment_details' => ['nullable', 'string'],
            'blood_group' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function update_rules()
    {
        return [
            'child_id' => ['sometimes', 'exists:children,id', IModel::IGNORE_RULE],
            'doctor_name_contact' => ['sometimes', 'nullable', 'string', 'max:255'],
            'vaccines_up_to_date' => ['sometimes', 'boolean'],
            'has_allergies' => ['sometimes', 'boolean'],
            'allergies_details' => ['sometimes', 'nullable', 'string'],
            'medical_conditions' => ['sometimes', 'nullable', 'string'],
            'has_ongoing_treatment' => ['sometimes', 'boolean'],
            'ongoing_treatment_details' => ['sometimes', 'nullable', 'string'],
            'blood_group' => ['sometimes', 'nullable', 'string', 'max:10'],
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }
}
