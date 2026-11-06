<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingConfiguration extends Model implements IModel
{
    use HasFactory;

    public static array $BILLING_UNITS = [
        'fixed',
        'per_hour',
        'per_day',
        'per_week',
        'per_month',
        'per_year',
    ];

    protected $fillable = [
        'code',
        'label',
        'amount',
        'currency',
        'billing_unit',
        'effective_date',
        'is_active',
        'description',
    ];

    protected $casts = [
        'amount' => 'integer',
        'effective_date' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public $relation_methods = [];

    public function getMigrateKey()
    {
        return 'pricing_configuration_id';
    }

    public function rules()
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:pricing_configurations,code'],
            'label' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'billing_unit' => ['sometimes', 'in:' . implode(',', self::$BILLING_UNITS)],
            'effective_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', 'unique:pricing_configurations,code,' . $this->id],
            'label' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'max:10'],
            'billing_unit' => ['sometimes', 'in:' . implode(',', self::$BILLING_UNITS)],
            'effective_date' => ['sometimes', 'nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
