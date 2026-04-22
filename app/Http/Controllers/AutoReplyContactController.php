<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAutoReplyContactRequest;
use App\Models\AutoReplyContact;
use App\Models\Conversation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AutoReplyContactController extends Controller
{
    public function index(Request $request): Response
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

        return Inertia::render('Whatsapp/AutoReply', [
            'contacts' => $contacts->map(fn (AutoReplyContact $contact) => [
                'id' => $contact->id,
                'identifier' => $contact->identifier,
                'name' => $contact->name,
                'preferred_conversation_id' => $contact->preferred_conversation_id,
                'preferred_conversation' => $contact->preferredConversation ? [
                    'id' => $contact->preferredConversation->id,
                    'title' => $contact->preferredConversation->title,
                    'source' => $contact->preferredConversation->source,
                ] : null,
                'ai_additional_instructions' => $contact->ai_additional_instructions,
                'ai_additional_instructions_preview' => $contact->ai_additional_instructions
                    ? Str::limit($contact->ai_additional_instructions, 140)
                    : null,
                'is_active' => $contact->is_active,
                'urls' => [
                    'toggle' => route('whatsapp.auto-reply.contacts.toggle', $contact),
                    'destroy' => route('whatsapp.auto-reply.contacts.destroy', $contact),
                ],
            ])->all(),
            'availableConversations' => $availableConversations->map(fn (Conversation $conversation) => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'source' => $conversation->source,
                'participant_count' => $conversation->participant_count,
            ])->all(),
            'recentLogs' => $recentLogs->map(fn ($log) => [
                'id' => $log->id,
                'direction' => $log->direction,
                'contact_phone' => $log->contact_phone,
                'body' => Str::limit($log->body, 200),
                'error' => $log->error,
                'context_messages_used' => $log->context_messages_used,
                'created_at' => $log->created_at?->diffForHumans(),
                'has_trace' => (bool) $log->aiTrace,
                'trace_url' => $log->aiTrace ? route('whatsapp.auto-reply.logs.trace', $log) : null,
            ])->all(),
            'urls' => [
                'dashboard' => route('dashboard'),
                'profile' => route('profile.edit'),
                'whatsapp' => route('whatsapp.index'),
                'autoReply' => route('whatsapp.auto-reply.index'),
                'imports' => route('imports.index'),
                'ai' => route('ai.index'),
                'store' => route('whatsapp.auto-reply.contacts.store'),
            ],
        ]);
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

        return back()->with('success', 'Contact '.($contact->is_active ? 'enabled' : 'disabled').'.');
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
