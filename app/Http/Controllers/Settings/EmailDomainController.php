<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\InitiateDomainVerificationRequest;
use App\Http\Requests\Settings\UpdateEmailFromRequest;
use App\Services\SesDomainVerificationService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EmailDomainController extends Controller
{
    public function __construct(
        private SesDomainVerificationService $verificationService
    ) {}

    /**
     * Show the email domain settings page.
     */
    public function index(): Response|RedirectResponse
    {
        $brand = auth()->user()->currentBrand();

        if (! $brand) {
            return redirect()->route('brands.create');
        }

        return Inertia::render('Settings/EmailDomain', [
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'custom_email_domain' => $brand->custom_email_domain,
                'custom_email_from' => $brand->custom_email_from,
                'email_domain_verification_status' => $brand->email_domain_verification_status,
                'email_domain_dns_records' => $brand->email_domain_dns_records,
                'email_domain_verified_at' => $brand->email_domain_verified_at?->toIso8601String(),
            ],
            'defaultFromAddress' => config('mail.from.address'),
        ]);
    }

    /**
     * Initiate domain verification with SES.
     */
    public function initiate(InitiateDomainVerificationRequest $request): RedirectResponse
    {
        $brand = auth()->user()->currentBrand();
        $validated = $request->validated();

        try {
            $this->verificationService->initiateDomainVerification(
                $brand,
                $validated['domain']
            );

            // Also save the "from" address part
            $brand->update([
                'custom_email_from' => $validated['from_address'] ?? 'hello',
            ]);

            return back()->with('success', 'Domain verification initiated! Add the DNS records below to verify ownership.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to initiate domain verification: '.$e->getMessage());
        }
    }

    /**
     * Check the current verification status.
     */
    public function checkStatus(): RedirectResponse
    {
        $brand = auth()->user()->currentBrand();

        if (! $brand->custom_email_domain) {
            return back()->with('error', 'No domain configured.');
        }

        try {
            $status = $this->verificationService->checkVerificationStatus($brand);

            $messages = [
                'verified' => 'Domain verified successfully! Your emails will now be sent from your custom domain.',
                'pending' => 'Domain verification is still pending. Please ensure DNS records are added correctly.',
                'failed' => 'Domain verification failed. Please check your DNS records and try again.',
                'none' => 'No verification in progress.',
                'error' => 'Could not check verification status. Please try again.',
            ];

            $type = in_array($status, ['verified']) ? 'success' : (in_array($status, ['failed', 'error']) ? 'error' : 'info');

            return back()->with($type, $messages[$status] ?? 'Status: '.$status);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to check status: '.$e->getMessage());
        }
    }

    /**
     * Update the "from" email address.
     */
    public function updateFrom(UpdateEmailFromRequest $request): RedirectResponse
    {
        $brand = auth()->user()->currentBrand();
        $validated = $request->validated();

        $brand->update([
            'custom_email_from' => $validated['from_address'],
        ]);

        return back()->with('success', 'From address updated successfully.');
    }

    /**
     * Remove the custom domain.
     */
    public function remove(): RedirectResponse
    {
        $brand = auth()->user()->currentBrand();

        if (! $brand->custom_email_domain) {
            return back()->with('error', 'No domain configured.');
        }

        try {
            $this->verificationService->removeDomain($brand);

            return back()->with('success', 'Custom domain removed. Emails will now be sent from the default address.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to remove domain: '.$e->getMessage());
        }
    }
}
