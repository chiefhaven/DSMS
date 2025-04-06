<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasUuids, HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes, LogsActivity;
    protected $keyType = 'string'; // Ensure it's a string (UUID)
    public $incrementing = false; // Disable auto-incrementing

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "User {$this->name} " .
                "has been {$eventName}."
            );
    }
}
