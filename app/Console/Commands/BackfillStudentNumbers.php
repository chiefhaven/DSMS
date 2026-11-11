<?php

namespace App\Console\Commands;

use App\Services\StudentNumber;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillStudentNumbers extends Command
{
    protected $signature = 'students:backfill-numbers';
    protected $description = 'Assign student numbers using the earliest active enrollment date';

    public function handle(StudentNumber $numbers): int
    {
        $students = DB::table('students')
            ->join('invoices', 'invoices.student_id', '=', 'students.id')
            ->whereNull('students.student_number')
            ->whereNull('students.deleted_at')
            ->whereNull('invoices.deleted_at')
            ->select('students.id')
            ->selectRaw('MIN(invoices.created_at) as enrolled_at')
            ->groupBy('students.id')
            ->orderBy('enrolled_at')
            ->orderBy('students.id')
            ->get();

        foreach ($students as $student) {
            if (!$student->enrolled_at) {
                $this->error('Enrollment date missing for student '.$student->id);

                return self::FAILURE;
            }
            $numbers->assign($student->id, Carbon::parse($student->enrolled_at, date_default_timezone_get()));
        }

        $this->info('Assigned numbers to '.$students->count().' enrolled students.');

        return self::SUCCESS;
    }
}
