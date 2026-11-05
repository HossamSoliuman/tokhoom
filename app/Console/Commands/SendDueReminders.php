<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Notifications\ReminderDue;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tokhoom:send-due-reminders')]
#[Description('Notify agents about follow-ups that are due now')]
class SendDueReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sent = 0;

        Reminder::open()
            ->whereNull('notified_at')
            ->where('due_at', '<=', now()->addMinutes(15))
            ->with(['user', 'client'])
            ->chunkById(200, function ($reminders) use (&$sent): void {
                foreach ($reminders as $reminder) {
                    $reminder->user->notify(new ReminderDue($reminder));
                    $reminder->update(['notified_at' => now()]);
                    $sent++;
                }
            });

        $this->info("Sent {$sent} reminder notifications.");

        return self::SUCCESS;
    }
}
