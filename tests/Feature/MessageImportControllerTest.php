<?php

namespace Tests\Feature;

use App\Models\MessageImportRun;
use App\Models\User;
use App\Services\MessageEmbeddingService;
use App\Services\MessageImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MessageImportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_page_requires_authentication(): void
    {
        $this->get(route('imports.index'))
            ->assertRedirect(route('login'));
    }

    public function test_import_page_renders_export_instructions(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('imports.index'));

        $response->assertOk();
        $response->assertSee('Import Message History');
        $response->assertSee('Download your information');
        $response->assertSee('Messages');
        $response->assertSee('Instagram');
    }

    public function test_store_validates_archive_is_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('imports.store'), [
                'me_name' => 'Rastko Todorovic',
            ])
            ->assertSessionHasErrors(['archive', 'source_path']);
    }

    public function test_store_shows_large_upload_message_when_request_body_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withServerVariables(['CONTENT_LENGTH' => '1007889697'])
            ->post(route('imports.store'), [])
            ->assertSessionHasErrors([
                'archive' => 'The upload did not reach Laravel. This usually happens with very large export files. Use the local export path field instead of browser upload for huge archives.',
            ]);
    }

    public function test_store_accepts_instagram_local_source_path_and_runs_import(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $relativeSourcePath = 'storage/framework/testing/instagram-source-'.uniqid().'/your_instagram_activity/messages';
        $sourcePath = base_path($relativeSourcePath);
        $threadPath = $sourcePath.'/inbox/example_thread';

        File::ensureDirectoryExists($threadPath);

        try {
            file_put_contents($threadPath.'/message_1.json', '{}');

            $this->mock(MessageImportService::class, function ($mock) use ($sourcePath) {
                $mock->shouldReceive('importFromPath')
                    ->once()
                    ->with($sourcePath, 'Rastko Todorovic')
                    ->andReturn([
                        'threads_found' => 1,
                        'conversations' => 1,
                        'messages_imported' => 8,
                        'messages_skipped' => 2,
                    ]);
            });

            $this->mock(MessageEmbeddingService::class, function ($mock) {
                $mock->shouldReceive('generate')
                    ->once()
                    ->with(true)
                    ->andReturn([
                        'messages_indexed' => 8,
                        'total_messages' => 8,
                        'batch_size' => 100,
                    ]);
            });

            $response = $this->actingAs($user)->post(route('imports.store'), [
                'source_path' => $relativeSourcePath,
                'me_name' => 'Rastko Todorovic',
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Import completed: 8 messages absorbed, 2 skipped, 1 conversations updated. Embeddings were rebuilt.');

            $run = MessageImportRun::query()->latest()->first();

            $this->assertNotNull($run);
            $this->assertSame('completed', $run->status);
            $this->assertSame('messages', $run->uploaded_filename);
            $this->assertSame('local://'.$sourcePath, $run->storage_path);
        } finally {
            File::deleteDirectory(base_path(dirname(dirname($relativeSourcePath))));
        }
    }

    public function test_store_runs_import_synchronously_and_persists_run(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $archive = $this->makeArchive();

        $this->mock(MessageImportService::class, function ($mock) {
            $mock->shouldReceive('importFromPath')
                ->once()
                ->andReturn([
                    'threads_found' => 1,
                    'conversations' => 2,
                    'messages_imported' => 10,
                    'messages_skipped' => 3,
                ]);
        });

        $this->mock(MessageEmbeddingService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->with(true)
                ->andReturn([
                    'messages_indexed' => 10,
                    'total_messages' => 10,
                    'batch_size' => 100,
                ]);
        });

        $response = $this->actingAs($user)->post(route('imports.store'), [
            'archive' => $archive,
            'me_name' => 'Rastko Todorovic',
            'replace_existing' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Import completed: 10 messages absorbed, 3 skipped, 2 conversations updated. Embeddings were rebuilt.');

        $run = MessageImportRun::query()->latest()->first();

        $this->assertNotNull($run);
        $this->assertSame('completed', $run->status);
        $this->assertSame('facebook-messages.zip', $run->uploaded_filename);
        $this->assertTrue($run->replace_existing);
        $this->assertSame(10, $run->messages_imported);
        $this->assertSame(3, $run->messages_skipped);
        $this->assertSame(2, $run->conversations_count);
        Storage::disk('local')->assertMissing($run->storage_path);
    }

    public function test_store_accepts_whatsapp_local_source_path_and_runs_import(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $relativeSourcePath = 'data/whatsapp-test-'.uniqid().'/_chat.txt';
        $sourcePath = base_path($relativeSourcePath);

        File::ensureDirectoryExists(dirname($sourcePath));

        try {
            file_put_contents($sourcePath, '[25. 3. 2026., 10:08:41 PM] Mama: Bijeljina');

            $this->mock(MessageImportService::class, function ($mock) use ($sourcePath) {
                $mock->shouldReceive('importFromPath')
                    ->once()
                    ->with($sourcePath, 'Rastko Todorovic')
                    ->andReturn([
                        'threads_found' => 1,
                        'conversations' => 1,
                        'messages_imported' => 8,
                        'messages_skipped' => 2,
                    ]);
            });

            $this->mock(MessageEmbeddingService::class, function ($mock) {
                $mock->shouldReceive('generate')
                    ->once()
                    ->with(true)
                    ->andReturn([
                        'messages_indexed' => 8,
                        'total_messages' => 8,
                        'batch_size' => 100,
                    ]);
            });

            $response = $this->actingAs($user)->post(route('imports.store'), [
                'source_path' => $relativeSourcePath,
                'me_name' => 'Rastko Todorovic',
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Import completed: 8 messages absorbed, 2 skipped, 1 conversations updated. Embeddings were rebuilt.');

            $run = MessageImportRun::query()->latest()->first();

            $this->assertNotNull($run);
            $this->assertSame('completed', $run->status);
            $this->assertSame('_chat.txt', $run->uploaded_filename);
            $this->assertSame('local://'.$sourcePath, $run->storage_path);
        } finally {
            File::deleteDirectory(dirname($sourcePath));
        }
    }

    public function test_store_accepts_whatsapp_export_upload(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $archive = UploadedFile::fake()->createWithContent('_chat.txt', '[25. 3. 2026., 10:08:41 PM] Mama: Bijeljina');

        $this->mock(MessageImportService::class, function ($mock) {
            $mock->shouldReceive('importFromPath')
                ->once()
                ->withArgs(function (string $path, string $meName) {
                    return $meName === 'Rastko Todorovic'
                        && is_file($path)
                        && str_ends_with($path, '.txt');
                })
                ->andReturn([
                    'threads_found' => 1,
                    'conversations' => 1,
                    'messages_imported' => 3,
                    'messages_skipped' => 1,
                ]);
        });

        $this->mock(MessageEmbeddingService::class, function ($mock) {
            $mock->shouldReceive('generate')
                ->once()
                ->with(true)
                ->andReturn([
                    'messages_indexed' => 3,
                    'total_messages' => 3,
                    'batch_size' => 100,
                ]);
        });

        $response = $this->actingAs($user)->post(route('imports.store'), [
            'archive' => $archive,
            'me_name' => 'Rastko Todorovic',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Import completed: 3 messages absorbed, 1 skipped, 1 conversations updated. Embeddings were rebuilt.');

        $run = MessageImportRun::query()->latest()->first();

        $this->assertNotNull($run);
        $this->assertSame('completed', $run->status);
        $this->assertSame('_chat.txt', $run->uploaded_filename);
        Storage::disk('local')->assertMissing($run->storage_path);
    }

    public function test_store_rejects_new_upload_when_import_is_running(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        MessageImportRun::create([
            'status' => 'processing',
            'uploaded_filename' => 'existing.zip',
            'storage_path' => 'message-imports/existing.zip',
            'me_name' => 'Rastko Todorovic',
        ]);

        $response = $this->actingAs($user)->post(route('imports.store'), [
            'archive' => UploadedFile::fake()->create('facebook-messages.zip', 128, 'application/zip'),
            'me_name' => 'Rastko Todorovic',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertCount(1, MessageImportRun::all());
    }

    public function test_store_accepts_local_source_path_and_runs_import(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $relativeSourcePath = 'storage/framework/testing/facebook-source-'.uniqid().'/your_facebook_activity/messages';
        $sourcePath = base_path($relativeSourcePath);
        $threadPath = $sourcePath.'/inbox/example_thread';

        File::ensureDirectoryExists($threadPath);

        try {
            file_put_contents($threadPath.'/message_1.json', '{}');

            $this->mock(MessageImportService::class, function ($mock) use ($sourcePath) {
                $mock->shouldReceive('importFromPath')
                    ->once()
                    ->with($sourcePath, 'Rastko Todorovic')
                    ->andReturn([
                        'threads_found' => 1,
                        'conversations' => 4,
                        'messages_imported' => 25,
                        'messages_skipped' => 5,
                    ]);
            });

            $this->mock(MessageEmbeddingService::class, function ($mock) {
                $mock->shouldReceive('generate')
                    ->once()
                    ->with(true)
                    ->andReturn([
                        'messages_indexed' => 25,
                        'total_messages' => 25,
                        'batch_size' => 100,
                    ]);
            });

            $response = $this->actingAs($user)->post(route('imports.store'), [
                'source_path' => $relativeSourcePath,
                'me_name' => 'Rastko Todorovic',
            ]);

            $response->assertRedirect();
            $response->assertSessionHas('success', 'Import completed: 25 messages absorbed, 5 skipped, 4 conversations updated. Embeddings were rebuilt.');

            $run = MessageImportRun::query()->latest()->first();

            $this->assertNotNull($run);
            $this->assertSame('completed', $run->status);
            $this->assertSame('messages', $run->uploaded_filename);
            $this->assertSame('local://'.$sourcePath, $run->storage_path);
        } finally {
            File::deleteDirectory(base_path(dirname(dirname($relativeSourcePath))));
        }
    }

    protected function makeArchive(): UploadedFile
    {
        $path = storage_path('framework/testing/facebook-messages-'.uniqid().'.zip');
        $zip = new \ZipArchive;

        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addEmptyDir('your_facebook_activity/messages/inbox/example_thread');
        $zip->addFromString('your_facebook_activity/messages/inbox/example_thread/message_1.json', '{}');
        $zip->close();

        return new UploadedFile($path, 'facebook-messages.zip', 'application/zip', null, true);
    }
}
