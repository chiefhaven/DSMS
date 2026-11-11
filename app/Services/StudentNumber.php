<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Student;

class StudentNumber
{
    public function assign(string $studentId, CarbonInterface $enrolledAt): string
    {
        $date = $enrolledAt->copy()->timezone(date_default_timezone_get());

        return DB::transaction(function () use ($studentId, $date) {
            $student = DB::table('students')->where('id', $studentId)->lockForUpdate()->first();

            if (!$student) {
                throw (new ModelNotFoundException)->setModel(Student::class, [$studentId]);
            }

            if ($student->student_number !== null) {
                return $student->student_number;
            }

            $day = $date->toDateString();
            DB::table('student_number_sequences')->insertOrIgnore(['date' => $day, 'last_number' => 0]);
            $sequence = DB::table('student_number_sequences')->where('date', $day)->lockForUpdate()->first();
            $next = $sequence->last_number + 1;
            $number = $date->format('Y').str_pad((string) $date->dayOfYear, 3, '0', STR_PAD_LEFT)
                .str_pad((string) $next, 3, '0', STR_PAD_LEFT);

            DB::table('student_number_sequences')->where('date', $day)->update(['last_number' => $next]);
            DB::table('students')->where('id', $studentId)->update(['student_number' => $number]);

            return $number;
        }, 5);
    }
}
