<?php

namespace App\Services;

use App\Models\Fleet;
use App\Models\User;
use App\Notifications\FleetDueNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class FleetDueNotificationService
{
    public function notify(
        Fleet $fleet,
        User $recipient,
        string $dueType,
        string $dueItem,
        string $notificationDate
    ): bool {
        $vehicleLabel = trim($fleet->car_brand_model . ' (' . $fleet->car_registration_number . ')');
        $url = $recipient->can('view fleet')
            ? route('fleet.show', $fleet)
            : route('notifications.index');
        $notification = new FleetDueNotification(
            (int) $fleet->getKey(),
            $vehicleLabel,
            $dueType,
            $dueItem,
            $url
        );

        try {
            $databaseNotificationSent = DB::transaction(function () use (
                $fleet,
                $recipient,
                $dueType,
                $notification,
                $notificationDate
            ) {
                $inserted = DB::table('fleet_due_notification_logs')->insertOrIgnore([
                    'fleet_id' => $fleet->getKey(),
                    'user_id' => $recipient->getKey(),
                    'due_type' => $dueType,
                    'notification_date' => $notificationDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($inserted === 0) {
                    return false;
                }

                Notification::sendNow($recipient, $notification, ['database']);

                return true;
            });
        } catch (Throwable $exception) {
            Log::error('Fleet due in-app notification failed.', [
                'fleet_id' => $fleet->getKey(),
                'recipient_id' => $recipient->getKey(),
                'exception' => $exception,
            ]);

            return false;
        }

        if (!$databaseNotificationSent) {
            return false;
        }

        if (!$recipient->email) {
            return true;
        }

        try {
            Notification::sendNow($recipient, $notification, ['mail']);
        } catch (Throwable $exception) {
            Log::error('Fleet due email notification failed.', [
                'fleet_id' => $fleet->getKey(),
                'recipient_id' => $recipient->getKey(),
                'exception' => $exception,
            ]);
        }

        return true;
    }
}
