<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAutoReplyContactRequest;
use App\Models\AutoReplyContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutoReplyContactController extends Controller
{
    public function index(Request $request): View
    {
        $contacts = $request->user()->autoReplyContacts()
            ->orderBy('channel')
            ->orderBy('name')
            ->get();

        $recentLogs = $request->user()->whatsappMessageLogs()
            ->with('aiTrace')
            ->latest()
            ->limit(25)
            ->get()
            ->map(fn ($log) => $log->setAttribute('channel', 'whatsapp'))
            ->concat(
                $request->user()->telegramMessageLogs()
                    ->latest()
                    ->limit(25)
                    ->get()
                    ->map(fn ($log) => $log->setAttribute('channel', 'telegram'))
            )
            ->sortByDesc('created_at')
            ->take(50)
            ->values();

        return view('whatsapp.auto-reply', compact('contacts', 'recentLogs'));
    }

    public function store(StoreAutoReplyContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $normalized = AutoReplyContact::normalizeIdentifier($validated['channel'], $validated['identifier']);

        $request->user()->autoReplyContacts()->updateOrCreate(
            [
                'channel' => $validated['channel'],
                'identifier' => $normalized,
            ],
            [
                'phone_number' => $normalized,
                'name' => $validated['name'] ?? null,
                'is_active' => true,
            ],
        );

        return back()->with('success', 'Contact added to auto-reply list.');
    }

    public function toggle(Request $request, AutoReplyContact $contact): RedirectResponse
    {
        if ($contact->user_id !== $request->user()->id) {
            abort(403);
        }

        $contact->update(['is_active' => ! $contact->is_active]);

        return back()->with('success', 'Contact ' . ($contact->is_active ? 'enabled' : 'disabled') . '.');
    }

    public function destroy(Request $request, AutoReplyContact $contact): RedirectResponse
    {
        if ($contact->user_id !== $request->user()->id) {
            abort(403);
        }

        $contact->delete();

        return back()->with('success', 'Contact removed.');
    }
}
