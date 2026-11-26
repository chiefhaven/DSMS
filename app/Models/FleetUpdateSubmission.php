<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetUpdateSubmission extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'fleet_id',
        'submitted_by',
        'odometer_km',
        'odometer_reading_date',
        'service_date',
        'roadworthiness_expiry',
        'insurance_expiry',
        'odometer_picture_path',
        'service_date_picture_path',
        'roadworthiness_picture_path',
        'insurance_picture_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    protected $casts = [
        'odometer_km' => 'integer',
        'odometer_reading_date' => 'date',
        'service_date' => 'date',
        'roadworthiness_expiry' => 'date',
        'insurance_expiry' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function fleet()
    {
        return $this->belongsTo(Fleet::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
