<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'stripe' => [
        'prices' => [
            'starter_monthly' => env('STRIPE_STARTER_MONTHLY'),
            'starter_annual' => env('STRIPE_STARTER_ANNUAL'),
            'creator_monthly' => env('STRIPE_CREATOR_MONTHLY'),
            'creator_annual' => env('STRIPE_CREATOR_ANNUAL'),
            'pro_monthly' => env('STRIPE_PRO_MONTHLY'),
            'pro_annual' => env('STRIPE_PRO_ANNUAL'),
            'email_metered' => env('STRIPE_EMAIL_METERED'),
        ],
        'email_meter_id' => env('STRIPE_EMAIL_METER_ID'),
        'email_meter_event_name' => env('STRIPE_EMAIL_METER_EVENT_NAME', 'email_sent'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'configuration_set' => env('SES_CONFIGURATION_SET', 'shared-pool'),
        'sns_topic_arn' => env('SES_SNS_TOPIC_ARN'),
        'managed_ip_pool' => env('SES_MANAGED_IP_POOL', 'pro-managed-pool'),
        'bounce_rate_threshold' => 0.05,
        'complaint_rate_threshold' => 0.001,
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Media Platforms (OAuth + Publishing)
    |--------------------------------------------------------------------------
    */

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => '/settings/social/facebook/callback',
        'scopes' => env('FACEBOOK_SCOPES', 'pages_manage_posts,pages_read_engagement,pages_show_list,instagram_basic,instagram_content_publish'),
    ],

    // Instagram Business API is accessed via Facebook OAuth - no separate credentials needed
    // The Instagram Business Account is linked to Facebook Pages
    'instagram' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),  // Uses Facebook app credentials
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => '/settings/social/instagram/callback',
        'scopes' => env('INSTAGRAM_SCOPES', 'pages_show_list,instagram_basic,instagram_content_publish'),
    ],

    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => '/settings/social/linkedin/callback',
        'scopes' => env('LINKEDIN_SCOPES', 'openid,profile,w_member_social'),
    ],

    'pinterest' => [
        'client_id' => env('PINTEREST_CLIENT_ID'),
        'client_secret' => env('PINTEREST_CLIENT_SECRET'),
        'redirect' => '/settings/social/pinterest/callback',
        'scopes' => env('PINTEREST_SCOPES', 'boards:read,pins:read,pins:write'),
    ],

    'tiktok' => [
        'client_id' => env('TIKTOK_CLIENT_KEY'),
        'client_secret' => env('TIKTOK_CLIENT_SECRET'),
        'redirect' => '/settings/social/tiktok/callback',
        'scopes' => env('TIKTOK_SCOPES', 'user.info.basic,video.upload,video.publish'),
    ],

];
