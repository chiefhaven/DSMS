<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class expense extends Model
{
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    public function Students()
    {
        return $this->belongsToMany(Student::class)->withPivot('expense_type');
    }

    public function Administrator()
    {
        return $this->belongsTo(Administrator::class, 'added_by');
    }

    //delete relationships!
    protected static function booted () {
        static::deleting(function(Expense $expense) {
             $expense->student()->detach();
        });
    }
}
