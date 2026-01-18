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
