<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guardian extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'profession',
        'phone',
        'phone_secondary',
        'email',
        'address',
    ];

    public $relation_methods = ['children', 'childGuardians', 'payments'];

    public function getMigrateKey()
    {
        return 'guardian_id';
    }

    public function rules()
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'profession' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'phone_secondary' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function update_rules()
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'profession' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:50'],
            'phone_secondary' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function childGuardians(): HasMany
    {
        return $this->hasMany(ChildGuardian::class, 'guardian_id');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_guardian', 'guardian_id', 'child_id')
            ->withPivot([
                'relationship_type_id',
                'is_primary_contact',
                'is_billing_contact',
                'is_emergency_contact',
                'is_authorized_pickup',
                'emergency_priority',
            ])
            ->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payer_guardian_id');
    }
}
