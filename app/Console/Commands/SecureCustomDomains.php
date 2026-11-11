<?php

namespace App\Console\Commands;

use App\Actions\SecureCustomDomain;
use App\Enums\DomainStatus;
use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every minute: issue certificates for custom domains whose record was just
 * verified, and renew live ones a month before they run out.
 */
#[Signature('tokhoom:secure-domains')]
#[Description('Issue and renew certificates for custom domains')]
class SecureCustomDomains extends Command
{
    public function handle(SecureCustomDomain $secureCustomDomain): int
    {
        $renewBefore = now()->addDays(config('tokhoom.certificates.renew_days'));

        $workspaces = Workspace::whereNotNull('custom_domain')
            ->where(fn (Builder $query) => $query
                ->where('domain_status', DomainStatus::Securing)
                ->orWhere(fn (Builder $query) => $query
                    ->where('domain_status', DomainStatus::Active)
                    ->where(fn (Builder $query) => $query
                        ->whereNull('domain_certificate_expires_at')
                        ->orWhere('domain_certificate_expires_at', '<', $renewBefore))))
            ->get()
            ->filter(fn (Workspace $workspace) => SecureCustomDomain::isDue($workspace));

        $secured = 0;

        foreach ($workspaces as $workspace) {
            if ($secureCustomDomain->handle($workspace)) {
                $secured++;
            }
        }

        $this->info("Secured {$secured} of {$workspaces->count()} custom domains.");

        return self::SUCCESS;
    }
}
