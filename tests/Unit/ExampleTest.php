<?php

namespace Tests\Unit;

use App\Jobs\SendTelegramMessageJob;
use App\Services\ChatService;
use App\Services\TelegramNotificationService;
use App\Support\MediaStorage;
use Mockery;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_chat_service_extracts_http_and_telegram_links(): void
    {
        $links = ChatService::extractLinks('Yangi dars: https://repetitor.uz/kurslar va kanal t.me/ITblockbehruz!');

        $this->assertCount(2, $links);
        $this->assertSame('https://repetitor.uz/kurslar', $links[0]['url']);
        $this->assertSame('https://t.me/ITblockbehruz', $links[1]['url']);
    }

    public function test_media_storage_normalizes_urls_cleanly(): void
    {
        $this->assertSame('/media/courses/cover.jpg', MediaStorage::url('storage/app/public/courses/cover.jpg'));
        $this->assertSame('/media/teachers/a.png', MediaStorage::url('http://localhost:8000/storage/teachers/a.png'));
        $this->assertNull(MediaStorage::url('   '));
    }

    public function test_send_telegram_message_job_delegates_to_service(): void
    {
        $mock = Mockery::mock(TelegramNotificationService::class);
        $mock->shouldReceive('sendToChat')
            ->once()
            ->with('123456', 'Salom test', null)
            ->andReturn(true);

        $job = new SendTelegramMessageJob('Salom test', '123456');
        $job->handle($mock);

        $this->assertSame(3, $job->tries);
    }
}
