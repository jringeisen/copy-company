<?php

namespace App\Services\SocialPublishing;

use Facebook\Facebook;
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
            $fb = new Facebook([
                'app_id' => config('services.facebook.client_id'),
                'app_secret' => config('services.facebook.client_secret'),
                'default_graph_version' => 'v18.0',
            ]);

            $response = $fb->get('/me/accounts', $userAccessToken);
            $pages = $response->getGraphEdge();

            $result = [];
            foreach ($pages as $page) {
                $result[] = [
                    'id' => $page['id'],
                    'name' => $page['name'],
                    'access_token' => $page['access_token'],
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Facebook pages', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
