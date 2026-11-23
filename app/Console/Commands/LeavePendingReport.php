<?php

namespace App\Console\Commands;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class LeavePendingReport extends Command
{
    protected $signature = 'hrm:leave-pending {--days=0 : Highlight pending requests submitted at least this many days ago}';

    protected $description = 'Report pending HRM leave requests awaiting review';

    public function handle(): int
    {
        if (!Schema::hasTable('leave_requests')) {
            $this->warn('Leave requests table is not available yet. Run migrations before using this command.');
            return self::SUCCESS;
        }

        $highlightDays = max(0, (int) $this->option('days'));

        $requests = LeaveRequest::query()
            ->with(['instructor', 'leaveType'])
            ->where('status', 'pending')
            ->orderBy('start_date')
            ->get();

        $this->info('Pending leave requests: ' . $requests->count());

        if ($requests->isEmpty()) {
            $this->line('No pending leave requests found.');
            return self::SUCCESS;
        }

        $this->table(
            ['Employee', 'Type', 'Start', 'End', 'Days', 'Submitted'],
            $requests->map(function ($request) use ($highlightDays) {
                $submitted = $request->created_at ? Carbon::parse($request->created_at) : null;
                $ageDays = $submitted ? $submitted->diffInDays(now()) : null;
                $submittedLabel = $submitted ? $submitted->format('Y-m-d') : '-';
                if ($highlightDays > 0 && $ageDays !== null && $ageDays >= $highlightDays) {
                    $submittedLabel .= ' (! ' . $ageDays . 'd)';
                }

                $name = '-';
                if ($request->relationLoaded('instructor') && $request->instructor) {
                    $name = trim($request->instructor->fname . ' ' . $request->instructor->sname);
                }

                return [
                    $name ?: '-',
                    $request->leaveType->name ?? 'Unspecified',
                    optional($request->start_date)->format('Y-m-d') ?? (string) $request->start_date,
                    optional($request->end_date)->format('Y-m-d') ?? (string) $request->end_date,
                    $request->days ?? '-',
                    $submittedLabel,
                ];
            })->all()
        );

        return self::SUCCESS;
    }
}
