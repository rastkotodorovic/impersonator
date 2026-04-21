import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import {
    Bot,
    CheckCircle2,
    ChevronRight,
    Clock3,
    MessageCircleMore,
    MessagesSquare,
    PauseCircle,
    ShieldCheck,
    Sparkles,
    Trash2,
    UserRoundPlus,
} from 'lucide-react';

import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

function FieldError({ message }) {
    if (!message) {
        return null;
    }

    return <p className="mt-2 text-sm text-destructive">{message}</p>;
}

function StatusBadge({ active }) {
    return (
        <span
            className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${
                active
                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                    : 'bg-muted text-muted-foreground'
            }`}
        >
            {active ? 'Active' : 'Paused'}
        </span>
    );
}

export default function AutoReply({ auth, contacts, availableConversations, recentLogs, urls }) {
    const { flash, errors } = usePage().props;
    const form = useForm({
        identifier: '',
        name: '',
        preferred_conversation_id: '',
        ai_additional_instructions: '',
    });

    function submitContact(event) {
        event.preventDefault();
        form.post(urls.store, {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    }

    function toggleContact(contact) {
        router.patch(contact.urls.toggle, {}, { preserveScroll: true });
    }

    function removeContact(contact) {
        router.delete(contact.urls.destroy, { preserveScroll: true });
    }

    return (
        <>
            <Head title="WhatsApp Auto-Reply" />
            {flash?.success ? (
                <div className="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                    {flash.success}
                </div>
            ) : null}

            <div className="grid gap-6 xl:grid-cols-[1.1fr_0.9fr]">
                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-center gap-2">
                                    <UserRoundPlus className="size-4 text-primary" />
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Add contact
                                    </p>
                                </div>

                                <h2 className="mt-3 text-xl font-semibold text-card-foreground">
                                    Create or update a WhatsApp auto-reply contact
                                </h2>
                                <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                                    Add a WhatsApp number to auto-reply to and optionally bias the style retrieval
                                    toward one imported conversation.
                                </p>

                                <form onSubmit={submitContact} className="mt-6 space-y-5">
                                    <div className="grid gap-4 md:grid-cols-2">
                                        <div>
                                            <label className="block text-sm font-medium text-foreground" htmlFor="identifier">
                                                Identifier
                                            </label>
                                            <Input
                                                id="identifier"
                                                value={form.data.identifier}
                                                onChange={(event) => form.setData('identifier', event.target.value)}
                                                className="mt-2"
                                                placeholder="381651234567"
                                            />
                                            <FieldError message={errors.identifier} />
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-foreground" htmlFor="name">
                                                Name
                                            </label>
                                            <Input
                                                id="name"
                                                value={form.data.name}
                                                onChange={(event) => form.setData('name', event.target.value)}
                                                className="mt-2"
                                                placeholder="e.g. Mom"
                                            />
                                            <FieldError message={errors.name} />
                                        </div>
                                    </div>

                                    <div className="rounded-2xl border border-border bg-background p-5">
                                        <label className="block text-sm font-medium text-foreground" htmlFor="preferred_conversation_id">
                                            Impersonation source conversation
                                        </label>
                                        <select
                                            id="preferred_conversation_id"
                                            value={form.data.preferred_conversation_id}
                                            onChange={(event) => form.setData('preferred_conversation_id', event.target.value)}
                                            className="mt-2 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring"
                                        >
                                            <option value="">All imported conversations</option>
                                            {availableConversations.map((conversation) => (
                                                <option key={conversation.id} value={conversation.id}>
                                                    {conversation.title} ({conversation.source}, {conversation.participant_count} participants)
                                                </option>
                                            ))}
                                        </select>
                                        <p className="mt-2 text-xs leading-5 text-muted-foreground">
                                            If selected, this chat is searched first for tone and phrasing examples for
                                            this contact. If it has too little signal, the rest of your imported
                                            library still acts as fallback.
                                        </p>
                                        <FieldError message={errors.preferred_conversation_id} />
                                    </div>

                                    <div className="rounded-2xl border border-border bg-background p-5">
                                        <label className="block text-sm font-medium text-foreground" htmlFor="ai_additional_instructions">
                                            Custom AI instructions
                                        </label>
                                        <textarea
                                            id="ai_additional_instructions"
                                            value={form.data.ai_additional_instructions}
                                            onChange={(event) => form.setData('ai_additional_instructions', event.target.value)}
                                            rows={5}
                                            className="mt-2 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring"
                                            placeholder="Only applied to this contact. Example: Reply in Serbian Latin, keep responses short, and do not mention pricing unless asked directly."
                                        />
                                        <p className="mt-2 text-xs leading-5 text-muted-foreground">
                                            These instructions are appended to the system prompt only for this contact.
                                        </p>
                                        <FieldError message={errors.ai_additional_instructions} />
                                    </div>

                                    <Button
                                        type="submit"
                                        className="bg-primary text-primary-foreground hover:bg-primary/90"
                                        disabled={form.processing}
                                    >
                                        {form.processing ? (
                                            <>
                                                <Sparkles className="size-4 animate-pulse" />
                                                Saving...
                                            </>
                                        ) : (
                                            'Save contact'
                                        )}
                                    </Button>
                                </form>
                            </section>

                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="size-4 text-primary" />
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Notes
                                    </p>
                                </div>

                                <div className="mt-5 space-y-4">
                                    <div className="rounded-2xl border border-border bg-background p-4">
                                        <p className="text-sm font-semibold text-card-foreground">Contact-scoped prompts</p>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            Custom instructions apply only to this contact, which makes it safer to
                                            tune tone without affecting every conversation.
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-border bg-background p-4">
                                        <p className="text-sm font-semibold text-card-foreground">Preferred chat source</p>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            Picking a conversation lets retrieval start from a stronger style anchor
                                            before falling back to the wider imported library.
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-border bg-background p-4">
                                        <p className="text-sm font-semibold text-card-foreground">Trace review</p>
                                        <p className="mt-2 text-sm leading-6 text-muted-foreground">
                                            Recent activity links to AI traces when available so you can inspect what
                                            context and model output led to a reply.
                                        </p>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div className="mt-6 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-center gap-2">
                                    <MessagesSquare className="size-4 text-primary" />
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Whitelisted contacts
                                    </p>
                                </div>

                                {contacts.length === 0 ? (
                                    <p className="mt-5 text-sm text-muted-foreground">No contacts added yet.</p>
                                ) : (
                                    <div className="mt-5 space-y-4">
                                        {contacts.map((contact) => (
                                            <div key={contact.id} className="rounded-[24px] border border-border bg-background p-5">
                                                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                    <div className="min-w-0">
                                                        <div className="flex items-center gap-3">
                                                            <p className="font-mono text-sm font-medium text-foreground">
                                                                {contact.identifier}
                                                            </p>
                                                            <StatusBadge active={contact.is_active} />
                                                        </div>
                                                        <p className="mt-2 text-sm text-muted-foreground">
                                                            {contact.name || 'Unnamed contact'}
                                                        </p>
                                                        <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                                            {contact.preferred_conversation
                                                                ? `${contact.preferred_conversation.title} (${contact.preferred_conversation.source})`
                                                                : 'All imported conversations'}
                                                        </p>
                                                        <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                                            {contact.ai_additional_instructions_preview || 'No custom prompt added.'}
                                                        </p>
                                                    </div>

                                                    <div className="flex flex-wrap gap-3">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            onClick={() => toggleContact(contact)}
                                                        >
                                                            {contact.is_active ? (
                                                                <>
                                                                    <PauseCircle className="size-4" />
                                                                    Pause
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <CheckCircle2 className="size-4" />
                                                                    Enable
                                                                </>
                                                            )}
                                                        </Button>
                                                        <Button
                                                            type="button"
                                                            variant="destructive"
                                                            onClick={() => removeContact(contact)}
                                                        >
                                                            <Trash2 className="size-4" />
                                                            Remove
                                                        </Button>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </section>

                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-center gap-2">
                                    <Clock3 className="size-4 text-primary" />
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Recent activity
                                    </p>
                                </div>

                                {recentLogs.length === 0 ? (
                                    <p className="mt-5 text-sm text-muted-foreground">No messages yet.</p>
                                ) : (
                                    <div className="mt-5 space-y-3">
                                        {recentLogs.map((log) => (
                                            <div
                                                key={log.id}
                                                className={`rounded-2xl border p-4 ${
                                                    log.error
                                                        ? 'border-destructive/20 bg-destructive/10'
                                                        : log.direction === 'incoming'
                                                          ? 'border-sky-500/20 bg-sky-500/10'
                                                          : 'border-emerald-500/20 bg-emerald-500/10'
                                                }`}
                                            >
                                                <div className="flex items-center justify-between gap-4">
                                                    <span className="text-xs font-medium uppercase tracking-wide text-foreground/80">
                                                        {log.direction === 'incoming' ? 'Received from' : 'Replied to'} {log.contact_phone}
                                                    </span>
                                                    <span className="text-xs text-muted-foreground">{log.created_at}</span>
                                                </div>
                                                <p className="mt-2 text-sm leading-6 text-foreground/80">{log.body}</p>
                                                {log.error ? (
                                                    <p className="mt-2 text-xs text-destructive">Error: {log.error}</p>
                                                ) : null}
                                                {log.context_messages_used ? (
                                                    <p className="mt-2 text-xs text-muted-foreground">
                                                        {log.context_messages_used} context messages used
                                                    </p>
                                                ) : null}
                                                {log.has_trace ? (
                                                    <div className="mt-3">
                                                        <Button asChild variant="outline" className="text-xs">
                                                            <Link href={log.trace_url}>
                                                                Visualize AI details
                                                                <ChevronRight className="size-3.5" />
                                                            </Link>
                                                        </Button>
                                                    </div>
                                                ) : null}
                                            </div>
                                        ))}
                                    </div>
                                )}
                                </section>
            </div>
        </>
    );
}

AutoReply.layout = (page) => (
    <AppShell
        activePage="whatsapp"
        badge="WhatsApp auto-reply"
        badgeIcon={Bot}
        title="Manage contacts, prompt bias, and recent reply activity"
        description="Whitelist specific contacts, steer style retrieval toward a source conversation, and review the latest WhatsApp reply activity in one place."
    >
        {page}
    </AppShell>
);
