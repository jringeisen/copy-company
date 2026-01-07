# Amazon SES Setup Guide

This guide walks you through setting up Amazon SES for high-volume email sending with Copy Company.

## Prerequisites

- An AWS account
- Access to your domain's DNS settings
- Your application deployed with a public URL (for webhooks)

---

## Step 1: Access Amazon SES

1. Log in to the [AWS Console](https://console.aws.amazon.com/)
2. Search for "SES" in the search bar
3. Click on **Amazon Simple Email Service**
4. Select your preferred region (us-east-1 recommended for best deliverability)

> **Important**: Note your region - you'll need it for configuration.

---

## Step 2: Verify Your Domain

### 2.1 Create Domain Identity

1. In the SES console, click **Verified identities** in the left sidebar
2. Click **Create identity**
3. Select **Domain**
4. Enter your domain (e.g., `copy-company.com`)
5. Check **Use a custom MAIL FROM domain** (optional but recommended)
   - Enter subdomain: `mail` (creates `mail.copy-company.com`)
6. Click **Create identity**

### 2.2 Add DNS Records

After creating the identity, AWS will show you the required DNS records:

#### DKIM Records (3 CNAMEs)
Add these CNAME records to your DNS:

| Type | Name | Value |
|------|------|-------|
| CNAME | `xxxxxxxxxx._domainkey.yourdomain.com` | `xxxxxxxxxx.dkim.amazonses.com` |
| CNAME | `yyyyyyyyyy._domainkey.yourdomain.com` | `yyyyyyyyyy.dkim.amazonses.com` |
| CNAME | `zzzzzzzzzz._domainkey.yourdomain.com` | `zzzzzzzzzz.dkim.amazonses.com` |

#### SPF Record
Add or update your SPF TXT record:

| Type | Name | Value |
|------|------|-------|
| TXT | `yourdomain.com` | `v=spf1 include:amazonses.com ~all` |

If you already have an SPF record, add `include:amazonses.com` before the `~all` or `-all`.

#### DMARC Record (Recommended)
Add a DMARC policy:

| Type | Name | Value |
|------|------|-------|
| TXT | `_dmarc.yourdomain.com` | `v=DMARC1; p=quarantine; rua=mailto:dmarc@yourdomain.com` |

#### Custom MAIL FROM (if enabled)

| Type | Name | Value |
|------|------|-------|
| MX | `mail.yourdomain.com` | `10 feedback-smtp.us-east-1.amazonses.com` |
| TXT | `mail.yourdomain.com` | `v=spf1 include:amazonses.com ~all` |

### 2.3 Verify DNS Propagation

1. Wait 15-60 minutes for DNS propagation
2. In SES console, click your domain identity
3. Click **Publish DNS records** or wait for automatic verification
4. Status should change from "Pending" to "Verified"

---

## Step 3: Create Configuration Set (for Tracking)

Configuration Sets enable open/click tracking and event notifications.

### 3.1 Create the Configuration Set

1. In SES console, click **Configuration sets** in the left sidebar
2. Click **Create set**
3. Name: `copy-company-tracking`
4. Click **Create set**

### 3.2 About Open & Click Tracking

Open and click tracking are automatically enabled when you add event destinations for "Opens" and "Clicks" events (covered in Step 5).

**How it works:**
- **Open tracking**: SES inserts a tiny 1x1 transparent image in your emails. When opened, it pings AWS servers.
- **Click tracking**: SES rewrites links to go through `r.REGION.awstrack.me` before redirecting to your original URL.

> **Note**: Many ad blockers block the default AWS tracking domain. For production, consider [setting up a custom tracking domain](https://docs.aws.amazon.com/ses/latest/dg/configure-custom-open-click-domains.html) using your own subdomain (e.g., `track.yourdomain.com`).

---

## Step 4: Create SNS Topic for Webhooks

SNS (Simple Notification Service) will send email events to your application.

### 4.1 Create the SNS Topic

1. Open [SNS Console](https://console.aws.amazon.com/sns/)
2. Click **Topics** in the left sidebar
3. Click **Create topic**
4. Type: **Standard**
5. Name: `copy-company-ses-events`
6. Click **Create topic**

### 4.2 Create HTTPS Subscription

1. Click on your new topic
2. Click **Create subscription**
3. Protocol: **HTTPS**
4. Endpoint: `https://yourdomain.com/webhooks/ses`
5. Click **Create subscription**

The subscription will show as "Pending confirmation". Your application will automatically confirm it when it receives the confirmation request.

---

## Step 5: Connect Configuration Set to SNS

### 5.1 Add Event Destinations

1. Go back to SES console → **Configuration sets**
2. Click on `copy-company-tracking`
3. Go to **Event destinations** tab
4. Click **Add destination**

#### For Bounce & Complaint Events:
1. Event types: Select **Bounces** and **Complaints**
2. Click **Next**
3. Destination type: **Amazon SNS**
4. SNS topic: Select `copy-company-ses-events`
5. Click **Next** → **Add destination**

#### For Delivery Events:
Repeat the above, selecting **Deliveries**

#### For Engagement Events:
Repeat the above, selecting **Opens** and **Clicks**

---

## Step 6: Request Production Access

New SES accounts are in "sandbox mode" with these limits:
- Can only send to verified email addresses
- Maximum 200 emails per 24 hours
- Maximum 1 email per second

### 6.1 Request Production Access

1. In SES console, click **Account dashboard**
2. In the "Your account is in the sandbox" banner, click **Request production access**
3. Fill out the form:
   - **Mail type**: Transactional (for newsletters, select "Marketing")
   - **Website URL**: Your application URL
   - **Use case description**: Be detailed! Example:
     ```
     We operate a content platform where creators send newsletters to their
     opted-in subscribers. Users must confirm their subscription via double
     opt-in before receiving emails. We handle bounces and complaints
     automatically via SNS webhooks, immediately removing bounced addresses
     and honoring all unsubscribe requests. We expect to send approximately
     100,000 emails per month initially.
     ```
   - **Additional contacts**: Add an email for AWS to contact
4. Click **Submit request**

> **Note**: Review typically takes 24-48 hours. You'll receive an email when approved.

---

## Step 7: Create IAM Credentials

### 7.1 Create IAM User

1. Open [IAM Console](https://console.aws.amazon.com/iam/)
2. Click **Users** → **Create user**
3. User name: `copy-company-ses`
4. Click **Next**

### 7.2 Attach Policies

1. Select **Attach policies directly**
2. Search and select:
   - `AmazonSESFullAccess`
   - `AmazonSNSReadOnlyAccess`
3. Click **Next** → **Create user**

### 7.3 Create Access Keys

1. Click on your new user
2. Go to **Security credentials** tab
3. Click **Create access key**
4. Select **Application running outside AWS**
5. Click **Next** → **Create access key**
6. **Important**: Download the CSV or copy both keys now - you won't see the secret again!

---

## Step 8: Configure Your Application

### 8.1 Update Environment Variables

Add these to your `.env` file:

```env
# Mail Configuration
MAIL_MAILER=ses

# AWS Credentials
AWS_ACCESS_KEY_ID=your-access-key-id
AWS_SECRET_ACCESS_KEY=your-secret-access-key
AWS_DEFAULT_REGION=us-east-1

# SES Configuration Set (for tracking)
SES_CONFIGURATION_SET=copy-company-tracking
```

### 8.2 Update Mail Configuration (Optional)

If you need to specify the configuration set, update `config/mail.php`:

```php
'ses' => [
    'transport' => 'ses',
    'options' => [
        'ConfigurationSetName' => env('SES_CONFIGURATION_SET'),
    ],
],
```

### 8.3 Deploy and Test

1. Deploy your application with the new environment variables
2. The SNS subscription should auto-confirm when AWS sends the confirmation
3. Send a test email and verify it arrives
4. Check the SES console → **Sending statistics** to see your metrics

---

## Step 9: IP Warming (Important!)

When you first start sending, gradually increase volume to build reputation:

| Week | Daily Volume |
|------|-------------|
| 1 | 1,000 emails |
| 2 | 5,000 emails |
| 3 | 20,000 emails |
| 4 | 50,000 emails |
| 5+ | Scale as needed |

Sending too much too fast can damage your sender reputation.

---

## Monitoring & Maintenance

### Check Reputation Regularly

Run the reputation check command:

```bash
php artisan ses:check-reputation
```

This runs automatically every hour via the scheduler.

### Key Metrics to Watch

| Metric | Healthy | Warning | Critical |
|--------|---------|---------|----------|
| Bounce Rate | < 3% | 3-5% | > 5% |
| Complaint Rate | < 0.05% | 0.05-0.1% | > 0.1% |

### If Rates Are Too High

1. **Immediately stop sending** to prevent account suspension
2. Clean your subscriber list - remove old/inactive addresses
3. Implement stricter double opt-in
4. Review your unsubscribe process
5. Contact AWS support if needed

---

## Troubleshooting

### Emails Not Sending

1. Check you're out of sandbox mode
2. Verify your domain is confirmed
3. Check AWS credentials are correct
4. Review Laravel logs for errors

### Webhooks Not Working

1. Verify your endpoint is publicly accessible
2. Check the SNS subscription status
3. Ensure CSRF is disabled for the webhook route
4. Check application logs for incoming requests

### High Bounce Rate

1. Use email verification services before adding subscribers
2. Implement double opt-in
3. Remove subscribers who haven't engaged in 6+ months
4. Check for typos in email collection forms

---

## Cost Reference

| Service | Cost |
|---------|------|
| SES Emails | $0.10 per 1,000 |
| SNS Notifications | $0.50 per 1,000,000 |
| Data Transfer | Included |

**Example**: 100,000 emails/month = ~$10
