<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingLevel extends Model
{
    use HasUuids, HasFactory, LogsActivity, SoftDeletes;

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults();
    }

    protected $keyType = 'string';
    public $incrementing = false;

    public function students()
    {
        return $this->hasMany(Student::class, 'trainingLevel_id');
    }
}
