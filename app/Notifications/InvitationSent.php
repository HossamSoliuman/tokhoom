<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * The invitation email a new team member receives.
 */
class InvitationSent extends Notification
{
    public function __construct(public Invitation $invitation) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $workspace = $this->invitation->workspace;

        return (new MailMessage)
            ->subject('دعوة للانضمام إلى '.$workspace->name)
            ->line(($this->invitation->inviter?->name ?? $workspace->name).' يدعوك للانضمام إلى '.$workspace->name.' على تخوم.')
            ->action('قبول الدعوة', route('invitations.accept', $this->invitation->token));
    }
}
