<?php

namespace App\Models;

use BenjiMeugi\Contracts\IModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperatingSchedule extends Model implements IModel
{
    use HasFactory;

    protected $fillable = [
        'day_of_week',
        'day_name',
        'opening_time',
        'closing_time',
        'overtime_grace_minutes',
        'is_open',
        'description',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'overtime_grace_minutes' => 'integer',
        'is_open' => 'boolean',
    ];

    public $relation_methods = [];

    public function getMigrateKey()
    {
        return 'operating_schedule_id';
    }

    public function rules()
    {
        return [
            'day_of_week' => ['required', 'integer', 'between:1,7', 'unique:operating_schedules,day_of_week'],
            'day_name' => ['required', 'string', 'max:50'],
            'opening_time' => ['required', 'date_format:H:i:s'],
            'closing_time' => ['required', 'date_format:H:i:s'],
            'overtime_grace_minutes' => ['sometimes', 'integer', 'min:0'],
            'is_open' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function update_rules()
    {
        return [
            'day_of_week' => ['sometimes', 'integer', 'between:1,7', 'unique:operating_schedules,day_of_week,' . $this->id],
            'day_name' => ['sometimes', 'string', 'max:50'],
            'opening_time' => ['sometimes', 'date_format:H:i:s'],
            'closing_time' => ['sometimes', 'date_format:H:i:s'],
            'overtime_grace_minutes' => ['sometimes', 'integer', 'min:0'],
            'is_open' => ['sometimes', 'boolean'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
