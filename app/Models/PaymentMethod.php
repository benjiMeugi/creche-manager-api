<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'requires_reference',
        'is_active',
        'description',
    ];

    protected $casts = [
        'requires_reference' => 'boolean',
        'is_active' => 'boolean',
    ];

    public $relation_methods = ['payments'];

    public function getMigrateKey()
    {
        return 'payment_method_id';
    }

    public function rules()
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:payment_methods,code'],
            'label' => ['required', 'string', 'max:255'],
            'requires_reference' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', 'unique:payment_methods,code,' . $this->id],
            'label' => ['sometimes', 'string', 'max:255'],
            'requires_reference' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_method_id');
    }
}
