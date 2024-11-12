<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasUuids;
    protected $keyType = 'string'; // Ensure it's a string (UUID)
    public $incrementing = false; // Disable auto-incrementing
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function Student()
    {
        return $this->belongsTo(Student::class);
    }

    public function Account()
    {
       return $this->hasOne(Account::class);
    }

    public function Administrator()
    {
        return $this->belongsTo(Administrator::class);
    }

    public function Invoice()
    {
       return $this->hasOne(Invoice::class);
    }

    public function Course()
    {
       return $this->hasOne(Course::class);
    }

    public function Instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}
