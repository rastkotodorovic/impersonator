<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAutoReplyContactRequest;
use App\Models\AutoReplyContact;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutoReplyContactController extends Controller
{
    public function index(Request $request): View
    {
        $contacts = $request->user()->autoReplyContacts()
            ->where('channel', 'whatsapp')
            ->with('preferredConversation')
            ->orderBy('name')
            ->get();

        $availableConversations = Conversation::query()
            ->where('is_group_chat', false)
            ->orderBy('title')
            ->orderBy('source')
            ->get(['id', 'title', 'source', 'participant_count']);

        $recentLogs = $request->user()->whatsappMessageLogs()
            ->with('aiTrace')
            ->latest()
            ->take(50)
            ->get();

        return view('whatsapp.auto-reply', compact('contacts', 'availableConversations', 'recentLogs'));
    }

    public function store(StoreAutoReplyContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $normalized = AutoReplyContact::normalizeIdentifier($validated['identifier']);

        $request->user()->autoReplyContacts()->updateOrCreate(
            [
                'channel' => 'whatsapp',
                'identifier' => $normalized,
            ],
            [
                'phone_number' => $normalized,
                'name' => $validated['name'] ?? null,
                'preferred_conversation_id' => $validated['preferred_conversation_id'] ?? null,
                'ai_additional_instructions' => $validated['ai_additional_instructions'] ?? null,
                'is_active' => true,
            ],
        );

        return back()->with('success', 'Contact saved.');
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
