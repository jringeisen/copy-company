<?php

namespace App\Services\SocialPublishing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PinterestBoardsService
{
    protected string $apiBase = 'https://api.pinterest.com/v5';

    /**
     * Fetch all boards that the user owns.
     *
     * @return array<int, array{id: string, name: string}>
     */
    public function fetchUserBoards(string $accessToken): array
    {
        try {
            $response = Http::withToken($accessToken)
                ->get("{$this->apiBase}/boards");

            if (! $response->successful()) {
                Log::error('Failed to fetch Pinterest boards', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $boards = $response->json('items', []);

            $result = [];
            foreach ($boards as $board) {
                $result[] = [
                    'id' => $board['id'],
                    'name' => $board['name'],
                ];
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to fetch Pinterest boards', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
