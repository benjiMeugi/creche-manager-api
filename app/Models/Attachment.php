<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'document_type_id',
        'file_path',
        'original_name',
        'attachable_type',
        'attachable_id',
        'is_verified',
        'uploaded_by_user_id',
    ];

    protected $casts = [
        'document_type_id' => 'integer',
        'attachable_id' => 'integer',
        'is_verified' => 'boolean',
        'uploaded_by_user_id' => 'integer',
    ];

    public $relation_methods = ['attachable', 'documentType'];

    public function getMigrateKey()
    {
        return $this->getForeignKey();
    }

    /**
     * Règles de validation pour l'upload de pièce jointe.
     * Le client envoie "file" et "document_type_id", tandis que file_path,
     * attachable_type et attachable_id sont déterminés côté serveur.
     */
    public function rules()
    {
        return [
            'file' => ['required', 'file', 'max:10240'], // 10 Mo max
            'document_type_id' => ['nullable', 'exists:document_types,id'],
        ];
    }

    public function update_rules()
    {
        return [
            'document_type_id' => ['sometimes', 'nullable', 'exists:document_types,id'],
            'is_verified' => ['sometimes', 'boolean'],
        ];
    }

    public function attachable()
    {
        return $this->morphTo();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
}
