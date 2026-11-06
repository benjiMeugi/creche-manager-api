<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model implements IModel
{
    use HasFactory;

    public static array $STATUS_OPTIONS = [
        'present',
        'absent',
        'excused',
        'late',
    ];

    protected $fillable = [
        'child_id',
        'attendance_date',
        'status',
        'check_in_at',
        'check_out_at',
        'picked_up_by_guardian_id',
        'third_party_pickup_name',
        'third_party_id_card',
        'dropped_off_by',
        'overtime_minutes',
        'overtime_hours_billed',
        'overtime_amount',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'child_id' => 'integer',
        'attendance_date' => 'date:Y-m-d',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'picked_up_by_guardian_id' => 'integer',
        'overtime_minutes' => 'integer',
        'overtime_hours_billed' => 'integer',
        'overtime_amount' => 'integer',
        'recorded_by_user_id' => 'integer',
    ];

    public $relation_methods = ['child', 'pickedUpByGuardian'];

    public function getMigrateKey()
    {
        return 'attendance_id';
    }

    public function rules()
    {
        return [
            'child_id' => ['required', 'exists:children,id'],
            'attendance_date' => ['required', 'date'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'check_in_at' => ['nullable', 'date'],
            'check_out_at' => ['nullable', 'date'],
            'picked_up_by_guardian_id' => ['nullable', 'exists:guardians,id'],
            'third_party_pickup_name' => ['nullable', 'string', 'max:255'],
            'third_party_id_card' => ['nullable', 'string', 'max:255'],
            'dropped_off_by' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'child_id' => ['sometimes', 'exists:children,id'],
            'attendance_date' => ['sometimes', 'date'],
            'status' => ['sometimes', 'in:' . implode(',', self::$STATUS_OPTIONS)],
            'check_in_at' => ['sometimes', 'nullable', 'date'],
            'check_out_at' => ['sometimes', 'nullable', 'date'],
            'picked_up_by_guardian_id' => ['sometimes', 'nullable', 'exists:guardians,id'],
            'third_party_pickup_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'third_party_id_card' => ['sometimes', 'nullable', 'string', 'max:255'],
            'dropped_off_by' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }

    /**
     * Calcule automatiquement les minutes et frais de dépassement selon les horaires
     * réglementaires (table operating_schedules) et la grille tarifaire (table pricing_configurations).
     */
    public static function calculateOvertime(mixed $checkOutAt, ?int $hourlyRate = null): array
    {
        if (! $checkOutAt) {
            return [
                'overtime_minutes' => 0,
                'overtime_hours_billed' => 0,
                'overtime_amount' => 0,
            ];
        }

        $checkout = $checkOutAt instanceof Carbon ? $checkOutAt : Carbon::parse($checkOutAt);
        $dayOfWeek = (int) $checkout->dayOfWeekIso; // 1 (Lundi) à 7 (Dimanche)

        // Récupération dynamique de l'horaire de fermeture du jour depuis la base
        $schedule = null;
        try {
            $schedule = OperatingSchedule::where('day_of_week', $dayOfWeek)->first();
        } catch (\Throwable $e) {
            // Ignorer si la table n'est pas encore migrée
        }

        $closingTimeStr = $schedule?->closing_time ?? (string) config('services.creche.closing_time', '18:30');
        $graceMinutes = $schedule?->overtime_grace_minutes ?? 0;
        [$closingHour, $closingMinute] = array_map('intval', explode(':', substr($closingTimeStr, 0, 5)));

        // Récupération dynamique du taux horaire de garderie du soir
        if ($hourlyRate === null) {
            $priceConfig = null;
            try {
                $priceConfig = PricingConfiguration::where('code', 'overtime_hourly')->where('is_active', true)->first();
            } catch (\Throwable $e) {
                // Ignorer si la table n'est pas encore migrée
            }
            $rate = $priceConfig ? (int) $priceConfig->amount : (int) config('services.creche.overtime_hourly_rate', 2000);
        } else {
            $rate = $hourlyRate;
        }

        $closing = $checkout->copy()->setTime($closingHour, $closingMinute, 0);

        if ($checkout->lte($closing->copy()->addMinutes($graceMinutes))) {
            return [
                'overtime_minutes' => 0,
                'overtime_hours_billed' => 0,
                'overtime_amount' => 0,
            ];
        }

        $minutes = (int) $closing->diffInMinutes($checkout);
        $hoursBilled = (int) ceil($minutes / 60);

        return [
            'overtime_minutes' => $minutes,
            'overtime_hours_billed' => $hoursBilled,
            'overtime_amount' => $hoursBilled * $rate,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Attendance $attendance) {
            if ($attendance->check_out_at) {
                $overtime = static::calculateOvertime($attendance->check_out_at);
                $attendance->overtime_minutes = $overtime['overtime_minutes'];
                $attendance->overtime_hours_billed = $overtime['overtime_hours_billed'];
                $attendance->overtime_amount = $overtime['overtime_amount'];
            }
        });
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class, 'child_id');
    }

    public function pickedUpByGuardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class, 'picked_up_by_guardian_id');
    }
}
