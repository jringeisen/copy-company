<?php

namespace Tests\Unit\Services;

use App\Models\Account;
use App\Models\Brand;
use App\Models\User;
use App\Services\SesDomainVerificationService;
use Aws\Result;
use Aws\Ses\SesClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use ReflectionClass;
use Tests\TestCase;

class SesDomainVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SesDomainVerificationService $service;

    protected SesClient $mockSes;

    protected Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create();
        $account = Account::factory()->create();
        $account->users()->attach($user->id, ['role' => 'admin']);
        $this->brand = Brand::factory()->forAccount($account)->create();

        $this->mockSes = Mockery::mock(SesClient::class);

        $this->service = new SesDomainVerificationService;

        // Use reflection to inject the mock SES client
        $reflection = new ReflectionClass($this->service);
        $property = $reflection->getProperty('ses');
        $property->setAccessible(true);
        $property->setValue($this->service, $this->mockSes);
    }

    public function test_initiate_domain_verification_creates_dns_records(): void
    {
        $domain = 'example.com';

        $this->mockSes->shouldReceive('verifyDomainIdentity')
            ->once()
            ->with(['Domain' => $domain])
            ->andReturn(new Result([
                'VerificationToken' => 'verification-token-12345',
            ]));

        $this->mockSes->shouldReceive('verifyDomainDkim')
            ->once()
            ->with(['Domain' => $domain])
            ->andReturn(new Result([
                'DkimTokens' => ['dkim-token-1', 'dkim-token-2', 'dkim-token-3'],
            ]));

        Log::shouldReceive('info')->once();

        $result = $this->service->initiateDomainVerification($this->brand, $domain);

        $this->assertArrayHasKey('verification', $result);
        $this->assertArrayHasKey('dkim', $result);
        $this->assertArrayHasKey('spf', $result);

        $this->assertEquals('TXT', $result['verification']['type']);
        $this->assertEquals("_amazonses.{$domain}", $result['verification']['name']);
        $this->assertEquals('verification-token-12345', $result['verification']['value']);

        $this->assertCount(3, $result['dkim']);
        $this->assertEquals('CNAME', $result['dkim'][0]['type']);

        $this->assertEquals('TXT', $result['spf']['type']);
        $this->assertEquals($domain, $result['spf']['name']);
        $this->assertEquals('v=spf1 include:amazonses.com ~all', $result['spf']['value']);

        $this->brand->refresh();
        $this->assertEquals($domain, $this->brand->custom_email_domain);
        $this->assertEquals('pending', $this->brand->email_domain_verification_status);
        $this->assertNotNull($this->brand->email_domain_dns_records);
    }

    public function test_check_verification_status_returns_none_when_no_domain(): void
    {
        $status = $this->service->checkVerificationStatus($this->brand);

        $this->assertEquals('none', $status);
    }

    public function test_check_verification_status_returns_verified(): void
    {
        $this->brand->update(['custom_email_domain' => 'verified.example.com']);

        $this->mockSes->shouldReceive('getIdentityVerificationAttributes')
            ->once()
            ->with(['Identities' => ['verified.example.com']])
            ->andReturn(new Result([
                'VerificationAttributes' => [
                    'verified.example.com' => [
                        'VerificationStatus' => 'Success',
                    ],
                ],
            ]));

        Log::shouldReceive('info')->once();

        $status = $this->service->checkVerificationStatus($this->brand);

        $this->assertEquals('verified', $status);

        $this->brand->refresh();
        $this->assertEquals('verified', $this->brand->email_domain_verification_status);
        $this->assertNotNull($this->brand->email_domain_verified_at);
    }

    public function test_check_verification_status_returns_pending(): void
    {
        $this->brand->update(['custom_email_domain' => 'pending.example.com']);

        $this->mockSes->shouldReceive('getIdentityVerificationAttributes')
            ->once()
            ->with(['Identities' => ['pending.example.com']])
            ->andReturn(new Result([
                'VerificationAttributes' => [
                    'pending.example.com' => [
                        'VerificationStatus' => 'Pending',
                    ],
                ],
            ]));

        Log::shouldReceive('info')->once();

        $status = $this->service->checkVerificationStatus($this->brand);

        $this->assertEquals('pending', $status);

        $this->brand->refresh();
        $this->assertEquals('pending', $this->brand->email_domain_verification_status);
        $this->assertNull($this->brand->email_domain_verified_at);
    }

    public function test_check_verification_status_returns_failed(): void
    {
        $this->brand->update(['custom_email_domain' => 'failed.example.com']);

        $this->mockSes->shouldReceive('getIdentityVerificationAttributes')
            ->once()
            ->with(['Identities' => ['failed.example.com']])
            ->andReturn(new Result([
                'VerificationAttributes' => [
                    'failed.example.com' => [
                        'VerificationStatus' => 'Failed',
                    ],
                ],
            ]));

        Log::shouldReceive('info')->once();

        $status = $this->service->checkVerificationStatus($this->brand);

        $this->assertEquals('failed', $status);

        $this->brand->refresh();
        $this->assertEquals('failed', $this->brand->email_domain_verification_status);
    }

    public function test_check_verification_status_returns_failed_on_temporary_failure(): void
    {
        $this->brand->update(['custom_email_domain' => 'temp-failed.example.com']);

        $this->mockSes->shouldReceive('getIdentityVerificationAttributes')
            ->once()
            ->with(['Identities' => ['temp-failed.example.com']])
            ->andReturn(new Result([
                'VerificationAttributes' => [
                    'temp-failed.example.com' => [
                        'VerificationStatus' => 'TemporaryFailure',
                    ],
                ],
            ]));

        Log::shouldReceive('info')->once();

        $status = $this->service->checkVerificationStatus($this->brand);

        $this->assertEquals('failed', $status);
    }

    public function test_check_verification_status_returns_none_on_not_started(): void
    {
        $this->brand->update(['custom_email_domain' => 'notstarted.example.com']);

        $this->mockSes->shouldReceive('getIdentityVerificationAttributes')
            ->once()
            ->with(['Identities' => ['notstarted.example.com']])
            ->andReturn(new Result([
                'VerificationAttributes' => [
                    'notstarted.example.com' => [
                        'VerificationStatus' => 'NotStarted',
                    ],
                ],
            ]));

        Log::shouldReceive('info')->once();

        $status = $this->service->checkVerificationStatus($this->brand);

        $this->assertEquals('none', $status);
    }

    public function test_check_verification_status_returns_error_on_exception(): void
    {
        $this->brand->update(['custom_email_domain' => 'error.example.com']);

        $this->mockSes->shouldReceive('getIdentityVerificationAttributes')
            ->once()
            ->andThrow(new \Exception('AWS error'));

        Log::shouldReceive('error')->once();

        $status = $this->service->checkVerificationStatus($this->brand);

        $this->assertEquals('error', $status);
    }

    public function test_remove_domain_clears_brand_email_settings(): void
    {
        $this->brand->update([
            'custom_email_domain' => 'remove.example.com',
            'custom_email_from' => 'hello@remove.example.com',
            'email_domain_verification_status' => 'verified',
            'email_domain_dns_records' => ['some' => 'records'],
            'email_domain_verified_at' => now(),
        ]);

        $this->mockSes->shouldReceive('deleteIdentity')
            ->once()
            ->with(['Identity' => 'remove.example.com']);

        Log::shouldReceive('info')->once();

        $this->service->removeDomain($this->brand);

        $this->brand->refresh();
        $this->assertNull($this->brand->custom_email_domain);
        $this->assertNull($this->brand->custom_email_from);
        $this->assertEquals('none', $this->brand->email_domain_verification_status);
        $this->assertNull($this->brand->email_domain_dns_records);
        $this->assertNull($this->brand->email_domain_verified_at);
    }

    public function test_remove_domain_does_nothing_when_no_domain(): void
    {
        $this->mockSes->shouldNotReceive('deleteIdentity');

        $this->service->removeDomain($this->brand);

        $this->assertNull($this->brand->custom_email_domain);
    }

    public function test_remove_domain_logs_error_on_exception(): void
    {
        $this->brand->update(['custom_email_domain' => 'error.example.com']);

        $this->mockSes->shouldReceive('deleteIdentity')
            ->once()
            ->andThrow(new \Exception('AWS error'));

        Log::shouldReceive('error')->once();

        $this->service->removeDomain($this->brand);

        // Domain should still be set since deletion failed
        $this->brand->refresh();
        $this->assertEquals('error.example.com', $this->brand->custom_email_domain);
    }

    public function test_check_verification_status_handles_missing_attributes(): void
    {
        $this->brand->update(['custom_email_domain' => 'missing.example.com']);

        $this->mockSes->shouldReceive('getIdentityVerificationAttributes')
            ->once()
            ->with(['Identities' => ['missing.example.com']])
            ->andReturn(new Result([
                'VerificationAttributes' => [],
            ]));

        Log::shouldReceive('info')->once();

        $status = $this->service->checkVerificationStatus($this->brand);

        $this->assertEquals('none', $status);
    }
}
