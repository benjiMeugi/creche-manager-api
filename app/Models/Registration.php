<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Registration extends Model implements IModel
{
    use HasFactory;

    public static array $STATUS_OPTIONS = [
        'draft',
        'submitted',
        'under_review',
        'approved',
        'rejected',
    ];

    protected $fillable = [
        'registration_number',
        'academic_year',
        'child_id',
        'section_id',
        'status',
        'consent_first_aid',
        'consent_photos_videos',
        'consent_emergency_pickup',
        'subscribe_daily_meal',
        'subscribe_aftercare',
        'subscribe_weekly_package',
        'declarant_name',
        'signed_at_place',
        'signed_date',
        'rules_accepted',
        'admission_date',
        'admin_observations',
        'decision_comment',
        'decision_datetime',
        'created_by_user_id',
        'processed_by_user_id',
    ];

    protected $casts = [
        'child_id' => 'integer',
        'section_id' => 'integer',
        'consent_first_aid' => 'boolean',
        'consent_photos_videos' => 'boolean',
        'consent_emergency_pickup' => 'boolean',
        'subscribe_daily_meal' => 'boolean',
        'subscribe_aftercare' => 'boolean',
        'subscribe_weekly_package' => 'boolean',
        'rules_accepted' => 'boolean',
        'signed_date' => 'date:Y-m-d',
        'admission_date' => 'date:Y-m-d',
        'decision_datetime' => 'datetime',
        'created_by_user_id' => 'integer',
        'processed_by_user_id' => 'integer',
    ];

    public $relation_methods = [
        'child',
        'section',
        'tuitionInstallments',
        'additionalCharges',
        'attachments',
    ];

    public function getMigrateKey()
    {
        return 'registration_id';
    }

    public function rules()
    {
        return [
            'registration_number' => ['nullable', 'string', 'max:100', 'unique:' . $this->getTable() . ',registration_number'],
            'academic_year' => ['sometimes', 'string', 'max:20'],
            'child_id' => ['required', 'exists:children,id'],
            'section_id' => ['nullable', 'exists:sections,id'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'consent_first_aid' => ['required', 'boolean'],
            'consent_photos_videos' => ['required', 'boolean'],
            'consent_emergency_pickup' => ['required', 'boolean'],
            'subscribe_daily_meal' => ['sometimes', 'boolean'],
            'subscribe_aftercare' => ['sometimes', 'boolean'],
            'subscribe_weekly_package' => ['sometimes', 'boolean'],
            'declarant_name' => ['required', 'string', 'max:255'],
            'signed_at_place' => ['nullable', 'string', 'max:255'],
            'signed_date' => ['nullable', 'date'],
            'rules_accepted' => ['sometimes', 'boolean'],
            'admission_date' => ['nullable', 'date'],
            'admin_observations' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:100', IModel::IGNORE_RULE],
            'academic_year' => ['sometimes', 'string', 'max:20'],
            'child_id' => ['sometimes', 'exists:children,id'],
            'section_id' => ['sometimes', 'nullable', 'exists:sections,id'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'consent_first_aid' => ['sometimes', 'boolean'],
            'consent_photos_videos' => ['sometimes', 'boolean'],
            'consent_emergency_pickup' => ['sometimes', 'boolean'],
            'subscribe_daily_meal' => ['sometimes', 'boolean'],
            'subscribe_aftercare' => ['sometimes', 'boolean'],
            'subscribe_weekly_package' => ['sometimes', 'boolean'],
            'declarant_name' => ['sometimes', 'string', 'max:255'],
            'signed_at_place' => ['sometimes', 'nullable', 'string', 'max:255'],
            'signed_date' => ['sometimes', 'nullable', 'date'],
            'rules_accepted' => ['sometimes', 'boolean'],
            'admission_date' => ['sometimes', 'nullable', 'date'],
            'admin_observations' => ['sometimes', 'nullable', 'string'],
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Registration $registration) {
            if (empty($registration->registration_number)) {
                $year = now()->format('Y');
                $nextId = (static::max('id') ?? 0) + 1;
                $registration->registration_number = sprintf('PL-%s-%04d', $year, $nextId);
            }

            if (empty($registration->academic_year)) {
                $year = (int) now()->format('Y');
                $registration->academic_year = $year . '-' . ($year + 1);
            }

            if (empty($registration->signed_date)) {
                $registration->signed_date = now()->toDateString();
            }
        });
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function tuitionInstallments(): HasMany
    {
        return $this->hasMany(TuitionInstallment::class, 'registration_id');
    }

    public function additionalCharges(): HasMany
    {
        return $this->hasMany(AdditionalCharge::class, 'registration_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
