<?php

namespace App\Services\SocialPublishing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramAccountsService
{
    /**
     * Fetch Instagram Business Accounts connected to Facebook Pages.
     *
     * Instagram Business accounts are linked to Facebook Pages, so we need to:
     * 1. Get all Facebook Pages the user manages
     * 2. For each page, check if it has a connected Instagram Business Account
     *
     * @return array<int, array{id: string, name: string, username: string, page_id: string, page_name: string, access_token: string}>
     */
    public function fetchInstagramAccounts(string $userAccessToken): array
    {
        try {
            $client = Http::baseUrl('https://graph.facebook.com/v18.0');

            // Disable SSL verification in local development only
            if (app()->environment('local')) {
                $client = $client->withOptions(['verify' => false]);
            }

            // First, get all Facebook Pages the user manages
            $pagesResponse = $client->get('/me/accounts', [
                'access_token' => $userAccessToken,
                'fields' => 'id,name,access_token,instagram_business_account',
            ]);

            if (! $pagesResponse->successful()) {
                Log::error('Failed to fetch Facebook pages for Instagram', [
                    'status' => $pagesResponse->status(),
                    'body' => $pagesResponse->body(),
                ]);

                return [];
            }

            $pages = $pagesResponse->json('data', []);
            $instagramAccounts = [];

            foreach ($pages as $page) {
                // Skip pages without connected Instagram Business Account
                if (empty($page['instagram_business_account']['id'])) {
                    continue;
                }

                $instagramId = $page['instagram_business_account']['id'];

                // Fetch Instagram account details
                $igResponse = $client->get("/{$instagramId}", [
                    'access_token' => $page['access_token'],
                    'fields' => 'id,username,name,profile_picture_url',
                ]);

                if ($igResponse->successful()) {
                    $igData = $igResponse->json();

                    $instagramAccounts[] = [
                        'id' => $igData['id'],
                        'name' => $igData['name'] ?? $igData['username'],
                        'username' => $igData['username'],
                        'profile_picture_url' => $igData['profile_picture_url'] ?? null,
                        'page_id' => $page['id'],
                        'page_name' => $page['name'],
                        'access_token' => $page['access_token'],
                    ];
                }
            }

            Log::info('Fetched Instagram Business Accounts', [
                'count' => count($instagramAccounts),
            ]);

            return $instagramAccounts;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Instagram accounts', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
