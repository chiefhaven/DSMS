<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetTracker extends Model
{
    /** @use HasFactory<\Database\Factories\FleetTrackerFactory> */
    use HasFactory;

    protected $fillable = [
        'mbirasync_id', 'device_id', 'protocol',
        'device_time', 'fix_time',
        'latitude', 'longitude', 'altitude',
        'speed', 'course', 'valid', 'attributes',
    ];

    protected $casts = [
        'attributes'  => 'array',
        'device_time' => 'datetime',
        'fix_time'    => 'datetime',
        'valid'       => 'boolean',
    ];

    public function Fleet()
    {
       return $this->belongsTo(Fleet::class, 'device_id', 'tracker_device_id');
    }
}
