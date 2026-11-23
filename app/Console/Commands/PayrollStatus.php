<?php

namespace App\Console\Commands;

use App\Models\Payroll;
use App\Models\PayrollGroup;
use Carbon\Carbon;
use Illuminate\Console\Command;

class PayrollStatus extends Command
{
    protected $signature = 'hrm:payroll-status {--month= : Payroll month to report (YYYY-MM), defaults to current month}';

    protected $description = 'Report HRM payroll group and payroll record status for a month';

    public function handle(): int
    {
        $month = $this->option('month') ?: Carbon::now()->format('Y-m');
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $this->error('Invalid --month value. Expected YYYY-MM.');
            return self::FAILURE;
        }

        $groups = PayrollGroup::query()
            ->where('payment_month', $month)
            ->withCount([
                'payrolls',
                'payrolls as paid_count' => fn ($query) => $query->whereRaw("LOWER(status) = 'paid'"),
                'payrolls as final_count' => fn ($query) => $query->whereRaw("LOWER(status) = 'final'"),
                'payrolls as pending_count' => fn ($query) => $query->whereRaw("LOWER(status) = 'pending'"),
            ])
            ->withSum('payrolls as net_pay_sum', 'net_pay')
            ->orderBy('name')
            ->get();

        $payrolls = Payroll::query()->where('payment_month', $month);

        $this->info("HRM payroll status for {$month}");
        $this->line('Groups: ' . $groups->count());
        $this->line('Payroll records: ' . (clone $payrolls)->count());
        $this->line('Pending: ' . (clone $payrolls)->whereRaw("LOWER(status) = 'pending'")->count());
        $this->line('Final: ' . (clone $payrolls)->whereRaw("LOWER(status) = 'final'")->count());
        $this->line('Paid: ' . (clone $payrolls)->whereRaw("LOWER(status) = 'paid'")->count());
        $this->line('Total net pay: K' . number_format((float) (clone $payrolls)->sum('net_pay'), 2));

        if ($groups->isEmpty()) {
            $this->warn("No payroll groups found for {$month}.");
            return self::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['Group', 'Employees', 'Pending', 'Final', 'Paid', 'Net Pay'],
            $groups->map(fn ($group) => [
                $group->name,
                $group->payrolls_count,
                $group->pending_count,
                $group->final_count,
                $group->paid_count,
                'K' . number_format((float) ($group->net_pay_sum ?? 0), 2),
            ])->all()
        );

        $unpaidGroups = $groups->filter(fn ($group) => (int) $group->paid_count < (int) $group->payrolls_count);
        if ($unpaidGroups->isNotEmpty()) {
            $this->warn($unpaidGroups->count() . ' group(s) still have unpaid payroll records.');
        }

        return self::SUCCESS;
    }
}
