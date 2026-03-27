<?php

namespace App\Http\Controllers;

use App\Models\AutoReplyContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AutoReplyContactController extends Controller
{
    public function index(Request $request): View
    {
        $contacts = $request->user()->autoReplyContacts()
            ->orderBy('name')
            ->get();

        $recentLogs = $request->user()->whatsappMessageLogs()
            ->latest()
            ->limit(50)
            ->get();

        return view('whatsapp.auto-reply', compact('contacts', 'recentLogs'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $normalized = AutoReplyContact::normalizePhone($validated['phone_number']);

        $request->user()->autoReplyContacts()->updateOrCreate(
            ['phone_number' => $normalized],
            [
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
