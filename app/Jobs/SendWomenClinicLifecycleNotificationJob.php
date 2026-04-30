<?php

namespace App\Jobs;

use App\Models\WomenClinicAppointment;
use App\Services\WomenClinicNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendWomenClinicLifecycleNotificationJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    public const TRIGGER_SCHEDULED = 'SCHEDULED';
    public const TRIGGER_REMINDER_24H = 'REMINDER_24H';
    public const TRIGGER_CHECKIN = 'CHECKIN';
    public const TRIGGER_CHECKOUT = 'CHECKOUT';
    public const TRIGGER_CANCELLED = 'CANCELLED';
    public const TRIGGER_RESCHEDULED = 'RESCHEDULED';
    public const TRIGGER_EDITED = 'EDITED';

    public int $tries = 5;

    /**
     * @return array<int,int>
     */
    public function backoff(): array
    {
        return [30, 120, 300, 600, 1200];
    }

    public function __construct(
        public string $appointmentId,
        public string $trigger,
    ) {
    }

    public function handle(WomenClinicNotificationService $notifications): void
    {
        $appointment = WomenClinicAppointment::with('citizen')->find($this->appointmentId);

        if (! $appointment || ! $appointment->citizen) {
            return;
        }

        $trigger = strtoupper(trim($this->trigger));

        match ($trigger) {
            self::TRIGGER_SCHEDULED => $notifications->sendScheduled($appointment),
            self::TRIGGER_REMINDER_24H => $notifications->sendReminder24hWithCancelLink($appointment),
            self::TRIGGER_CHECKIN => $notifications->sendCheckIn($appointment),
            self::TRIGGER_CHECKOUT => $notifications->sendCheckOutAndFeedback($appointment),
            self::TRIGGER_CANCELLED => $notifications->sendCancelled($appointment),
            self::TRIGGER_RESCHEDULED => $notifications->sendRescheduled($appointment),
            self::TRIGGER_EDITED => $notifications->sendEdited($appointment),
            default => null,
        };
    }
}
