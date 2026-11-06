<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChildGuardian extends Model implements IModel
{
    use HasFactory;

    protected $table = 'child_guardian';

    protected $fillable = [
        'child_id',
        'guardian_id',
        'relationship_type_id',
        'is_primary_contact',
        'is_billing_contact',
        'is_emergency_contact',
        'is_authorized_pickup',
        'emergency_priority',
    ];

    protected $casts = [
        'child_id' => 'integer',
        'guardian_id' => 'integer',
        'relationship_type_id' => 'integer',
        'is_primary_contact' => 'boolean',
        'is_billing_contact' => 'boolean',
        'is_emergency_contact' => 'boolean',
        'is_authorized_pickup' => 'boolean',
        'emergency_priority' => 'integer',
    ];

    public $relation_methods = ['child', 'guardian', 'relationshipType'];

    public function getMigrateKey()
    {
        return 'child_guardian_id';
    }

    public function rules()
    {
        return [
            'child_id' => ['required', 'exists:children,id'],
            'guardian_id' => ['required', 'exists:guardians,id'],
            'relationship_type_id' => ['required', 'exists:relationship_types,id'],
            'is_primary_contact' => ['sometimes', 'boolean'],
            'is_billing_contact' => ['sometimes', 'boolean'],
            'is_emergency_contact' => ['sometimes', 'boolean'],
            'is_authorized_pickup' => ['sometimes', 'boolean'],
            'emergency_priority' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function update_rules()
    {
        return [
            'child_id' => ['sometimes', 'exists:children,id'],
            'guardian_id' => ['sometimes', 'exists:guardians,id'],
            'relationship_type_id' => ['sometimes', 'exists:relationship_types,id'],
            'is_primary_contact' => ['sometimes', 'boolean'],
            'is_billing_contact' => ['sometimes', 'boolean'],
            'is_emergency_contact' => ['sometimes', 'boolean'],
            'is_authorized_pickup' => ['sometimes', 'boolean'],
            'emergency_priority' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'guardian_id');
    }

    public function relationshipType(): BelongsTo
    {
        return $this->belongsTo(RelationshipType::class, 'relationship_type_id');
    }
}
