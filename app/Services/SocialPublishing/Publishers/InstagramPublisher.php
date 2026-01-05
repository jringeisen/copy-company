<?php

namespace App\Services\SocialPublishing\Publishers;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;

class InstagramPublisher extends AbstractPublisher
{
    public function getPlatform(): string
    {
        return 'instagram';
    }

    /**
     * @return array<string>
     */
    public function getRequiredScopes(): array
    {
        return ['instagram_basic', 'instagram_content_publish'];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(array $credentials): bool
    {
        return $this->hasRequiredFields($credentials, ['access_token', 'instagram_account_id']);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{success: bool, external_id: ?string, error: ?string}
     */
    public function publish(SocialPost $socialPost, array $credentials): array
    {
        try {
            $accountId = $credentials['instagram_account_id'];
            $accessToken = $credentials['access_token'];

            // Instagram requires an image for feed posts
            if (empty($socialPost->media)) {
                return $this->errorResponse('Instagram requires at least one image to publish.');
            }

            // Get media URL from the first media ID
            $mediaUrl = $this->getMediaUrl($socialPost->media[0]);

            if (! $mediaUrl) {
                return $this->errorResponse('Could not find the media file to publish.');
            }

            // Step 1: Create a media container
            $containerResponse = Http::post("https://graph.facebook.com/v18.0/{$accountId}/media", [
                'image_url' => $mediaUrl,
                'caption' => $socialPost->content,
                'access_token' => $accessToken,
            ]);

            if (! $containerResponse->successful()) {
                return $this->errorResponse('Failed to create media container: '.$containerResponse->body());
            }

            $containerId = $containerResponse->json('id');

            // Step 2: Publish the container
            $publishResponse = Http::post("https://graph.facebook.com/v18.0/{$accountId}/media_publish", [
                'creation_id' => $containerId,
                'access_token' => $accessToken,
            ]);

            if (! $publishResponse->successful()) {
                return $this->errorResponse('Failed to publish media: '.$publishResponse->body());
            }

            return $this->successResponse($publishResponse->json('id'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
