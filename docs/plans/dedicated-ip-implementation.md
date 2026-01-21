# Dedicated IP Support for Pro Accounts

## Overview

Implement tiered email sending infrastructure:
- **Starter/Creator**: Shared IP pool (current behavior)
- **Pro**: Dedicated IP per brand ($24.95/month from AWS)

## Architecture

```
AWS SES Account
├── Shared Configuration Set (default) → Starter/Creator brands
└── Brand-specific Configuration Set → Pro brands (with dedicated IP pool)
```

During warmup (20-30 days), Pro brands gradually shift traffic from shared to dedicated IP.

---

## Pricing Updates

### Current Pricing

| Plan | Monthly | Annual (per month) |
|------|---------|-------------------|
| Starter | $8 | $6 |
| Creator | $19 | $14 |
| Pro | $29 | $22 |

### AWS Costs

- **Dedicated IP**: $24.95/month per IP (no free tier, billed immediately)
- **Email sending**: ~$0.10 per 1,000 emails (already covered by metered billing)

### Proposed New Pro Pricing

To cover the dedicated IP cost ($24.95) with healthy margin:

| Plan | Monthly | Annual (per month) | Notes |
|------|---------|-------------------|-------|
| Pro | $49-59 | $39-49 | Includes dedicated IP |

**Margin analysis at $49/month:**
- Revenue: $49
- AWS dedicated IP: $24.95
- Gross margin: ~$24 (49%)

**Margin analysis at $59/month:**
- Revenue: $59
- AWS dedicated IP: $24.95
- Gross margin: ~$34 (58%)

### Code Changes for Pricing

**Update** `app/Enums/SubscriptionPlan.php`:
```php
public function monthlyPriceCents(): int
{
    return match ($this) {
        self::Starter => 800,
        self::Creator => 1900,
        self::Pro => 5900, // Updated from 2900
    };
}

public function annualPricePerMonthCents(): int
{
    return match ($this) {
        self::Starter => 600,
        self::Creator => 1400,
        self::Pro => 4900, // Updated from 2200
    };
}
```

**Stripe Setup:**
1. Create new Pro price products in Stripe Dashboard
2. Update `.env` with new price IDs:
   - `STRIPE_PRO_MONTHLY=price_xxx`
   - `STRIPE_PRO_ANNUAL=price_xxx`
3. Consider: grandfather existing Pro users at old price?

### Migration Strategy for Existing Pro Users

Options:
1. **Grandfather existing users** - Keep them at $29, only new Pro users pay $49+
2. **Grace period** - Give 30-60 day notice, then migrate to new pricing
3. **Opt-in dedicated IP** - Keep Pro at $29, offer dedicated IP as $25 add-on

---

## How Warmup Works

**Important**: Warmup does NOT delay or limit newsletter delivery. All subscribers receive the email immediately.

During warmup, each email is randomly routed through either:
- The new dedicated IP (to warm it up)
- The shared IP pool (for reliable delivery)

**Example: New Pro user with 50,000 subscribers sends a newsletter**

| Day | Via Dedicated IP | Via Shared Pool | Total Sent |
|-----|-----------------|-----------------|------------|
| Day 1 (5%) | 2,500 emails | 47,500 emails | 50,000 |
| Day 5 (25%) | 12,500 emails | 37,500 emails | 50,000 |
| Day 10 (50%) | 25,000 emails | 25,000 emails | 50,000 |
| Day 20 (100%) | 50,000 emails | 0 emails | 50,000 |

This gradually builds the dedicated IP's sender reputation without impacting deliverability.

---

## Implementation Steps

### 1. Database Migration

**File**: `database/migrations/XXXX_add_dedicated_ip_to_brands_table.php`

Add to `brands` table:
- `ses_configuration_set` - Brand's AWS Configuration Set name
- `ses_dedicated_ip_pool` - Brand's IP pool name
- `dedicated_ip_address` - The actual IP
- `dedicated_ip_status` - Enum: none, provisioning, warming, active, suspended, released
- `dedicated_ip_provisioned_at`, `dedicated_ip_warmup_started_at`, `dedicated_ip_warmup_completed_at`
- `warmup_day` - Current day in warmup (1-30)
- `warmup_daily_stats` - JSON tracking sends during warmup
- `last_warmup_send_at` - Timestamp of last send during warmup (for pause/resume)
- `warmup_paused` - Boolean, true if warmup paused due to inactivity

**File**: `database/migrations/XXXX_create_dedicated_ip_logs_table.php`

Audit log: `brand_id`, `action`, `ip_address`, `metadata`, `admin_user_id`

### 2. Enums & Models

**Create** `app/Enums/DedicatedIpStatus.php`:
```php
enum DedicatedIpStatus: string {
    case None = 'none';
    case Provisioning = 'provisioning';
    case Warming = 'warming';
    case Active = 'active';
    case Suspended = 'suspended';
    case Released = 'released';
}
```

**Create** `app/Models/DedicatedIpLog.php`

**Update** `app/Models/Brand.php`:
- Add fillable fields and casts
- `hasDedicatedIp(): bool`
- `isInWarmupPeriod(): bool`
- `getSesConfigurationSet(): string` - Returns brand's config set or default
- `shouldUseDedicatedIp(): bool` - Handles warmup percentage logic

**Update** `app/Enums/SubscriptionPlan.php`:
- Add `hasDedicatedIpSupport(): bool` (returns true for Pro)

### 3. SES Service

**Create** `app/Services/SesDedicatedIpService.php`:

```php
class SesDedicatedIpService
{
    public function provisionDedicatedIp(Brand $brand, User $admin): array
    // Creates Configuration Set + IP Pool in AWS

    public function assignDedicatedIp(Brand $brand, string $ipAddress, User $admin): array
    // Assigns purchased IP to brand's pool, starts warmup

    public function getWarmupStatus(Brand $brand): array
    // Queries AWS for IP warmup progress

    public function releaseDedicatedIp(Brand $brand, User $admin, string $reason): array
    // Cleans up AWS resources on downgrade
}
```

### 4. Modify Email Sending

**Update** `app/Jobs/SendNewsletterToSubscriber.php`:

```php
public function handle(): void
{
    $brand = $this->newsletterSend->brand;

    // During warmup, randomly decide based on percentage
    $useDedicatedIp = $brand->shouldUseDedicatedIp();

    $configSet = $useDedicatedIp
        ? $brand->ses_configuration_set
        : config('services.ses.configuration_set');

    // Send with dynamic Configuration Set
    $sentMessage = Mail::mailer('ses')
        ->to($this->subscriber->email)
        ->send(new NewsletterMail(...)->withConfigurationSet($configSet));

    // Track warmup stats if applicable
    if ($brand->isInWarmupPeriod()) {
        $this->trackWarmupSend($brand, $useDedicatedIp);
    }
    // ... existing logic
}
```

**Brand model warmup logic:**
```php
public function shouldUseDedicatedIp(): bool
{
    if ($this->dedicated_ip_status === 'active') {
        return true; // Always use dedicated IP after warmup
    }

    if ($this->dedicated_ip_status === 'warming') {
        // Randomly route based on warmup day percentage
        $percentage = config("services.ses.warmup_percentages.{$this->warmup_day}", 100);
        return random_int(1, 100) <= $percentage;
    }

    return false; // No dedicated IP
}
```

### 5. Warmup Command

**Create** `app/Console/Commands/ProcessDedicatedIpWarmup.php`:
- Runs daily via scheduler
- Increments `warmup_day` for brands in warming status
- Checks AWS warmup status, marks as `active` when complete
- Logs daily stats

**Update** `routes/console.php`:
```php
Schedule::command('dedicated-ip:process-warmup')->dailyAt('00:05');
```

### 6. Configuration

**Update** `config/services.php`:
```php
'ses' => [
    // ... existing
    'configuration_set' => env('SES_CONFIGURATION_SET'), // Shared default
    'sns_topic_arn' => env('SES_SNS_TOPIC_ARN'),
    'warmup_percentages' => [
        1 => 5,   2 => 10,  3 => 15,  4 => 20,  5 => 25,
        6 => 30,  7 => 35,  8 => 40,  9 => 45,  10 => 50,
        11 => 55, 12 => 60, 13 => 65, 14 => 70, 15 => 75,
        16 => 80, 17 => 85, 18 => 90, 19 => 95, 20 => 100,
    ],
],
```

### 7. Admin Controller (Optional - can defer)

**Create** `app/Http/Controllers/Admin/DedicatedIpController.php`:
- `index()` - List brands with dedicated IPs
- `provision(Brand)` - Create AWS resources
- `assignIp(Brand, ip)` - Assign purchased IP
- `release(Brand)` - Remove dedicated IP
- `status(Brand)` - Get warmup/reputation metrics

### 8. Downgrade Handler

**Create** `app/Listeners/HandleSubscriptionDowngrade.php`:
- Listen for Stripe webhook `customer.subscription.updated`
- If downgrading from Pro, call `releaseDedicatedIp()` for all account brands

---

## AWS Setup & IP Provisioning Strategy

### Recommended Approach: Pre-purchased IP Pool

Rather than buying a new IP for each Pro signup (slow, manual), maintain a pool of pre-purchased IPs:

```
Available IP Pool (pre-purchased, pre-warmed)
├── 54.240.x.1 (available)
├── 54.240.x.2 (available)
└── 54.240.x.3 (available)

Assigned IPs
├── 54.240.x.4 → Brand "Acme Co" (active)
└── 54.240.x.5 → Brand "TechNews" (warming, day 8)
```

**Benefits:**
- Instant assignment when Pro brand signs up (no waiting)
- Reuse IPs from churned accounts (already warmed!)
- Control costs by deciding when to expand pool
- Pre-warm new IPs before they're needed

### One-Time Initial Setup (AWS Console)

**Step 1: Create SNS Topic for SES Events**
1. Go to AWS SNS Console → Topics → Create topic
2. Name: `ses-events-production`
3. Type: Standard
4. Create topic, copy the ARN

**Step 2: Create SNS Subscription**
1. Select your topic → Create subscription
2. Protocol: HTTPS
3. Endpoint: `https://yourdomain.com/webhooks/ses`
4. Create subscription (will send confirmation to your endpoint)

**Step 3: Create Shared Configuration Set**
1. Go to AWS SES Console → Configuration sets → Create set
2. Name: `shared-pool` (this is your default for Starter/Creator)
3. Add event destination → SNS → Select your topic
4. Enable: Send, Delivery, Bounce, Complaint, Open, Click

**Step 4: Purchase Initial Dedicated IPs**
1. Go to SES Console → Dedicated IPs → Request dedicated IP
2. Start with 3-5 IPs depending on expected Pro signups
3. Note: Each IP costs $24.95/month, billed immediately
4. IPs arrive in "warming" status by default

**Step 5: Create IP Pool for Available IPs**
1. Go to SES Console → Dedicated IPs → IP pools → Create pool
2. Name: `available-pool`
3. Add your purchased IPs to this pool
4. These IPs will warm up in the pool (you can send test emails)

**Step 6: Set IAM Permissions**
Add these permissions to your application's IAM role:
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "ses:CreateConfigurationSet",
        "ses:DeleteConfigurationSet",
        "ses:CreateConfigurationSetEventDestination",
        "ses:CreateDedicatedIpPool",
        "ses:DeleteDedicatedIpPool",
        "ses:PutDedicatedIpInPool",
        "ses:GetDedicatedIp",
        "ses:GetDedicatedIps",
        "ses:ListDedicatedIpPools"
      ],
      "Resource": "*"
    }
  ]
}
```

### Workflow: When a Pro Brand Signs Up

**Automatic (code does this):**
1. Create brand-specific Configuration Set (`brand-{id}-config`)
2. Create brand-specific IP Pool (`brand-{id}-pool`)
3. Set brand status to `provisioning`
4. Notify admin that brand needs IP assignment

**Manual (admin does this):**
1. Admin receives notification (email/Slack)
2. Admin goes to admin panel → Dedicated IPs
3. Admin sees list of available IPs from `available-pool`
4. Admin clicks "Assign IP" for the brand
5. System moves IP from `available-pool` to `brand-{id}-pool`
6. Brand status changes to `warming` and warmup begins

**Why semi-manual?**
- Prevents runaway costs if many Pro signups happen
- Lets you verify brand is legitimate before committing $25/month
- Allows you to prioritize which brands get IPs first if pool is low

### Workflow: When a Pro Brand Churns/Downgrades

1. Stripe webhook triggers downgrade handler
2. System moves IP from `brand-{id}-pool` back to `available-pool`
3. System deletes `brand-{id}-pool` and `brand-{id}-config`
4. IP retains its warmed reputation in `available-pool`
5. IP is immediately available for next Pro brand (pre-warmed!)

### When to Purchase More IPs

Monitor your pool and purchase more when:
- Available pool drops below 2-3 IPs
- You have Pro signups waiting for IP assignment
- Pro growth is accelerating

**Add command for monitoring:**
```php
// app/Console/Commands/CheckDedicatedIpPool.php
// Run daily, alerts if available pool is low
Schedule::command('dedicated-ip:check-pool')->dailyAt('09:00');
```

### Database: Track IP Pool

**Create** `database/migrations/XXXX_create_dedicated_ips_table.php`:

```php
Schema::create('dedicated_ips', function (Blueprint $table) {
    $table->id();
    $table->string('ip_address')->unique();
    $table->string('status'); // available, assigned, warming
    $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
    $table->timestamp('purchased_at');
    $table->timestamp('assigned_at')->nullable();
    $table->timestamp('released_at')->nullable();
    $table->timestamps();

    $table->index('status');
});
```

This lets you:
- See all IPs you own
- Track which brand each IP is assigned to
- Know when IPs were purchased/assigned
- Query available IPs for assignment

### Environment Variables

Add to `.env`:
```
SES_CONFIGURATION_SET=shared-pool
SES_SNS_TOPIC_ARN=arn:aws:sns:us-east-1:123456789:ses-events-production
SES_AVAILABLE_IP_POOL=available-pool
```

---

## Warmup Percentage Schedule

| Day | % via Dedicated IP | % via Shared |
|-----|-------------------|--------------|
| 1-5 | 5-25% | 75-95% |
| 6-10 | 30-50% | 50-70% |
| 11-15 | 55-75% | 25-45% |
| 16-20 | 80-100% | 0-20% |

After day 20, check AWS warmup status. If complete, mark as `active`.

---

## Files to Modify

| File | Change |
|------|--------|
| `app/Jobs/SendNewsletterToSubscriber.php` | Dynamic Configuration Set selection, track warmup sends |
| `app/Models/Brand.php` | Add dedicated IP fields and methods |
| `app/Enums/SubscriptionPlan.php` | Add `hasDedicatedIpSupport()`, update pricing |
| `app/Services/SubscriptionLimitsService.php` | Add `canUseDedicatedIp()` |
| `app/Http/Middleware/HandleInertiaRequests.php` | Add dedicated IP status to shared props |
| `config/services.php` | Add warmup config |
| `routes/console.php` | Schedule warmup + reputation commands |

## Files to Create

| File | Purpose |
|------|---------|
| `app/Enums/DedicatedIpStatus.php` | Status enum |
| `app/Models/DedicatedIp.php` | IP pool tracking model |
| `app/Models/DedicatedIpLog.php` | Audit model |
| `app/Services/SesDedicatedIpService.php` | AWS SES interactions |
| `app/Console/Commands/ProcessDedicatedIpWarmup.php` | Daily warmup processing |
| `app/Console/Commands/CheckDedicatedIpReputation.php` | Hourly reputation check, auto-suspend |
| `app/Console/Commands/CheckDedicatedIpPool.php` | Daily pool size monitoring, alerts if low |
| `app/Listeners/HandleSubscriptionDowngrade.php` | Cleanup on downgrade |
| `app/Listeners/HandleProSubscription.php` | Provisions resources when brand upgrades to Pro |
| `app/Notifications/DedicatedIpSuspended.php` | User notification when IP suspended |
| `app/Notifications/DedicatedIpAssignmentNeeded.php` | Admin notification when Pro brand needs IP |
| `app/Notifications/DedicatedIpPoolLow.php` | Admin alert when available pool is low |
| `resources/js/Components/DedicatedIpStatus.vue` | Warmup progress bar component |
| `database/migrations/XXXX_add_dedicated_ip_to_brands_table.php` | Brand schema changes |
| `database/migrations/XXXX_create_dedicated_ips_table.php` | IP pool tracking table |
| `database/migrations/XXXX_create_dedicated_ip_logs_table.php` | Audit log |

---

## Verification

1. **Unit tests**: `SesDedicatedIpService` methods with mocked AWS client
2. **Feature tests**:
   - Newsletter sends use correct Configuration Set based on plan/status
   - Warmup percentage logic works correctly
   - Downgrade triggers IP release
3. **Manual testing**:
   - Provision a test brand with dedicated IP
   - Send test newsletters, verify Configuration Set in SES console
   - Run warmup command, verify day progression

---

## Dashboard UI for Pro Users

### Warmup Progress Display

Show a simple progress indicator in the Pro user's dashboard:

```
Dedicated IP: Warming up (Day 12 of 20)
[████████████░░░░░░░░] 60%
```

**Implementation:**
- Add to Inertia shared props or brand settings page
- Show only when `dedicated_ip_status === 'warming'`
- After warmup complete, show: "Dedicated IP: Active"

**Files:**
- `app/Http/Middleware/HandleInertiaRequests.php` - Add dedicated IP status to shared props
- `resources/js/Components/DedicatedIpStatus.vue` - Progress bar component

---

## Auto-Pause for Reputation Protection

### Thresholds

Automatically pause dedicated IP sending if:
- **Bounce rate > 5%** (rolling 24-hour window)
- **Complaint rate > 0.1%** (rolling 24-hour window)

### Behavior

1. When threshold exceeded:
   - Set `dedicated_ip_status` to `suspended`
   - Log the event with metrics
   - Send notification email to user explaining the pause
   - Send alert to admin

2. To re-enable:
   - Admin must manually review and re-activate
   - Consider requiring user to clean their list first

### Implementation

**Create** `app/Console/Commands/CheckDedicatedIpReputation.php`:
```php
// Run hourly via scheduler
Schedule::command('dedicated-ip:check-reputation')->hourly();
```

```php
public function handle(): void
{
    Brand::where('dedicated_ip_status', 'active')
        ->orWhere('dedicated_ip_status', 'warming')
        ->each(function (Brand $brand) {
            $metrics = $this->get24HourMetrics($brand);

            if ($metrics['bounce_rate'] > 0.05 || $metrics['complaint_rate'] > 0.001) {
                $this->suspendDedicatedIp($brand, $metrics);
            }
        });
}
```

**Add to files to create:**
| File | Purpose |
|------|---------|
| `app/Console/Commands/CheckDedicatedIpReputation.php` | Hourly reputation check |
| `app/Notifications/DedicatedIpSuspended.php` | User notification |

---

## Warmup Pause/Resume for Inactive Senders

### Problem

If a Pro user doesn't send emails during warmup, the IP won't actually warm up even though days are passing.

### Solution: Pause and Resume

- After **7 days** of no sends, pause the warmup timer
- Resume from the same day when they send again
- Track `last_warmup_send_at` timestamp

### Database Addition

Add to brands migration:
```php
$table->timestamp('last_warmup_send_at')->nullable();
$table->boolean('warmup_paused')->default(false);
```

### Logic Updates

**Update** `ProcessDedicatedIpWarmup` command:
```php
public function processWarmup(Brand $brand): void
{
    // Check for inactivity
    if ($brand->last_warmup_send_at?->diffInDays(now()) >= 7) {
        if (!$brand->warmup_paused) {
            $brand->update(['warmup_paused' => true]);
            DedicatedIpLog::create([
                'brand_id' => $brand->id,
                'action' => 'warmup_paused',
                'metadata' => ['reason' => 'no_sends_7_days'],
            ]);
        }
        return; // Don't increment warmup day
    }

    // Resume if was paused and now sending again
    if ($brand->warmup_paused) {
        $brand->update(['warmup_paused' => false]);
        DedicatedIpLog::create([
            'brand_id' => $brand->id,
            'action' => 'warmup_resumed',
        ]);
    }

    // ... continue with normal warmup progression
}
```

**Update** `SendNewsletterToSubscriber` job:
```php
// After successful send during warmup
if ($brand->isInWarmupPeriod()) {
    $brand->updateQuietly(['last_warmup_send_at' => now()]);
}
```

### User Communication

When warmup is paused, show in dashboard:
```
Dedicated IP: Warmup paused (no recent sends)
Send a newsletter to resume warmup.
```
