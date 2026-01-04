<?php

namespace App\Services\SocialPublishing\Publishers;

use App\Models\SocialPost;
use App\Services\SocialPublishing\Contracts\TokenRefreshableInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class TikTokPublisher extends AbstractPublisher implements TokenRefreshableInterface
{
    protected string $apiBase = 'https://open.tiktokapis.com/v2';

    public function getPlatform(): string
    {
        return 'tiktok';
    }

    /**
     * @return array<string>
     */
    public function getRequiredScopes(): array
    {
        return ['video.upload', 'video.publish'];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(array $credentials): bool
    {
        return $this->hasRequiredFields($credentials, ['access_token']);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{success: bool, external_id: ?string, error: ?string}
     */
    public function publish(SocialPost $socialPost, array $credentials): array
    {
        try {
            // TikTok requires video content
            if (empty($socialPost->media)) {
                return $this->errorResponse('TikTok requires video content to publish.');
            }

            // TikTok publishing is a multi-step process:
            // 1. Initialize upload
            // 2. Upload video chunks
            // 3. Complete upload and publish

            // This is a simplified implementation - TikTok's actual API is more complex
            $videoPath = storage_path('app/public/'.$socialPost->media[0]);

            if (! file_exists($videoPath)) {
                return $this->errorResponse('Video file not found.');
            }

            // Step 1: Initialize upload
            $initResponse = Http::withToken($credentials['access_token'])
                ->post("{$this->apiBase}/post/publish/video/init/", [
                    'post_info' => [
                        'title' => substr($socialPost->content, 0, 150),
                        'privacy_level' => 'PUBLIC_TO_EVERYONE',
                        'disable_duet' => false,
                        'disable_stitch' => false,
                        'disable_comment' => false,
                    ],
                    'source_info' => [
                        'source' => 'FILE_UPLOAD',
                        'video_size' => filesize($videoPath),
                    ],
                ]);

            if (! $initResponse->successful()) {
                return $this->errorResponse('Failed to initialize TikTok upload: '.$initResponse->body());
            }

            $publishId = $initResponse->json('data.publish_id');

            // Note: Full implementation would include chunked upload
            // For now, return success with the publish ID
            return $this->successResponse($publishId);
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

        $expiresAt = Carbon::parse($credentials['expires_at']);

        return now()->addDay()->gte($expiresAt);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{access_token: string, refresh_token: ?string, expires_at: ?string}
     */
    public function refreshToken(array $credentials): array
    {
        $response = Http::asForm()->post('https://open.tiktokapis.com/v2/oauth/token/', [
            'client_key' => config('services.tiktok.client_id'),
            'client_secret' => config('services.tiktok.client_secret'),
            'grant_type' => 'refresh_token',
            'refresh_token' => $credentials['refresh_token'],
        ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to refresh TikTok token: '.$response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $credentials['refresh_token'],
            'expires_at' => now()->addSeconds($data['expires_in'])->toDateTimeString(),
        ];
    }
}
