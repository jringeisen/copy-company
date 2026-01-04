<?php

namespace App\Services\SocialPublishing\Publishers;

use App\Models\SocialPost;
use App\Services\SocialPublishing\Contracts\TokenRefreshableInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class FacebookPublisher extends AbstractPublisher implements TokenRefreshableInterface
{
    protected function getHttpClient(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::baseUrl('https://graph.facebook.com/v18.0');

        // Disable SSL verification in local development only
        if (app()->environment('local')) {
            $client = $client->withOptions(['verify' => false]);
        }

        return $client;
    }

    public function getPlatform(): string
    {
        return 'facebook';
    }

    /**
     * @return array<string>
     */
    public function getRequiredScopes(): array
    {
        return ['pages_manage_posts', 'pages_read_engagement'];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(array $credentials): bool
    {
        return $this->hasRequiredFields($credentials, ['access_token', 'page_id']);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{success: bool, external_id: ?string, error: ?string}
     */
    public function publish(SocialPost $socialPost, array $credentials): array
    {
        try {
            $pageId = $credentials['page_id'];
            $pageToken = $credentials['page_access_token'] ?? $credentials['access_token'];

            $data = [
                'message' => $socialPost->content,
                'access_token' => $pageToken,
            ];

            // Add link if present
            if ($socialPost->link) {
                $data['link'] = $socialPost->link;
            }

            $response = $this->getHttpClient()->post("/{$pageId}/feed", $data);

            if (! $response->successful()) {
                $error = $response->json('error.message', 'Unknown Facebook API error');

                return $this->errorResponse($error);
            }

            $postId = $response->json('id');

            return $this->successResponse($postId);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function tokenNeedsRefresh(array $credentials): bool
    {
        if (empty($credentials['expires_at'])) {
            return false;
        }

        $expiresAt = Carbon::parse($credentials['expires_at']);

        return now()->addDay()->gte($expiresAt);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string}
     */
    public function refreshToken(array $credentials): array
    {
        $response = $this->getHttpClient()->get('/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => config('services.facebook.client_id'),
            'client_secret' => config('services.facebook.client_secret'),
            'fb_exchange_token' => $credentials['access_token'],
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Failed to refresh Facebook token: '.$response->json('error.message', 'Unknown error'));
        }

        $expiresIn = $response->json('expires_in');

        return [
            'access_token' => $response->json('access_token'),
            'refresh_token' => null,
            'expires_at' => $expiresIn ? now()->addSeconds($expiresIn)->format('Y-m-d H:i:s') : null,
        ];
    }
}
