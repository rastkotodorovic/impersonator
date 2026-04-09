<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageImportRequest;
use App\Jobs\ProcessMessageImport;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageImportRun;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MessageImportController extends Controller
{
    public function index(): View
    {
        return view('imports.messages', [
            'latestRun' => MessageImportRun::query()->latest()->first(),
            'recentRuns' => MessageImportRun::query()->latest()->limit(5)->get(),
            'isImportRunning' => MessageImportRun::query()
                ->whereIn('status', ['pending', 'processing'])
                ->exists(),
            'totalMessages' => Message::count(),
            'totalConversations' => Conversation::count(),
        ]);
    }

    public function store(StoreMessageImportRequest $request): RedirectResponse
    {
        $isImportRunning = MessageImportRun::query()
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($isImportRunning) {
            return back()->with('error', 'A message import is already running. Wait for it to finish before uploading another export.');
        }

        $archive = $request->file('archive');
        $sourcePath = $request->input('source_path');

        if ($archive) {
            $storedPath = $archive->store('message-imports');
            $displayName = $archive->getClientOriginalName();
        } else {
            $normalizedPath = $this->normalizeSourcePath($sourcePath);
            $storedPath = 'local://'.$normalizedPath;
            $displayName = basename($normalizedPath);
        }

        $importRun = MessageImportRun::create([
            'status' => 'pending',
            'uploaded_filename' => $displayName,
            'storage_path' => $storedPath,
            'me_name' => $request->string('me_name')->toString(),
            'replace_existing' => $request->boolean('replace_existing'),
        ]);

        try {
            ProcessMessageImport::dispatchSync($importRun->id);
        } catch (\Throwable) {
            return back()->with('error', 'The message import failed. Check the latest import details below.');
        }

        $importRun->refresh();

        return back()->with('success', sprintf(
            'Import completed: %s messages absorbed, %s skipped, %s conversations updated. Embeddings were rebuilt.',
            number_format($importRun->messages_imported ?? 0),
            number_format($importRun->messages_skipped ?? 0),
            number_format($importRun->conversations_count ?? 0),
        ));
    }

    protected function normalizeSourcePath(?string $sourcePath): string
    {
        $sourcePath = trim((string) $sourcePath);

        if ($sourcePath === '') {
            throw new \RuntimeException('A local source path is required when no archive is uploaded.');
        }

        if (str_starts_with($sourcePath, DIRECTORY_SEPARATOR)) {
            return $sourcePath;
        }

        return base_path($sourcePath);
    }
}
