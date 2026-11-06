<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'code',
        'label',
        'is_mandatory_for_registration',
        'description',
    ];

    protected $casts = [
        'is_mandatory_for_registration' => 'boolean',
    ];

    public $relation_methods = ['attachments'];

    public function getMigrateKey()
    {
        return 'document_type_id';
    }

    public function rules()
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:' . $this->getTable() . ',code'],
            'label' => ['required', 'string', 'max:255'],
            'is_mandatory_for_registration' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'code' => ['sometimes', 'required', 'string', 'max:50', IModel::IGNORE_RULE],
            'label' => ['sometimes', 'required', 'string', 'max:255'],
            'is_mandatory_for_registration' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'document_type_id');
    }
}
