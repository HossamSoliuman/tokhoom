<?php

namespace App\Notifications;

use App\Models\Workspace;
use Illuminate\Notifications\Messages\MailMessage;

class WorkspaceApproved extends DashboardNotification
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
        return (new MailMessage)
            ->subject('تم اعتماد '.$this->workspace->name)
            ->greeting('مرحباً '.$notifiable->name)
            ->line('اعتمدنا بيانات الترخيص، وأصبح موقعك ظاهراً للزوار على '.$this->workspace->siteHost().'.')
            ->action('فتح لوحة التحكم', route('today'));
    }

    protected function icon(): string
    {
        return 'ti-circle-check';
    }

    protected function tone(): string
    {
        return 'brand';
    }

    protected function text(): string
    {
        return 'تم اعتماد '.$this->workspace->name.'. موقعك ظاهر للزوار الآن';
    }

    protected function url(): string
    {
        return route('site');
    }
}
