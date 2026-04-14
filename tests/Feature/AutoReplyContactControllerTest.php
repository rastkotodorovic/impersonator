<?php

namespace Tests\Feature;

use App\Models\AutoReplyContact;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoReplyContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_renders_conversation_selector(): void
    {
        $user = User::factory()->create();

        Conversation::create([
            'thread_path' => 'inbox/dzil',
            'title' => 'Dzil',
            'source' => 'inbox',
            'participants' => ['Rastko', 'Dzil'],
            'participant_count' => 2,
            'is_group_chat' => false,
        ]);

        $response = $this->actingAs($user)->get(route('whatsapp.auto-reply.index'));

        $response->assertOk();
        $response->assertSee('Impersonation source conversation');
        $response->assertSee('All imported conversations');
        $response->assertSee('Dzil');
    }

    public function test_store_saves_preferred_conversation_for_contact(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::create([
            'thread_path' => 'inbox/mama',
            'title' => 'Mama',
            'source' => 'inbox',
            'participants' => ['Rastko', 'Mama'],
            'participant_count' => 2,
            'is_group_chat' => false,
        ]);

        $response = $this->actingAs($user)->post(route('whatsapp.auto-reply.contacts.store'), [
            'identifier' => '38164111222',
            'name' => 'Mom',
            'preferred_conversation_id' => $conversation->id,
            'ai_additional_instructions' => 'Keep it brief.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Contact saved.');

        $contact = AutoReplyContact::firstOrFail();

        $this->assertSame($conversation->id, $contact->preferred_conversation_id);
        $this->assertSame('38164111222', $contact->identifier);
    }
}
