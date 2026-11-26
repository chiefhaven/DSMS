<?php

namespace App\Console\Commands;

use App\Models\Fleet;
use App\Models\User;
use App\Services\FleetDueNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class NotifyFleetDues extends Command
{
    protected $signature = 'fleet:notify-dues';

    protected $description = 'Notify instructors and permitted administrators about fleet dues';

    public function handle(FleetDueNotificationService $notifications): int
    {
        $today = Carbon::today('Africa/Harare');
        $dueThrough = $today->copy()->addDays(20);
        $notificationDate = $today->toDateString();
        $admins = User::permission('receive fleet due notifications')->get();
        $fleets = Fleet::query()
            ->with('Instructor.user')
            ->where(function ($query) use ($dueThrough) {
                $query->whereDate('insurance_expiry', '<=', $dueThrough->toDateString())
                    ->orWhereDate('roadworthiness_expiry', '<=', $dueThrough->toDateString())
                    ->orWhere(function ($serviceQuery) {
                        $serviceQuery->whereNotNull('odometer_km')
                            ->whereNotNull('next_service_km')
                            ->whereColumn('odometer_km', '>=', 'next_service_km');
                    });
            })
            ->get();
        $sent = 0;

        foreach ($fleets as $fleet) {
            $dueItems = $this->dueItems($fleet, $today, $dueThrough);
            if ($dueItems === []) {
                continue;
            }

            $recipients = collect([$fleet->Instructor?->user])
                ->filter()
                ->merge($admins)
                ->unique('id');

            foreach ($recipients as $recipient) {
                foreach ($dueItems as $dueType => $dueItem) {
                    if ($notifications->notify($fleet, $recipient, $dueType, $dueItem, $notificationDate)) {
                        $sent++;
                    }
                }
            }
        }

        $this->info("Fleet due notifications sent: {$sent}");

        return self::SUCCESS;
    }

    private function dueItems(Fleet $fleet, Carbon $today, Carbon $dueThrough): array
    {
        $items = [];

        foreach ([
            'insurance_expiry' => 'Insurance',
            'roadworthiness_expiry' => 'Roadworthiness',
        ] as $field => $label) {
            $date = $fleet->{$field};
            if (!$date || $date->gt($dueThrough)) {
                continue;
            }

            $items[$field] = $date->lt($today)
                ? $label . ' expired on ' . $date->format('j M Y')
                : $label . ' due by ' . $date->format('j M Y');
        }

        if (
            $fleet->odometer_km !== null
            && $fleet->next_service_km !== null
            && $fleet->odometer_km >= $fleet->next_service_km
        ) {
            $items['service'] = 'Service due at ' . number_format($fleet->next_service_km)
                . ' km (current odometer ' . number_format($fleet->odometer_km) . ' km)';
        }

        return $items;
    }
}
