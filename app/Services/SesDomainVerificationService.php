<?php

namespace App\Services;

use App\Models\Brand;
use Aws\Ses\SesClient;
use Illuminate\Support\Facades\Log;

class SesDomainVerificationService
{
    private SesClient $ses;

    public function __construct()
    {
        $this->ses = new SesClient([
            'version' => 'latest',
            'region' => config('services.ses.region', 'us-east-1'),
            'credentials' => [
                'key' => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ]);
    }

    /**
     * Start domain verification process for a brand.
     *
     * @return array<string, mixed> The DNS records the brand needs to add
     */
    public function initiateDomainVerification(Brand $brand, string $domain): array
    {
        // Verify domain identity in SES
        $result = $this->ses->verifyDomainIdentity([
            'Domain' => $domain,
        ]);

        // Get DKIM tokens
        $dkimResult = $this->ses->verifyDomainDkim([
            'Domain' => $domain,
        ]);

        $dnsRecords = [
            'verification' => [
                'type' => 'TXT',
                'name' => "_amazonses.{$domain}",
                'value' => $result['VerificationToken'],
            ],
            'dkim' => collect($dkimResult['DkimTokens'])->map(fn (string $token) => [
                'type' => 'CNAME',
                'name' => "{$token}._domainkey.{$domain}",
                'value' => "{$token}.dkim.amazonses.com",
            ])->toArray(),
            'spf' => [
                'type' => 'TXT',
                'name' => $domain,
                'value' => 'v=spf1 include:amazonses.com ~all',
            ],
        ];

        $brand->update([
            'custom_email_domain' => $domain,
            'email_domain_verification_status' => 'pending',
            'email_domain_dns_records' => $dnsRecords,
        ]);

        Log::info('Domain verification initiated for brand', [
            'brand_id' => $brand->id,
            'domain' => $domain,
        ]);

        return $dnsRecords;
    }

    /**
     * Check if domain is verified in SES.
     */
    public function checkVerificationStatus(Brand $brand): string
    {
        if (! $brand->custom_email_domain) {
            return 'none';
        }

        try {
            $result = $this->ses->getIdentityVerificationAttributes([
                'Identities' => [$brand->custom_email_domain],
            ]);

            $status = $result['VerificationAttributes'][$brand->custom_email_domain]['VerificationStatus'] ?? 'NotStarted';

            $mappedStatus = match ($status) {
                'Success' => 'verified',
                'Pending' => 'pending',
                'Failed', 'TemporaryFailure' => 'failed',
                default => 'none',
            };

            $brand->update([
                'email_domain_verification_status' => $mappedStatus,
                'email_domain_verified_at' => $mappedStatus === 'verified' ? now() : null,
            ]);

            Log::info('Domain verification status checked', [
                'brand_id' => $brand->id,
                'domain' => $brand->custom_email_domain,
                'status' => $mappedStatus,
            ]);

            return $mappedStatus;
        } catch (\Exception $e) {
            Log::error('Failed to check domain verification status', [
                'brand_id' => $brand->id,
                'domain' => $brand->custom_email_domain,
                'error' => $e->getMessage(),
            ]);

            return 'error';
        }
    }

    /**
     * Remove a domain from SES (when brand removes custom domain).
     */
    public function removeDomain(Brand $brand): void
    {
        if (! $brand->custom_email_domain) {
            return;
        }

        try {
            $this->ses->deleteIdentity([
                'Identity' => $brand->custom_email_domain,
            ]);

            $brand->update([
                'custom_email_domain' => null,
                'custom_email_from' => null,
                'email_domain_verification_status' => 'none',
                'email_domain_dns_records' => null,
                'email_domain_verified_at' => null,
            ]);

            Log::info('Domain removed from SES', [
                'brand_id' => $brand->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove domain from SES', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
