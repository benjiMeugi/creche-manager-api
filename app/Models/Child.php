<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Child extends Model implements IModel
{
    use HasFactory;

    public static array $SEX_OPTIONS = ['M', 'F'];

    public static array $STATUS_OPTIONS = ['pending', 'active', 'suspended', 'archived'];

    protected $fillable = [
        'registration_number',
        'first_name',
        'last_name',
        'birth_date',
        'sex',
        'birth_place',
        'dietary_habits',
        'sleep_habits',
        'other_useful_info',
        'status',
        'created_by_user_id',
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'created_by_user_id' => 'integer',
    ];

    protected $appends = ['age_in_months', 'full_name'];

    public $relation_methods = [
        'section',
        'languages',
        'guardians',
        'childGuardians',
        'medicalProfile',
        'registrations',
        'attendances',
        'childEvents',
        'additionalCharges',
        'attachments',
    ];

    public function getMigrateKey()
    {
        return 'child_id';
    }

    public function rules()
    {
        return [
            'registration_number' => ['nullable', 'string', 'max:100', 'unique:' . $this->getTable() . ',registration_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'sex' => ['required', 'in:' . implode(',', self::$SEX_OPTIONS)],
            'birth_place' => ['required', 'string', 'max:255'],
            'dietary_habits' => ['nullable', 'string'],
            'sleep_habits' => ['nullable', 'string'],
            'other_useful_info' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'language_ids' => ['sometimes', 'array'],
            'language_ids.*' => ['integer', 'exists:languages,id'],
        ];
    }

    public function update_rules()
    {
        return [
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:100', IModel::IGNORE_RULE],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'date'],
            'sex' => ['sometimes', 'in:' . implode(',', self::$SEX_OPTIONS)],
            'birth_place' => ['sometimes', 'string', 'max:255'],
            'dietary_habits' => ['sometimes', 'nullable', 'string'],
            'sleep_habits' => ['sometimes', 'nullable', 'string'],
            'other_useful_info' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'language_ids' => ['sometimes', 'array'],
            'language_ids.*' => ['integer', 'exists:languages,id'],
        ];
    }

    public function getAgeInMonthsAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return (int) Carbon::parse($this->birth_date)->diffInMonths(now());
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->last_name . ' ' . $this->first_name);
    }

    /**
     * Retourne la section de l'enfant déduite de son inscription.
     */
    public function section(): HasOneThrough
    {
        return $this->hasOneThrough(
            Section::class,
            Registration::class,
            'child_id',
            'id',
            'id',
            'section_id'
        );
    }

    protected static function booted(): void
    {
        static::creating(function (Child $child) {
            if (empty($child->registration_number)) {
                $year = now()->format('Y');
                $nextId = (static::max('id') ?? 0) + 1;
                $child->registration_number = sprintf('ENF-%s-%04d', $year, $nextId);
            }
        });
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Language::class, 'child_language', 'child_id', 'language_id')->withTimestamps();
    }

    public function childGuardians(): HasMany
    {
        return $this->hasMany(ChildGuardian::class, 'child_id');
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'child_guardian', 'child_id', 'guardian_id')
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

    public function medicalProfile(): HasOne
    {
        return $this->hasOne(ChildMedicalProfile::class, 'child_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'child_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'child_id');
    }

    public function childEvents(): HasMany
    {
        return $this->hasMany(ChildEvent::class, 'child_id');
    }

    public function additionalCharges(): HasMany
    {
        return $this->hasMany(AdditionalCharge::class, 'child_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
