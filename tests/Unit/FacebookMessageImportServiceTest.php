<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\FacebookMessageImportService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FacebookMessageImportServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_instagram_attachment_placeholders_are_skipped_but_real_shared_text_is_kept(): void
    {
        $basePath = storage_path('framework/testing/instagram-import-'.uniqid().'/your_instagram_activity/messages');
        $threadPath = $basePath.'/inbox/example_thread';

        File::ensureDirectoryExists($threadPath);

        try {
            file_put_contents($threadPath.'/message_1.json', json_encode([
                'participants' => [
                    ['name' => 'Bojan Panic'],
                    ['name' => 'Rastko Todorovic'],
                ],
                'messages' => [
                    [
                        'sender_name' => 'Bojan Panic',
                        'timestamp_ms' => 3000,
                        'content' => 'Bojan sent an attachment.',
                        'share' => ['link' => 'https://instagram.com/reel/123'],
                    ],
                    [
                        'sender_name' => 'Rastko Todorovic',
                        'timestamp_ms' => 2000,
                        'content' => 'Nice',
                        'share' => ['link' => 'https://instagram.com/stories/123'],
                    ],
                    [
                        'sender_name' => 'Bojan Panic',
                        'timestamp_ms' => 1000,
                        'content' => 'Hahahaha',
                    ],
                ],
                'title' => 'Bojan Panic',
                'thread_path' => 'inbox/example_thread',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $result = app(FacebookMessageImportService::class)->importFromPath($basePath, 'Rastko Todorovic');

            $this->assertSame(1, $result['threads_found']);
            $this->assertSame(2, $result['messages_imported']);
            $this->assertSame(1, $result['messages_skipped']);
            $this->assertSame(1, Conversation::count());
            $this->assertSame(['Hahahaha', 'Nice'], Message::query()->orderBy('timestamp_ms')->pluck('content')->all());
        } finally {
            File::deleteDirectory(dirname(dirname($basePath)));
        }
    }
}
