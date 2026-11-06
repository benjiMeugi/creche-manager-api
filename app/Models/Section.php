<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'min_age_months',
        'max_age_months',
        'registration_fee',
        'total_tuition',
        'daily_meal_fee',
        'hourly_aftercare_fee',
        'weekly_rate',
        'capacity',
        'description',
    ];

    protected $casts = [
        'min_age_months' => 'integer',
        'max_age_months' => 'integer',
        'registration_fee' => 'integer',
        'total_tuition' => 'integer',
        'daily_meal_fee' => 'integer',
        'hourly_aftercare_fee' => 'integer',
        'weekly_rate' => 'integer',
        'capacity' => 'integer',
    ];

    public $relation_methods = ['tranches', 'children', 'registrations'];

    public function getMigrateKey()
    {
        return 'section_id';
    }

    public function rules()
    {
        return [
            'code' => ['required', 'string', 'max:100', 'unique:' . $this->getTable() . ',code'],
            'label' => ['required', 'string', 'max:255'],
            'min_age_months' => ['required', 'integer', 'min:0'],
            'max_age_months' => ['required', 'integer', 'gte:min_age_months'],
            'registration_fee' => ['sometimes', 'integer', 'min:0'],
            'total_tuition' => ['required', 'integer', 'min:0'],
            'daily_meal_fee' => ['sometimes', 'integer', 'min:0'],
            'hourly_aftercare_fee' => ['sometimes', 'integer', 'min:0'],
            'weekly_rate' => ['required', 'integer', 'min:0'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:100', IModel::IGNORE_RULE],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'min_age_months' => ['sometimes', 'integer', 'min:0'],
            'max_age_months' => ['sometimes', 'integer', 'min:0'],
            'registration_fee' => ['sometimes', 'integer', 'min:0'],
            'total_tuition' => ['sometimes', 'integer', 'min:0'],
            'daily_meal_fee' => ['sometimes', 'integer', 'min:0'],
            'hourly_aftercare_fee' => ['sometimes', 'integer', 'min:0'],
            'weekly_rate' => ['sometimes', 'integer', 'min:0'],
            'capacity' => ['sometimes', 'integer', 'min:1'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function tranches(): HasMany
    {
        return $this->hasMany(SectionTranche::class, 'section_id')->orderBy('tranche_number');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class, 'section_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'section_id');
    }
}
