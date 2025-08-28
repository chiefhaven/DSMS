<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class Administrator extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public function User()
    {
       return $this->hasOne(User::class);
    }

    public function Expense()
    {
       return $this->hasMany(Expense::class);
    }

    public function BulkAttendances()
    {
       return $this->hasMany(bulkAttendance::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_activity')
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) =>
                "Administrator {$this->fname} {$this->sname} " .
                "{$eventName}."
            );
    }
}
