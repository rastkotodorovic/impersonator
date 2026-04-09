<?php

namespace App\Jobs;

use App\Models\MessageImportRun;
use App\Services\MessageEmbeddingService;
use App\Services\MessageImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProcessMessageImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        public int $importRunId,
    ) {}

    public function handle(
        MessageImportService $messageImportService,
        MessageEmbeddingService $embeddingService,
    ): void {
        $importRun = MessageImportRun::findOrFail($this->importRunId);

        $importRun->update([
            'status' => 'processing',
            'started_at' => now(),
            'error' => null,
        ]);

        try {
            $importPath = str_starts_with($importRun->storage_path, 'local://')
                ? substr($importRun->storage_path, strlen('local://'))
                : Storage::disk('local')->path($importRun->storage_path);

            if ($importRun->replace_existing) {
                $this->replaceExistingHistory();
            }

            $importResult = $messageImportService->importFromPath($importPath, $importRun->me_name);

            $embeddingService->generate(true);

            $importRun->update([
                'status' => 'completed',
                'messages_imported' => $importResult['messages_imported'],
                'messages_skipped' => $importResult['messages_skipped'],
                'conversations_count' => $importResult['conversations'],
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $importRun->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            throw $exception;
        } finally {
            if (! str_starts_with($importRun->storage_path, 'local://') && Storage::disk('local')->exists($importRun->storage_path)) {
                Storage::disk('local')->delete($importRun->storage_path);
            }
        }
    }

    protected function replaceExistingHistory(): void
    {
        DB::transaction(function () {
            DB::table('messages')->delete();
            DB::table('conversations')->delete();
        });
    }
}
