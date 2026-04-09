<?php

namespace Tests\Unit;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\WhatsappChatImportService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class WhatsappChatImportServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_it_imports_a_whatsapp_export_with_multiline_messages(): void
    {
        $path = storage_path('framework/testing/whatsapp-import-'.uniqid().'/_chat.txt');

        File::ensureDirectoryExists(dirname($path));

        try {
            file_put_contents($path, implode("\n", [
                '[20. 3. 2026., 5:40:04 PM] Mama: Messages and calls are end-to-end encrypted. Only people in this chat can read, listen to, or share them.',
                '[25. 3. 2026., 10:08:41 PM] Mama: Bijeljina',
                '[25. 3. 2026., 10:08:47 PM] Rastko: Sutra u Bijeljini izgleda sunano.',
                'Oko 10C, bez padavina.',
                '',
                'Vrijeme je skroz pristojno.',
                '[25. 3. 2026., 10:09:28 PM] Mama: <Media omitted>',
            ]));

            $result = app(WhatsappChatImportService::class)->importFromPath($path, 'Rastko');

            $this->assertSame(1, $result['threads_found']);
            $this->assertSame(2, $result['messages_imported']);
            $this->assertSame(2, $result['messages_skipped']);
            $this->assertSame(1, Conversation::count());
            $this->assertSame('whatsapp', Conversation::query()->firstOrFail()->source);
            $this->assertSame(
                [
                    'Bijeljina',
                    "Sutra u Bijeljini izgleda sunano.\nOko 10C, bez padavina.\nVrijeme je skroz pristojno.",
                ],
                Message::query()->orderBy('timestamp_ms')->pluck('content')->all()
            );
        } finally {
            File::deleteDirectory(dirname($path));
        }
    }
}
