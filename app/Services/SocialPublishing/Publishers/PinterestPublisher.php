<?php

namespace App\Services\SocialPublishing\Publishers;

use App\Models\SocialPost;
use App\Services\SocialPublishing\Contracts\TokenRefreshableInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PinterestPublisher extends AbstractPublisher implements TokenRefreshableInterface
{
    protected string $apiBase = 'https://api.pinterest.com/v5';

    public function getPlatform(): string
    {
        return 'pinterest';
    }

    /**
     * @return array<string>
     */
    public function getRequiredScopes(): array
    {
        return ['boards:read', 'pins:read', 'pins:write'];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(array $credentials): bool
    {
        return $this->hasRequiredFields($credentials, ['access_token', 'board_id']);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{success: bool, external_id: ?string, error: ?string}
     */
    public function publish(SocialPost $socialPost, array $credentials): array
    {
        try {
            // Pinterest requires an image
            if (empty($socialPost->media)) {
                return $this->errorResponse('Pinterest requires at least one image to create a pin.');
            }

            $imageUrl = $this->getPublicMediaUrl($socialPost->media[0]);

            $payload = [
                'board_id' => $credentials['board_id'],
                'media_source' => [
                    'source_type' => 'image_url',
                    'url' => $imageUrl,
                ],
                'description' => $socialPost->content,
            ];

            if ($socialPost->link) {
                $payload['link'] = $socialPost->link;
            }

            $response = Http::withToken($credentials['access_token'])
                ->post("{$this->apiBase}/pins", $payload);

            if (! $response->successful()) {
                return $this->errorResponse('Pinterest API error: '.$response->body());
            }

            return $this->successResponse($response->json('id'));
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
        $response = Http::asForm()
            ->withBasicAuth(
                config('services.pinterest.client_id'),
                config('services.pinterest.client_secret')
            )
            ->post("{$this->apiBase}/oauth/token", [
                'grant_type' => 'refresh_token',
                'refresh_token' => $credentials['refresh_token'],
            ]);

        if (! $response->successful()) {
            throw new \Exception('Failed to refresh Pinterest token: '.$response->body());
        }

        $data = $response->json();

        return [
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'] ?? $credentials['refresh_token'],
            'expires_at' => now()->addSeconds($data['expires_in'])->toDateTimeString(),
        ];
    }

    protected function getPublicMediaUrl(string $path): string
    {
        return config('app.url').'/storage/'.$path;
    }
}
