<?php

namespace App\Services\SocialPublishing\Publishers;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Models\SocialPost;
use App\Services\SocialPublishing\Contracts\TokenRefreshableInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TwitterPublisher extends AbstractPublisher implements TokenRefreshableInterface
{
    public function getPlatform(): string
    {
        return 'twitter';
    }

    /**
     * @return array<string>
     */
    public function getRequiredScopes(): array
    {
        return ['tweet.read', 'tweet.write', 'users.read', 'offline.access'];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(array $credentials): bool
    {
        if (! $this->hasRequiredFields($credentials, ['access_token'])) {
            return false;
        }

        return ! $this->isTokenExpired($credentials);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{success: bool, external_id: ?string, error: ?string}
     */
    public function publish(SocialPost $socialPost, array $credentials): array
    {
        try {
            $twitter = new TwitterOAuth(
                config('services.twitter.client_id'),
                config('services.twitter.client_secret'),
                null,
                $credentials['access_token']
            );
            $twitter->setApiVersion('2');

            $payload = ['text' => $socialPost->content];

            // Add media if present
            if (! empty($socialPost->media)) {
                $mediaIds = $this->uploadMedia($twitter, $socialPost->media);
                if (! empty($mediaIds)) {
                    $payload['media'] = ['media_ids' => $mediaIds];
                }
            }

            $response = $twitter->post('tweets', $payload, ['jsonPayload' => true]);

            if (isset($response->data->id)) {
                return $this->successResponse($response->data->id);
            }

            $error = $response->detail ?? $response->title ?? 'Unknown Twitter API error';

            return $this->errorResponse($error, ['response' => $response]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function tokenNeedsRefresh(array $credentials): bool
    {
        if (empty($credentials['expires_at']) || empty($credentials['refresh_token'])) {
            return false;
        }

        // Refresh if expiring in the next hour
        $expiresAt = Carbon::parse($credentials['expires_at']);

        return now()->addHour()->gte($expiresAt);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string}
     */
    public function refreshToken(array $credentials): array
    {
        $response = Http::asForm()->post('https://api.twitter.com/2/oauth2/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $credentials['refresh_token'],
            'client_id' => config('services.twitter.client_id'),
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to refresh Twitter token: '.$response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $credentials['refresh_token'],
            'expires_at' => now()->addSeconds($data['expires_in'])->toDateTimeString(),
        ];
    }

    /**
     * Upload media files to Twitter.
     *
     * @param  array<string>  $mediaPaths
     * @return array<string>
     */
    protected function uploadMedia(TwitterOAuth $twitter, array $mediaPaths): array
    {
        $mediaIds = [];

        // Switch to v1.1 for media upload (v2 doesn't support it directly yet)
        $twitter->setApiVersion('1.1');

        foreach ($mediaPaths as $path) {
            $fullPath = storage_path('app/public/'.$path);

            if (! file_exists($fullPath)) {
                continue;
            }

            try {
                $media = $twitter->upload('media/upload', ['media' => $fullPath]);

                if (isset($media->media_id_string)) {
                    $mediaIds[] = $media->media_id_string;
                }
            } catch (\Exception $e) {
                $this->logError('Media upload failed: '.$e->getMessage());
            }
        }

        // Switch back to v2
        $twitter->setApiVersion('2');

        return $mediaIds;
    }
}
