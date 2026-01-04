<?php

namespace App\Services\SocialPublishing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPagesService
{
    /**
     * Fetch all pages that the user manages.
     *
     * @return array<int, array{id: string, name: string, access_token: string}>
     */
    public function fetchUserPages(string $userAccessToken): array
    {
        try {
            $client = Http::baseUrl('https://graph.facebook.com/v18.0');

            // Disable SSL verification in local development only
            if (app()->environment('local')) {
                $client = $client->withOptions(['verify' => false]);
            }

            $response = $client->get('/me/accounts', [
                'access_token' => $userAccessToken,
                'fields' => 'id,name,access_token',
            ]);

            Log::info('Facebook pages API response', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            if (! $response->successful()) {
                Log::error('Facebook API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json('data', []);

            return array_map(fn (array $page): array => [
                'id' => $page['id'],
                'name' => $page['name'],
                'access_token' => $page['access_token'],
            ], $data);
        } catch (\Exception $e) {
            Log::error('Failed to fetch Facebook pages', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
