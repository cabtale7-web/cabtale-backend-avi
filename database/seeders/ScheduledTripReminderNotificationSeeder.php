<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\BusinessManagement\Entities\FirebasePushNotification;

class ScheduledTripReminderNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $notifications = [
            [
                'name' => 'scheduled_trip_reminder',
                'title' => 'Upcoming Scheduled Trip',
                'value' => 'Your scheduled trip #{tripId} starts in 30 minutes. Please be ready!',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($notifications as $notification) {
            FirebasePushNotification::updateOrCreate(
                ['name' => $notification['name']],
                $notification
            );
        }
    }
}
