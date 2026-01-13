<?php

namespace Tests\Unit\Services\SocialPublishing;

use App\Services\SocialPublishing\PinterestBoardsService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PinterestBoardsServiceTest extends TestCase
{
    public function test_fetch_user_boards_returns_array_of_boards(): void
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response([
                'items' => [
                    ['id' => 'board_123', 'name' => 'My Travel Board'],
                    ['id' => 'board_456', 'name' => 'Recipes'],
                ],
            ], 200),
        ]);

        $service = new PinterestBoardsService;
        $result = $service->fetchUserBoards('test_token');

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('board_123', $result[0]['id']);
        $this->assertEquals('My Travel Board', $result[0]['name']);
        $this->assertEquals('board_456', $result[1]['id']);
        $this->assertEquals('Recipes', $result[1]['name']);
    }

    public function test_fetch_user_boards_returns_empty_array_on_api_failure(): void
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response([
                'error' => 'Unauthorized',
            ], 401),
        ]);

        $service = new PinterestBoardsService;
        $result = $service->fetchUserBoards('invalid_token');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_fetch_user_boards_returns_empty_array_on_exception(): void
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response(null, 500),
        ]);

        $service = new PinterestBoardsService;
        $result = $service->fetchUserBoards('test_token');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_fetch_user_boards_handles_empty_items(): void
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $service = new PinterestBoardsService;
        $result = $service->fetchUserBoards('test_token');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_fetch_user_boards_returns_empty_array_on_network_exception(): void
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $service = new PinterestBoardsService;
        $result = $service->fetchUserBoards('test_token');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_fetch_user_boards_handles_missing_items_key(): void
    {
        Http::fake([
            'https://api.pinterest.com/v5/boards' => Http::response([
                'data' => [], // Wrong key - items is expected
            ], 200),
        ]);

        $service = new PinterestBoardsService;
        $result = $service->fetchUserBoards('test_token');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
}
