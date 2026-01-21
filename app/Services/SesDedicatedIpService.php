<?php

namespace App\Services;

use App\Enums\DedicatedIpStatus;
use App\Models\Brand;
use App\Models\DedicatedIp;
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
     * Provision AWS resources for a brand's dedicated IP.
     * Creates Configuration Set and IP Pool in AWS.
     *
     * @return array{success: bool, message: string}
     */
    public function provisionDedicatedIp(Brand $brand, ?User $admin = null): array
    {
        $configSetName = "brand-{$brand->id}-config";
        $poolName = "brand-{$brand->id}-pool";

        try {
            // Create Configuration Set
            $this->ses->createConfigurationSet([
                'ConfigurationSetName' => $configSetName,
                'DeliveryOptions' => [
                    'SendingPoolName' => config('services.ses.available_ip_pool', 'available-pool'),
                ],
                'ReputationOptions' => [
                    'ReputationMetricsEnabled' => true,
                ],
                'SendingOptions' => [
                    'SendingEnabled' => true,
                ],
            ]);

            // Create dedicated IP pool for this brand
            $this->ses->createDedicatedIpPool([
                'PoolName' => $poolName,
                'ScalingMode' => 'STANDARD',
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
                'ses_dedicated_ip_pool' => $poolName,
                'dedicated_ip_status' => DedicatedIpStatus::Provisioning,
                'dedicated_ip_provisioned_at' => now(),
            ]);

            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'provisioned',
                'metadata' => [
                    'configuration_set' => $configSetName,
                    'ip_pool' => $poolName,
                ],
                'admin_user_id' => $admin?->id,
            ]);

            Log::info('Dedicated IP resources provisioned for brand', [
                'brand_id' => $brand->id,
                'configuration_set' => $configSetName,
                'ip_pool' => $poolName,
            ]);

            return ['success' => true, 'message' => 'Resources provisioned successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to provision dedicated IP resources', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Assign a dedicated IP to a brand's pool and start warmup.
     *
     * @return array{success: bool, message: string}
     */
    public function assignDedicatedIp(Brand $brand, DedicatedIp $dedicatedIp, ?User $admin = null): array
    {
        if (! $brand->ses_dedicated_ip_pool) {
            return ['success' => false, 'message' => 'Brand does not have a dedicated IP pool provisioned'];
        }

        if (! $dedicatedIp->isAvailable()) {
            return ['success' => false, 'message' => 'IP is not available for assignment'];
        }

        try {
            // Move IP from available pool to brand's pool
            $this->ses->putDedicatedIpInPool([
                'Ip' => $dedicatedIp->ip_address,
                'DestinationPoolName' => $brand->ses_dedicated_ip_pool,
            ]);

            // Update Configuration Set to use brand's pool
            $this->ses->putConfigurationSetDeliveryOptions([
                'ConfigurationSetName' => $brand->ses_configuration_set,
                'SendingPoolName' => $brand->ses_dedicated_ip_pool,
            ]);

            // Update dedicated IP record
            $dedicatedIp->update([
                'status' => 'assigned',
                'brand_id' => $brand->id,
                'assigned_at' => now(),
            ]);

            // Update brand
            $brand->update([
                'dedicated_ip_address' => $dedicatedIp->ip_address,
                'dedicated_ip_status' => DedicatedIpStatus::Warming,
                'dedicated_ip_warmup_started_at' => now(),
                'warmup_day' => 1,
                'warmup_daily_stats' => [],
                'warmup_paused' => false,
            ]);

            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'ip_assigned',
                'ip_address' => $dedicatedIp->ip_address,
                'metadata' => [
                    'warmup_started' => true,
                ],
                'admin_user_id' => $admin?->id,
            ]);

            Log::info('Dedicated IP assigned to brand', [
                'brand_id' => $brand->id,
                'ip_address' => $dedicatedIp->ip_address,
            ]);

            return ['success' => true, 'message' => 'IP assigned successfully, warmup started'];
        } catch (\Exception $e) {
            Log::error('Failed to assign dedicated IP', [
                'brand_id' => $brand->id,
                'ip_address' => $dedicatedIp->ip_address,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get warmup status for a brand's dedicated IP.
     *
     * @return array{success: bool, data?: array<string, mixed>, message?: string}
     */
    public function getWarmupStatus(Brand $brand): array
    {
        if (! $brand->dedicated_ip_address) {
            return ['success' => false, 'message' => 'Brand does not have a dedicated IP'];
        }

        try {
            $result = $this->ses->getDedicatedIp([
                'Ip' => $brand->dedicated_ip_address,
            ]);

            $ip = $result['DedicatedIp'];

            return [
                'success' => true,
                'data' => [
                    'ip_address' => $ip['Ip'],
                    'warmup_status' => $ip['WarmupStatus'],
                    'warmup_percentage' => $ip['WarmupPercentage'],
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get warmup status', [
                'brand_id' => $brand->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Release a brand's dedicated IP (on downgrade or churn).
     *
     * @return array{success: bool, message: string}
     */
    public function releaseDedicatedIp(Brand $brand, ?User $admin = null, string $reason = 'downgrade'): array
    {
        $ipAddress = $brand->dedicated_ip_address;
        $poolName = $brand->ses_dedicated_ip_pool;
        $configSetName = $brand->ses_configuration_set;

        try {
            // Move IP back to available pool
            if ($ipAddress) {
                $availablePool = config('services.ses.available_ip_pool', 'available-pool');
                $this->ses->putDedicatedIpInPool([
                    'Ip' => $ipAddress,
                    'DestinationPoolName' => $availablePool,
                ]);

                // Update the dedicated IP record
                DedicatedIp::where('ip_address', $ipAddress)->update([
                    'status' => 'available',
                    'brand_id' => null,
                    'released_at' => now(),
                ]);
            }

            // Delete brand's IP pool
            if ($poolName) {
                try {
                    $this->ses->deleteDedicatedIpPool([
                        'PoolName' => $poolName,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Could not delete IP pool (may not exist)', [
                        'pool' => $poolName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

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
                'ses_dedicated_ip_pool' => null,
                'dedicated_ip_address' => null,
                'dedicated_ip_status' => DedicatedIpStatus::Released,
                'warmup_day' => null,
                'warmup_daily_stats' => null,
                'warmup_paused' => false,
            ]);

            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'ip_released',
                'ip_address' => $ipAddress,
                'metadata' => [
                    'reason' => $reason,
                ],
                'admin_user_id' => $admin?->id,
            ]);

            Log::info('Dedicated IP released from brand', [
                'brand_id' => $brand->id,
                'ip_address' => $ipAddress,
                'reason' => $reason,
            ]);

            return ['success' => true, 'message' => 'IP released successfully'];
        } catch (\Exception $e) {
            Log::error('Failed to release dedicated IP', [
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
                'ip_address' => $brand->dedicated_ip_address,
                'metadata' => [
                    'bounce_rate' => $metrics['bounce_rate'] ?? null,
                    'complaint_rate' => $metrics['complaint_rate'] ?? null,
                ],
                'admin_user_id' => $admin?->id,
            ]);

            Log::warning('Dedicated IP suspended due to reputation issues', [
                'brand_id' => $brand->id,
                'ip_address' => $brand->dedicated_ip_address,
                'metrics' => $metrics,
            ]);

            return ['success' => true, 'message' => 'IP suspended'];
        } catch (\Exception $e) {
            Log::error('Failed to suspend dedicated IP', [
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
            return ['success' => false, 'message' => 'IP is not suspended'];
        }

        try {
            $brand->update([
                'dedicated_ip_status' => DedicatedIpStatus::Active,
            ]);

            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'reactivated',
                'ip_address' => $brand->dedicated_ip_address,
                'admin_user_id' => $admin?->id,
            ]);

            Log::info('Dedicated IP reactivated', [
                'brand_id' => $brand->id,
                'ip_address' => $brand->dedicated_ip_address,
            ]);

            return ['success' => true, 'message' => 'IP reactivated'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get list of available IPs in the pool.
     *
     * @return array<int, array{ip_address: string, warmup_status: string}>
     */
    public function getAvailableIps(): array
    {
        try {
            $poolName = config('services.ses.available_ip_pool', 'available-pool');
            $result = $this->ses->getDedicatedIpsFromPool([
                'PoolName' => $poolName,
            ]);

            return collect($result['DedicatedIps'] ?? [])->map(fn ($ip) => [
                'ip_address' => $ip['Ip'],
                'warmup_status' => $ip['WarmupStatus'],
                'warmup_percentage' => $ip['WarmupPercentage'],
            ])->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get available IPs', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Sync AWS dedicated IPs with local database.
     */
    public function syncDedicatedIps(): void
    {
        $awsIps = $this->getAvailableIps();

        foreach ($awsIps as $awsIp) {
            DedicatedIp::updateOrCreate(
                ['ip_address' => $awsIp['ip_address']],
                [
                    'status' => 'available',
                    'purchased_at' => now(),
                ]
            );
        }
    }

    /**
     * Mark warmup as complete for a brand.
     */
    public function completeWarmup(Brand $brand): void
    {
        $brand->update([
            'dedicated_ip_status' => DedicatedIpStatus::Active,
            'dedicated_ip_warmup_completed_at' => now(),
        ]);

        DedicatedIpLog::create([
            'brand_id' => $brand->id,
            'action' => 'warmup_completed',
            'ip_address' => $brand->dedicated_ip_address,
        ]);

        Log::info('Dedicated IP warmup completed', [
            'brand_id' => $brand->id,
            'ip_address' => $brand->dedicated_ip_address,
        ]);
    }
}
