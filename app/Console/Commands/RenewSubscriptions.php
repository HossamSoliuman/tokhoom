<?php

namespace App\Console\Commands;

use App\Enums\ApprovalStatus;
use App\Models\Workspace;
use App\Support\Subscriptions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Daily: workspaces whose trial or paid month has ended get their invoice and
 * a charge on the saved card. Sites are never suspended from here.
 */
#[Signature('tokhoom:renew-subscriptions')]
#[Description('Invoice due workspaces and charge their saved cards')]
class RenewSubscriptions extends Command
{
    public function handle(): int
    {
        $renewed = 0;

        Workspace::query()
            ->where('approval_status', ApprovalStatus::Approved)
            ->whereNull('suspended_at')
            ->lazyById()
            ->filter(fn (Workspace $workspace) => $workspace->isBillingDue())
            ->each(function (Workspace $workspace) use (&$renewed): void {
                Subscriptions::renew($workspace);
                $renewed++;
            });

        $this->info('Billed '.$renewed.' workspaces.');

        return self::SUCCESS;
    }
}
