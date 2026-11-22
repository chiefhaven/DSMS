<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoEndContracts extends Command
{
    protected $signature = 'staff:auto-end-contracts {--date= : Date to evaluate (Y-m-d), defaults to today in app timezone}';

    protected $description = 'Mark staff with reached contract end dates as Contract ended';

    public function handle(): int
    {
        $dateOption = $this->option('date');
        $asOf = $dateOption ? Carbon::parse($dateOption)->toDateString() : Carbon::today()->toDateString();

        $tables = [
            'administrators',
            'instructors',
        ];

        $total = 0;

        foreach ($tables as $table) {
            $updated = DB::table($table)
                ->whereNull('deleted_at')
                ->whereNotNull('end_date')
                ->whereDate('end_date', '<=', $asOf)
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhereRaw('LOWER(status) <> ?', ['contract ended']);
                })
                ->update([
                    'status' => 'Contract ended',
                    'updated_at' => now(),
                ]);

            $total += $updated;
            $this->line(sprintf('%s: %d updated', $table, $updated));
        }

        $this->info(sprintf('Auto contract end complete as of %s. Total updated: %d', $asOf, $total));

        return self::SUCCESS;
    }
}
