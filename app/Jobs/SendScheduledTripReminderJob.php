<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\TripManagement\Service\Interface\TripRequestServiceInterface;

class SendScheduledTripReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public function __construct(public string|int $tripRequestId) {}

    public function handle(TripRequestServiceInterface $tripRequestService): void
    {
        $trip = $tripRequestService->findOne(
            id: $this->tripRequestId, 
            relations: ['driver', 'customer', 'coordinate']
        );

        if (!$trip) return;

        // Only send reminder if trip is still pending/accepted and has assigned driver
        if (!in_array($trip->current_status, ['pending', 'accepted'])) return;
        if (!$trip->driver_id) return;
        if (!$trip->driver?->fcm_token) return;

        // Check if scheduled time is still in future
        if (!$trip->scheduled_at || Carbon::parse($trip->scheduled_at)->isPast()) return;

        $push = getNotification('scheduled_trip_reminder');
        
        sendDeviceNotification(
            fcm_token: $trip->driver->fcm_token,
            title: translate($push['title']),
            description: translate(textVariableDataFormat(
                value: $push['description'],
                tripId: $trip->ref_id
            )),
            ride_request_id: $trip->id,
            type: $trip->type,
            action: 'scheduled_trip_reminder',
            user_id: $trip->driver->id
        );
    }
}
