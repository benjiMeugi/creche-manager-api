<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Language extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
    ];

    public $relation_methods = ['children'];

    public function getMigrateKey()
    {
        return 'language_id';
    }

    public function rules()
    {
        return [
            'code' => ['required', 'string', 'max:20', 'unique:' . $this->getTable() . ',code'],
            'label' => ['required', 'string', 'max:255'],
        ];
    }

    public function update_rules()
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:20', IModel::IGNORE_RULE],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Child::class, 'child_language', 'language_id', 'child_id')->withTimestamps();
    }
}
