<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleTracker extends Model
{
    use HasFactory;
    protected $fillable = ['fleet_id', 'user_id', 'latitude', 'longitude'];
}
