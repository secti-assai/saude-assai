<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\User;

class DoctorIdleNotification extends Notification
{
    use Queueable;

    public function __construct(public User $doctor, public int $idleMinutes)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'doctor_id' => $this->doctor->id,
            'doctor_name' => $this->doctor->name,
            'idle_minutes' => $this->idleMinutes,
            'message' => "Médico {$this->doctor->name} está ocioso há {$this->idleMinutes} minutos.",
        ];
    }
}
