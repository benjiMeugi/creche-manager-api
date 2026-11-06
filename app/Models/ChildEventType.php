<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChildEventType extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'default_severity',
        'icon',
        'description',
    ];

    public $relation_methods = ['childEvents'];

    public function getMigrateKey()
    {
        return 'child_event_type_id';
    }

    public function rules()
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:' . $this->getTable() . ',code'],
            'label' => ['required', 'string', 'max:255'],
            'default_severity' => ['sometimes', 'string', 'in:info,attention,warning'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', IModel::IGNORE_RULE],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'default_severity' => ['sometimes', 'string', 'in:info,attention,warning'],
            'icon' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function childEvents(): HasMany
    {
        return $this->hasMany(ChildEvent::class, 'child_event_type_id');
    }
}
