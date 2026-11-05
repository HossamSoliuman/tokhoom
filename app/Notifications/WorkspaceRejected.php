<?php

namespace App\Notifications;

use App\Models\Workspace;
use Illuminate\Notifications\Messages\MailMessage;

class WorkspaceRejected extends DashboardNotification
{
    public function __construct(public Workspace $workspace) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('تعذّر اعتماد '.$this->workspace->name)
            ->greeting('مرحباً '.$notifiable->name)
            ->line('راجعنا بيانات الترخيص ولم نتمكن من اعتمادها: '.$this->workspace->rejection_reason?->label().'.');

        if ($this->workspace->rejection_message) {
            $mail->line($this->workspace->rejection_message);
        }

        return $mail->line('يمكنك الرد على هذه الرسالة لتصحيح البيانات.');
    }

    protected function icon(): string
    {
        return 'ti-circle-x';
    }

    protected function tone(): string
    {
        return 'danger';
    }

    protected function text(): string
    {
        return 'تعذّر اعتماد '.$this->workspace->name.': '.$this->workspace->rejection_reason?->label();
    }

    protected function url(): string
    {
        return route('today');
    }
}
