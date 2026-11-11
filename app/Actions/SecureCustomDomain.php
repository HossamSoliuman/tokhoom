<?php

namespace App\Actions;

use App\Enums\DomainStatus;
use App\Models\Workspace;
use App\Notifications\CustomDomainFailed;
use App\Notifications\CustomDomainLive;
use App\Support\Acme;
use App\Support\CertificateStore;
use App\Support\DomainVerifier;
use Exception;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Issue or renew a custom domain's certificate. Once issued the site is
 * served on the domain over HTTPS. Failed tries back off, and a domain
 * without a live certificate gives up after a few so the owner can fix the
 * record and check again.
 */
class SecureCustomDomain
{
    public const MAX_FAILURES = 5;

    /**
     * Minutes to wait before the next try, by how many failed in a row.
     *
     * @var list<int>
     */
    private const RETRY_MINUTES = [0, 2, 10, 30, 120];

    private const LONG_RETRY_MINUTES = 360;

    public function __construct(private Acme $acme, private DomainVerifier $verifier) {}

    public static function isDue(Workspace $workspace): bool
    {
        $attemptedAt = $workspace->domain_certificate_attempted_at;
        $wait = self::RETRY_MINUTES[$workspace->domain_certificate_failures] ?? self::LONG_RETRY_MINUTES;

        return $attemptedAt === null || $attemptedAt->copy()->addMinutes($wait)->isPast();
    }

    public function handle(Workspace $workspace): bool
    {
        $domain = (string) $workspace->custom_domain;
        $goesLive = $workspace->domain_status !== DomainStatus::Active;

        try {
            if (! $this->verifier->pointsToUs($domain)) {
                throw new RuntimeException('The CNAME record does not point at '.config('tokhoom.cname_target'));
            }

            $issued = $this->acme->issue($domain);
            CertificateStore::put($domain, $issued['certificate'], $issued['private_key']);
        } catch (Exception $exception) {
            report($exception);
            $this->recordFailure($workspace, $exception);

            return false;
        }

        $workspace->forceFill([
            'domain_status' => DomainStatus::Active,
            'domain_checked_at' => now(),
            'domain_certificate_expires_at' => $issued['expires_at'],
            'domain_certificate_attempted_at' => now(),
            'domain_certificate_failures' => 0,
            'domain_certificate_error' => null,
        ])->save();

        if ($goesLive) {
            $workspace->owner()?->notify(new CustomDomainLive($workspace));
        }

        return true;
    }

    private function recordFailure(Workspace $workspace, Exception $exception): void
    {
        $failures = min(255, $workspace->domain_certificate_failures + 1);
        $givesUp = $failures >= self::MAX_FAILURES && ! $workspace->hasLiveCertificate();

        $workspace->forceFill([
            'domain_status' => $givesUp ? DomainStatus::Error : $workspace->domain_status,
            'domain_certificate_attempted_at' => now(),
            'domain_certificate_failures' => $failures,
            'domain_certificate_error' => Str::limit($exception->getMessage(), 250),
        ])->save();

        if ($givesUp) {
            $workspace->owner()?->notify(new CustomDomainFailed($workspace));
        }
    }
}
