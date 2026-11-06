<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ChildEvent extends Model implements IModel
{
    use HasFactory;

    public static array $SEVERITY_OPTIONS = [
        'info',
        'attention',
        'warning',
    ];

    protected $fillable = [
        'child_id',
        'child_event_type_id',
        'severity',
        'title',
        'description',
        'event_at',
        'metadata',
        'is_shared_with_parent',
        'supply_fulfilled_at',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'child_id' => 'integer',
        'child_event_type_id' => 'integer',
        'event_at' => 'datetime',
        'metadata' => 'array',
        'is_shared_with_parent' => 'boolean',
        'supply_fulfilled_at' => 'datetime',
        'recorded_by_user_id' => 'integer',
    ];

    public $relation_methods = ['child', 'eventType', 'attachments'];

    public function getMigrateKey()
    {
        return 'child_event_id';
    }

    public function rules()
    {
        return [
            'child_id' => ['required', 'exists:children,id'],
            'child_event_type_id' => ['required', 'exists:child_event_types,id'],
            'severity' => ['sometimes', 'in:' . implode(',', self::$SEVERITY_OPTIONS)],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'event_at' => ['required', 'date'],
            'metadata' => ['nullable', 'array'],
            'is_shared_with_parent' => ['sometimes', 'boolean'],
            'supply_fulfilled_at' => ['nullable', 'date'],
        ];
    }

    public function update_rules()
    {
        return [
            'child_id' => ['sometimes', 'exists:children,id'],
            'child_event_type_id' => ['sometimes', 'exists:child_event_types,id'],
            'severity' => ['sometimes', 'in:' . implode(',', self::$SEVERITY_OPTIONS)],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'event_at' => ['sometimes', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'is_shared_with_parent' => ['sometimes', 'boolean'],
            'supply_fulfilled_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(ChildEventType::class, 'child_event_type_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
