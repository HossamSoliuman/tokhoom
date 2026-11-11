<?php

namespace App\Actions;

use App\Enums\DomainStatus;
use App\Models\Workspace;
use App\Support\CertificateStore;
use App\Support\DomainVerifier;

/**
 * Point a workspace at a custom domain and check its CNAME record. A domain
 * that points at us moves to securing, and the scheduler issues its
 * certificate; one that is already live over HTTPS stays active.
 */
class CheckCustomDomain
{
    public function __construct(private DomainVerifier $verifier) {}

    public function handle(Workspace $workspace, string $domain): DomainStatus
    {
        if ($workspace->custom_domain !== $domain) {
            CertificateStore::delete($workspace->custom_domain);

            $workspace->custom_domain = $domain;
            $workspace->domain_certificate_expires_at = null;
        }

        $status = match (true) {
            ! $this->verifier->pointsToUs($domain) => DomainStatus::Error,
            $workspace->domain_status === DomainStatus::Active && $workspace->hasLiveCertificate() => DomainStatus::Active,
            default => DomainStatus::Securing,
        };

        $workspace->forceFill([
            'domain_status' => $status,
            'domain_checked_at' => now(),
            'domain_certificate_attempted_at' => null,
            'domain_certificate_failures' => 0,
            'domain_certificate_error' => null,
        ])->save();

        return $status;
    }

    /**
     * Unlink the custom domain and remove its certificate.
     */
    public static function remove(Workspace $workspace): void
    {
        CertificateStore::delete($workspace->custom_domain);

        $workspace->forceFill([
            'custom_domain' => null,
            'domain_status' => null,
            'domain_checked_at' => null,
            'domain_certificate_expires_at' => null,
            'domain_certificate_attempted_at' => null,
            'domain_certificate_failures' => 0,
            'domain_certificate_error' => null,
        ])->save();
    }
}
