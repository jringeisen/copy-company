<?php

namespace App\Services;

use App\Enums\DedicatedIpStatus;
use App\Models\Brand;
use App\Models\DedicatedIpLog;
use App\Models\User;
use Aws\SesV2\SesV2Client;
use Illuminate\Support\Facades\Log;

class SesDedicatedIpService
{
    private SesV2Client $ses;

    public function __construct()
    {
        $this->ses = new SesV2Client([
            'version' => 'latest',
            'region' => config('services.ses.region', 'us-east-1'),
            'credentials' => [
                'key' => config('services.ses.key'),
                'secret' => config('services.ses.secret'),
            ],
        ]);
    }

    /**
     * Provision managed dedicated IP access for a Pro user.
     * Creates Configuration Set pointing to the shared managed IP pool.
     *
     * @return array{success: bool, message: string}
     */
    public function provisionForProUser(Brand $brand, ?User $admin = null): array
    {
        $configSetName = "brand-{$brand->id}-config";
        $managedPoolName = config('services.ses.managed_ip_pool', 'pro-managed-pool');

        try {
            // Create Configuration Set pointing to managed pool
            $this->ses->createConfigurationSet([
                'ConfigurationSetName' => $configSetName,
                'DeliveryOptions' => [
                    'SendingPoolName' => $managedPoolName,
                ],
                'ReputationOptions' => [
                    'ReputationMetricsEnabled' => true,
                ],
                'SendingOptions' => [
                    'SendingEnabled' => true,
                ],
            ]);

            // Add event destination for tracking
            $snsTopicArn = config('services.ses.sns_topic_arn');
            if ($snsTopicArn) {
                $this->ses->createConfigurationSetEventDestination([
                    'ConfigurationSetName' => $configSetName,
                    'EventDestinationName' => "{$configSetName}-events",
                    'EventDestination' => [
                        'Enabled' => true,
                        'MatchingEventTypes' => ['SEND', 'DELIVERY', 'BOUNCE', 'COMPLAINT', 'OPEN', 'CLICK'],
                        'SnsDestination' => [
                            'TopicArn' => $snsTopicArn,
                        ],
                    ],
                ]);
            }

            $brand->update([
                'ses_configuration_set' => $configSetName,
                'dedicated_ip_status' => DedicatedIpStatus::Active,
                'dedicated_ip_provisioned_at' => now(),
            ]);

            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'provisioned',
                'metadata' => [
                    'configuration_set' => $configSetName,
                    'managed_pool' => $managedPoolName,
                ],
                'admin_user_id' => $admin?->id,
            ]);

            Log::info('Managed dedicated IP access provisioned for brand', [
                'brand_id' => $brand->id,
                'configuration_set' => $configSetName,
                'managed_pool' => $managedPoolName,
            ]);

            return ['success' => true, 'message' => 'Managed IP access provisioned successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to provision managed dedicated IP access', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Release a brand's managed dedicated IP access (on downgrade or churn).
     *
     * @return array{success: bool, message: string}
     */
    public function releaseProUser(Brand $brand, ?User $admin = null, string $reason = 'downgrade'): array
    {
        $configSetName = $brand->ses_configuration_set;

        try {
            // Delete Configuration Set
            if ($configSetName) {
                try {
                    $this->ses->deleteConfigurationSet([
                        'ConfigurationSetName' => $configSetName,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Could not delete configuration set (may not exist)', [
                        'config_set' => $configSetName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Update brand
            $brand->update([
                'ses_configuration_set' => null,
                'dedicated_ip_status' => DedicatedIpStatus::Released,
            ]);

            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'released',
                'metadata' => [
                    'reason' => $reason,
                    'configuration_set' => $configSetName,
                ],
                'admin_user_id' => $admin?->id,
            ]);

            Log::info('Managed dedicated IP access released from brand', [
                'brand_id' => $brand->id,
                'configuration_set' => $configSetName,
                'reason' => $reason,
            ]);

            return ['success' => true, 'message' => 'Managed IP access released successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to release managed dedicated IP access', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Suspend a brand's dedicated IP sending (reputation issues).
     *
     * @return array{success: bool, message: string}
     */
    public function suspendDedicatedIp(Brand $brand, array $metrics, ?User $admin = null): array
    {
        try {
            $brand->update([
                'dedicated_ip_status' => DedicatedIpStatus::Suspended,
            ]);

            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'suspended',
                'metadata' => [
                    'bounce_rate' => $metrics['bounce_rate'] ?? null,
                    'complaint_rate' => $metrics['complaint_rate'] ?? null,
                ],
                'admin_user_id' => $admin?->id,
            ]);

            Log::warning('Dedicated IP access suspended due to reputation issues', [
                'brand_id' => $brand->id,
                'metrics' => $metrics,
            ]);

            return ['success' => true, 'message' => 'Dedicated IP access suspended'];
        } catch (\Exception $e) {
            Log::error('Failed to suspend dedicated IP access', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Reactivate a suspended dedicated IP.
     *
     * @return array{success: bool, message: string}
     */
    public function reactivateDedicatedIp(Brand $brand, ?User $admin = null): array
    {
        if ($brand->dedicated_ip_status !== DedicatedIpStatus::Suspended) {
            return ['success' => false, 'message' => 'Dedicated IP access is not suspended'];
        }

        try {
            $brand->update([
                'dedicated_ip_status' => DedicatedIpStatus::Active,
            ]);

            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'reactivated',
                'admin_user_id' => $admin?->id,
            ]);

            Log::info('Dedicated IP access reactivated', [
                'brand_id' => $brand->id,
            ]);

            return ['success' => true, 'message' => 'Dedicated IP access reactivated'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
