<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $casts = [
        'attendance_time_stop' => 'datetime',
        'attendance_time_start' => 'datetime',
        'paye_settings' => 'array',
        'pension_settings' => 'array',
    ];

    public function District()
    {
       return $this->belongsTo(District::class);
    }

    public static function payeConfig(): array
    {
        $defaults = (array) config('payroll.paye', []);
        $settings = static::query()->value('paye_settings');

        if (!is_array($settings)) {
            $settings = [];
        }

        $enabled = array_key_exists('enabled', $settings)
            ? (bool) $settings['enabled']
            : (bool) ($defaults['enabled'] ?? true);

        $rate = array_key_exists('rate', $settings) && is_numeric($settings['rate'])
            ? (float) $settings['rate']
            : (float) ($defaults['rate'] ?? 0);

        $bands = array_key_exists('bands', $settings) && is_array($settings['bands'])
            ? $settings['bands']
            : ($defaults['bands'] ?? []);
        if (!is_array($bands)) {
            $bands = [];
        }

        $normalizedBands = [];
        foreach ($bands as $band) {
            if (!is_array($band)) {
                continue;
            }
            $bandRate = $band['rate'] ?? null;
            if ($bandRate === null || $bandRate === '' || !is_numeric($bandRate)) {
                continue;
            }
            $upTo = $band['up_to'] ?? null;
            if ($upTo === '' || $upTo === null) {
                $upTo = null;
            } elseif (is_numeric($upTo)) {
                $upTo = (float) $upTo;
            } else {
                continue;
            }
            $normalizedBands[] = [
                'up_to' => $upTo,
                'rate' => (float) $bandRate,
            ];
        }

        return [
            'enabled' => $enabled,
            'rate' => $rate,
            'bands' => $normalizedBands,
        ];
    }

    public static function pensionConfig(): array
    {
        $defaults = (array) config('payroll.pension', []);
        $settings = static::query()->value('pension_settings');

        if (!is_array($settings)) {
            $settings = [];
        }

        $enabled = array_key_exists('enabled', $settings)
            ? (bool) $settings['enabled']
            : (bool) ($defaults['enabled'] ?? true);

        $employeeRate = array_key_exists('employee_rate', $settings) && is_numeric($settings['employee_rate'])
            ? (float) $settings['employee_rate']
            : (float) ($defaults['employee_rate'] ?? 0.05);

        $employerRate = array_key_exists('employer_rate', $settings) && is_numeric($settings['employer_rate'])
            ? (float) $settings['employer_rate']
            : (float) ($defaults['employer_rate'] ?? 0.10);

        return [
            'enabled' => $enabled,
            'employee_rate' => max(0.0, $employeeRate),
            'employer_rate' => max(0.0, $employerRate),
            'calculation_base' => 'basic_salary',
        ];
    }
}
