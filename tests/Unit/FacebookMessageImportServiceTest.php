<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\FacebookMessageImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FacebookMessageImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_messages_from_facebook_export_directory(): void
    {
        $basePath = storage_path('framework/testing/facebook-import-'.uniqid());
        $threadPath = $basePath.'/inbox/jane_doe_123';

        File::ensureDirectoryExists($threadPath);

        file_put_contents($threadPath.'/message_1.json', json_encode([
            'participants' => [
                ['name' => 'Jane Doe'],
                ['name' => 'Rastko Todorovic'],
            ],
            'messages' => [
                [
                    'sender_name' => 'Jane Doe',
                    'timestamp_ms' => 1718032680308,
                    'content' => 'Newest message',
                ],
                [
                    'sender_name' => 'Rastko Todorovic',
                    'timestamp_ms' => 1718032600000,
                    'content' => 'Older message',
                ],
                [
                    'sender_name' => 'Jane Doe',
                    'timestamp_ms' => 1718032500000,
                    'call_duration' => 120,
                ],
            ],
            'title' => 'Jane Doe',
            'thread_path' => 'inbox/jane_doe_123',
        ], JSON_THROW_ON_ERROR));

        $service = app(FacebookMessageImportService::class);

        try {
            $result = $service->importFromPath($basePath, 'Rastko Todorovic');
        } finally {
            File::deleteDirectory($basePath);
        }

        $this->assertSame(1, $result['threads_found']);
        $this->assertSame(1, $result['conversations']);
        $this->assertSame(2, $result['messages_imported']);
        $this->assertSame(1, $result['messages_skipped']);
        $this->assertCount(1, Conversation::all());
        $this->assertCount(2, Message::all());
        $this->assertTrue(Message::where('sender_name', 'Rastko Todorovic')->firstOrFail()->is_from_me);
    }

    public function test_import_is_idempotent_for_same_export(): void
    {
        $basePath = storage_path('framework/testing/facebook-import-'.uniqid());
        $threadPath = $basePath.'/inbox/jane_doe_123';

        File::ensureDirectoryExists($threadPath);

        file_put_contents($threadPath.'/message_1.json', json_encode([
            'participants' => [
                ['name' => 'Jane Doe'],
                ['name' => 'Rastko Todorovic'],
            ],
            'messages' => [
                [
                    'sender_name' => 'Jane Doe',
                    'timestamp_ms' => 1718032680308,
                    'content' => 'Hello again',
                ],
            ],
            'title' => 'Jane Doe',
            'thread_path' => 'inbox/jane_doe_123',
        ], JSON_THROW_ON_ERROR));

        $service = app(FacebookMessageImportService::class);

        try {
            $service->importFromPath($basePath, 'Rastko Todorovic');
            $service->importFromPath($basePath, 'Rastko Todorovic');
        } finally {
            File::deleteDirectory($basePath);
        }

        $this->assertCount(1, Conversation::all());
        $this->assertCount(1, Message::all());
    }
}
