<?php

namespace App\Services\SocialPublishing\Publishers;

use App\Models\Media;
use App\Services\SocialPublishing\Contracts\PublisherInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

abstract class AbstractPublisher implements PublisherInterface
{
    /**
     * Check if credentials contain required fields.
     *
     * @param  array<string, mixed>  $credentials
     * @param  array<string>  $requiredFields
     */
    protected function hasRequiredFields(array $credentials, array $requiredFields): bool
    {
        foreach ($requiredFields as $field) {
            if (empty($credentials[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if token is expired or will expire soon.
     *
     * @param  array<string, mixed>  $credentials
     */
    protected function isTokenExpired(array $credentials, int $bufferMinutes = 5): bool
    {
        if (empty($credentials['expires_at'])) {
            return false;
        }

        $expiresAt = Carbon::parse($credentials['expires_at']);

        return now()->addMinutes($bufferMinutes)->gte($expiresAt);
    }

    /**
     * Log a publishing error.
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::error("[{$this->getPlatform()}Publisher] {$message}", $context);
    }

    /**
     * Log successful publishing.
     */
    protected function logSuccess(string $externalId, array $context = []): void
    {
        Log::info("[{$this->getPlatform()}Publisher] Published successfully", array_merge([
            'external_id' => $externalId,
        ], $context));
    }

    /**
     * Build a success response.
     *
     * @return array{success: true, external_id: string, error: null}
     */
    protected function successResponse(string $externalId): array
    {
        $this->logSuccess($externalId);

        return [
            'success' => true,
            'external_id' => $externalId,
            'error' => null,
        ];
    }

    /**
     * Build an error response.
     *
     * @return array{success: false, external_id: null, error: string}
     */
    protected function errorResponse(string $error, array $context = []): array
    {
        $this->logError($error, $context);

        return [
            'success' => false,
            'external_id' => null,
            'error' => $error,
        ];
    }

    /**
     * Get a publicly accessible URL for a media item by ID.
     *
     * Uses a longer expiration (4 hours) to give external platforms time to fetch the image.
     */
    protected function getMediaUrl(int $mediaId): ?string
    {
        $media = Media::find($mediaId);

        if (! $media) {
            return null;
        }

        // Generate a signed URL with longer expiration for external platform access
        return Storage::disk($media->disk)->temporaryUrl(
            $media->path,
            now()->addHours(4)
        );
    }
}
