<?php

namespace App\Services\SocialPublishing\Publishers;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;

class LinkedInPublisher extends AbstractPublisher
{
    public function getPlatform(): string
    {
        return 'linkedin';
    }

    /**
     * @return array<string>
     */
    public function getRequiredScopes(): array
    {
        return ['w_member_social', 'r_liteprofile'];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(array $credentials): bool
    {
        return $this->hasRequiredFields($credentials, ['access_token', 'person_id']);
    }

    /**
     * @param  array<string, mixed>  $credentials
     * @return array{success: bool, external_id: ?string, error: ?string}
     */
    public function publish(SocialPost $socialPost, array $credentials): array
    {
        try {
            $personUrn = 'urn:li:person:'.$credentials['person_id'];

            $payload = [
                'author' => $personUrn,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $socialPost->content,
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ];

            // Add link if present
            if ($socialPost->link) {
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $payload['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [
                    [
                        'status' => 'READY',
                        'originalUrl' => $socialPost->link,
                    ],
                ];
            }

            $response = Http::withToken($credentials['access_token'])
                ->withHeaders(['X-Restli-Protocol-Version' => '2.0.0'])
                ->post('https://api.linkedin.com/v2/ugcPosts', $payload);

            if (! $response->successful()) {
                return $this->errorResponse('LinkedIn API error: '.$response->body());
            }

            $postId = $response->json('id');

            return $this->successResponse($postId);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
