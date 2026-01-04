<?php

namespace App\Services\SocialPublishing\Publishers;

use App\Models\SocialPost;
use App\Services\SocialPublishing\Contracts\TokenRefreshableInterface;
use Carbon\Carbon;
use Facebook\Facebook;

class FacebookPublisher extends AbstractPublisher implements TokenRefreshableInterface
{
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
            $fb = new Facebook([
                'app_id' => config('services.facebook.client_id'),
                'app_secret' => config('services.facebook.client_secret'),
                'default_graph_version' => 'v18.0',
            ]);

            $pageId = $credentials['page_id'];
            $pageToken = $credentials['page_access_token'] ?? $credentials['access_token'];

            $data = ['message' => $socialPost->content];

            // Add link if present
            if ($socialPost->link) {
                $data['link'] = $socialPost->link;
            }

            $response = $fb->post("/{$pageId}/feed", $data, $pageToken);
            $graphNode = $response->getGraphNode();

            return $this->successResponse($graphNode['id']);
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
        $fb = new Facebook([
            'app_id' => config('services.facebook.client_id'),
            'app_secret' => config('services.facebook.client_secret'),
            'default_graph_version' => 'v18.0',
        ]);

        $oAuth2Client = $fb->getOAuth2Client();
        $longLivedToken = $oAuth2Client->getLongLivedAccessToken($credentials['access_token']);

        return [
            'access_token' => $longLivedToken->getValue(),
            'refresh_token' => null,
            'expires_at' => $longLivedToken->getExpiresAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
