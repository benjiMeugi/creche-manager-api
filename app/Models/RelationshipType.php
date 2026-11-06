<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RelationshipType extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'description',
    ];

    public $relation_methods = ['guardians'];

    public function getMigrateKey()
    {
        return 'relationship_type_id';
    }

    public function rules()
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:' . $this->getTable() . ',code'],
            'label' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function update_rules()
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', IModel::IGNORE_RULE],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class, 'relationship_type_id');
    }
}
