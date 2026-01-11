<?php

namespace Tests\Unit\Services\SocialPublishing;

use App\Enums\SocialPlatform;
use App\Services\SocialPublishing\Contracts\PublisherInterface;
use App\Services\SocialPublishing\PublisherFactory;
use App\Services\SocialPublishing\Publishers\FacebookPublisher;
use App\Services\SocialPublishing\Publishers\InstagramPublisher;
use App\Services\SocialPublishing\Publishers\LinkedInPublisher;
use App\Services\SocialPublishing\Publishers\PinterestPublisher;
use App\Services\SocialPublishing\Publishers\TikTokPublisher;
use InvalidArgumentException;
use Tests\TestCase;

class PublisherFactoryTest extends TestCase
{
    public function test_make_creates_facebook_publisher(): void
    {
        $publisher = PublisherFactory::make('facebook');

        $this->assertInstanceOf(FacebookPublisher::class, $publisher);
        $this->assertInstanceOf(PublisherInterface::class, $publisher);
    }

    public function test_make_creates_instagram_publisher(): void
    {
        $publisher = PublisherFactory::make('instagram');

        $this->assertInstanceOf(InstagramPublisher::class, $publisher);
    }

    public function test_make_creates_linkedin_publisher(): void
    {
        $publisher = PublisherFactory::make('linkedin');

        $this->assertInstanceOf(LinkedInPublisher::class, $publisher);
    }

    public function test_make_creates_pinterest_publisher(): void
    {
        $publisher = PublisherFactory::make('pinterest');

        $this->assertInstanceOf(PinterestPublisher::class, $publisher);
    }

    public function test_make_creates_tiktok_publisher(): void
    {
        $publisher = PublisherFactory::make('tiktok');

        $this->assertInstanceOf(TikTokPublisher::class, $publisher);
    }

    public function test_make_throws_exception_for_unsupported_platform(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported publishing platform: unsupported');

        PublisherFactory::make('unsupported');
    }

    public function test_from_enum_creates_correct_publisher(): void
    {
        $publisher = PublisherFactory::fromEnum(SocialPlatform::Facebook);

        $this->assertInstanceOf(FacebookPublisher::class, $publisher);
        $this->assertEquals('facebook', $publisher->getPlatform());
    }

    public function test_is_supported_returns_true_for_supported_platforms(): void
    {
        $this->assertTrue(PublisherFactory::isSupported('facebook'));
        $this->assertTrue(PublisherFactory::isSupported('instagram'));
        $this->assertTrue(PublisherFactory::isSupported('linkedin'));
        $this->assertTrue(PublisherFactory::isSupported('pinterest'));
        $this->assertTrue(PublisherFactory::isSupported('tiktok'));
    }

    public function test_is_supported_returns_false_for_unsupported_platforms(): void
    {
        $this->assertFalse(PublisherFactory::isSupported('unsupported'));
        $this->assertFalse(PublisherFactory::isSupported('youtube'));
        $this->assertFalse(PublisherFactory::isSupported('twitter'));
        $this->assertFalse(PublisherFactory::isSupported(''));
    }

    public function test_supported_platforms_returns_all_platforms(): void
    {
        $platforms = PublisherFactory::supportedPlatforms();

        $this->assertContains('facebook', $platforms);
        $this->assertContains('instagram', $platforms);
        $this->assertContains('linkedin', $platforms);
        $this->assertContains('pinterest', $platforms);
        $this->assertContains('tiktok', $platforms);
        $this->assertCount(5, $platforms);
    }

    public function test_all_returns_all_publisher_instances(): void
    {
        $publishers = PublisherFactory::all();

        $this->assertCount(5, $publishers);
        $this->assertArrayHasKey('facebook', $publishers);
        $this->assertArrayHasKey('instagram', $publishers);

        foreach ($publishers as $publisher) {
            $this->assertInstanceOf(PublisherInterface::class, $publisher);
        }
    }

    public function test_publishers_return_correct_platform_identifier(): void
    {
        $platforms = ['facebook', 'instagram', 'linkedin', 'pinterest', 'tiktok'];

        foreach ($platforms as $platform) {
            $publisher = PublisherFactory::make($platform);
            $this->assertEquals($platform, $publisher->getPlatform());
        }
    }

    public function test_publishers_have_required_scopes(): void
    {
        $publishers = PublisherFactory::all();

        foreach ($publishers as $publisher) {
            $scopes = $publisher->getRequiredScopes();
            $this->assertIsArray($scopes);
            $this->assertNotEmpty($scopes);
        }
    }
}
