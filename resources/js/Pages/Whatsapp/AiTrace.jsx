import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Bot,
    Clock3,
    Database,
    MessageSquareQuote,
    Search,
    ShieldAlert,
    Sparkles,
} from 'lucide-react';

import { AppSidebar } from '@/components/app-sidebar';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

function SummaryStat({ label, value, hint }) {
    return (
        <div className="rounded-2xl border border-border bg-background p-4">
            <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">{label}</p>
            <p className="mt-3 text-2xl font-semibold text-foreground">{value}</p>
            {hint ? <p className="mt-2 text-sm text-muted-foreground">{hint}</p> : null}
        </div>
    );
}

function EmptyPanel({ message }) {
    return <p className="mt-4 text-sm leading-6 text-muted-foreground">{message}</p>;
}

function TranscriptMessage({ role, content, variant = 'default' }) {
    const tones = {
        default: 'border-border bg-background',
        assistant: 'border-emerald-500/20 bg-emerald-500/10',
        user: 'border-sky-500/20 bg-sky-500/10',
        system: 'border-amber-500/20 bg-amber-500/10',
    };

    return (
        <div className={`rounded-2xl border p-4 ${tones[variant] ?? tones.default}`}>
            <div className="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">{role}</div>
            <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-foreground">{content || 'No content recorded.'}</p>
        </div>
    );
}

function getStatusClasses(status) {
    if (status === 'completed') {
        return 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300';
    }

    if (status === 'failed') {
        return 'border-destructive/20 bg-destructive/10 text-destructive';
    }

    return 'border-border bg-muted text-muted-foreground';
}

function formatScore(score) {
    if (score === null || score === undefined || score === '') {
        return '—';
    }

    return Number(score).toFixed(3);
}

export default function AiTrace({ auth, log, trace, urls }) {
    return (
        <>
            <Head title="AI Trace" />

            <SidebarProvider defaultOpen>
                <AppSidebar auth={auth} urls={urls} activePage="whatsapp" />

                <SidebarInset className="bg-background">
                    <header className="sticky top-0 z-20 border-b border-border/70 bg-background/85 backdrop-blur">
                        <div className="flex flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-start gap-3">
                                <SidebarTrigger className="mt-1" />
                                <Separator orientation="vertical" className="mt-1 hidden h-6 bg-border lg:block" />

                                <div>
                                    <div className="inline-flex items-center gap-2 rounded-full border border-primary/25 bg-primary/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.22em] text-primary">
                                        <Sparkles className="size-3.5" />
                                        AI trace inspector
                                    </div>
                                    <h1 className="mt-3 text-2xl font-semibold tracking-tight text-foreground">
                                        Review the prompt, retrieval context, and generated reply
                                    </h1>
                                    <p className="mt-1 max-w-3xl text-sm text-muted-foreground">
                                        {trace.contact_phone} · {trace.created_at || 'Just now'} · detailed view for
                                        one auto-reply generation run.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                                <ThemeToggle />
                                <Button asChild variant="outline">
                                    <Link href={urls.whatsapp}>Connection page</Link>
                                </Button>
                                <Button asChild>
                                    <Link href={urls.autoReply}>
                                        <ArrowLeft className="size-4" />
                                        Back to auto-reply
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </header>

                    <div className="flex-1 px-4 py-6 sm:px-6">
                        <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                            <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                                <div className="max-w-3xl">
                                    <div
                                        className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium uppercase tracking-[0.2em] ${getStatusClasses(trace.status)}`}
                                    >
                                        {trace.status}
                                    </div>
                                    <p className="mt-4 text-sm leading-6 text-muted-foreground">
                                        Outgoing log #{log.id} {trace.created_at_full ? `· ${trace.created_at_full}` : ''}
                                    </p>
                                    {trace.retrieval_query ? (
                                        <div className="mt-5 rounded-2xl border border-border bg-background p-4">
                                            <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">
                                                Retrieval query
                                            </p>
                                            <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-foreground">
                                                {trace.retrieval_query}
                                            </p>
                                        </div>
                                    ) : null}
                                </div>

                                <div className="grid w-full gap-4 sm:grid-cols-2 xl:max-w-2xl">
                                    <SummaryStat label="Model" value={trace.model || '—'} />
                                    <SummaryStat
                                        label="Latency"
                                        value={trace.latency_ms ? `${trace.latency_ms} ms` : '—'}
                                    />
                                    <SummaryStat
                                        label="Retrieved Hits"
                                        value={trace.retrieval_hits_count}
                                        hint="Historical message matches included in generation."
                                    />
                                    <SummaryStat
                                        label="Prompt Messages"
                                        value={trace.prompt_messages_count}
                                        hint="Total prompt entries sent to the model."
                                    />
                                </div>
                            </div>

                            {trace.usage ? (
                                <div className="mt-6 grid gap-4 md:grid-cols-3">
                                    <SummaryStat label="Prompt Tokens" value={trace.usage.prompt_tokens ?? '—'} />
                                    <SummaryStat label="Completion Tokens" value={trace.usage.completion_tokens ?? '—'} />
                                    <SummaryStat label="Total Tokens" value={trace.usage.total_tokens ?? '—'} />
                                </div>
                            ) : null}
                        </section>

                        <div className="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr_0.95fr]">
                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-center gap-2">
                                    <MessageSquareQuote className="size-4 text-primary" />
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Incoming message
                                    </p>
                                </div>
                                <p className="mt-4 whitespace-pre-wrap rounded-2xl border border-border bg-background p-4 text-sm leading-6 text-foreground">
                                    {trace.input_message}
                                </p>
                            </section>

                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-center gap-2">
                                    <Bot className="size-4 text-primary" />
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Generated reply
                                    </p>
                                </div>

                                {trace.error ? (
                                    <div className="mt-4 rounded-2xl border border-destructive/20 bg-destructive/10 p-4">
                                        <div className="flex items-center gap-2 text-sm font-medium text-destructive">
                                            <ShieldAlert className="size-4" />
                                            Generation failed
                                        </div>
                                        <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-destructive">
                                            {trace.error}
                                        </p>
                                    </div>
                                ) : (
                                    <p className="mt-4 whitespace-pre-wrap rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm leading-6 text-foreground">
                                        {trace.model_response || 'No response recorded.'}
                                    </p>
                                )}
                            </section>

                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-center gap-2">
                                    <Clock3 className="size-4 text-primary" />
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Message log
                                    </p>
                                </div>

                                <dl className="mt-4 space-y-4 text-sm">
                                    <div className="flex items-start justify-between gap-4">
                                        <dt className="text-muted-foreground">Direction</dt>
                                        <dd className="font-medium text-foreground">{log.direction}</dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-4">
                                        <dt className="text-muted-foreground">Contact</dt>
                                        <dd className="font-medium text-foreground">{log.contact_phone}</dd>
                                    </div>
                                    <div className="flex items-start justify-between gap-4">
                                        <dt className="text-muted-foreground">Context messages used</dt>
                                        <dd className="font-medium text-foreground">{log.context_messages_used ?? '—'}</dd>
                                    </div>
                                </dl>

                                <div className="mt-4 rounded-2xl border border-border bg-background p-4">
                                    <p className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">
                                        Logged body
                                    </p>
                                    <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-foreground">{log.body}</p>
                                    {log.error ? (
                                        <p className="mt-3 text-sm text-destructive">Log error: {log.error}</p>
                                    ) : null}
                                </div>
                            </section>
                        </div>

                        <div className="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr]">
                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-center gap-2">
                                    <MessageSquareQuote className="size-4 text-primary" />
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Recent WhatsApp context
                                    </p>
                                </div>

                                {trace.recent_conversation.length === 0 ? (
                                    <EmptyPanel message="No recent conversation was included." />
                                ) : (
                                    <div className="mt-4 space-y-3">
                                        {trace.recent_conversation.map((message, index) => (
                                            <TranscriptMessage
                                                key={`${message.role}-${index}`}
                                                role={message.role}
                                                content={message.content}
                                                variant={message.role === 'assistant' ? 'assistant' : 'user'}
                                            />
                                        ))}
                                    </div>
                                )}
                            </section>

                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-center gap-2">
                                    <Search className="size-4 text-primary" />
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Retrieved matches
                                    </p>
                                </div>

                                {trace.retrieval_hits.length === 0 ? (
                                    <EmptyPanel message="No historical matches were retrieved." />
                                ) : (
                                    <div className="mt-4 space-y-4">
                                        {trace.retrieval_hits.map((hit, index) => (
                                            <div key={`${hit.rank ?? index}-${hit.conversation_title}`} className="rounded-2xl border border-border bg-background p-4">
                                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div>
                                                        <p className="text-sm font-semibold text-foreground">
                                                            #{hit.rank ?? '?'} · {hit.conversation_title}
                                                        </p>
                                                        <p className="mt-1 text-xs text-muted-foreground">
                                                            {hit.sender_name} {hit.sent_at ? `· ${hit.sent_at}` : ''}
                                                        </p>
                                                    </div>
                                                    <span className="inline-flex rounded-full border border-border px-2.5 py-1 text-xs text-muted-foreground">
                                                        Score: {formatScore(hit.ranking_score)}
                                                    </span>
                                                </div>
                                                <p className="mt-4 whitespace-pre-wrap text-sm leading-6 text-foreground">
                                                    {hit.content}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </section>
                        </div>

                        <section className="mt-6 rounded-[28px] border border-border bg-card p-6 shadow-sm">
                            <div className="flex items-center gap-2">
                                <Database className="size-4 text-primary" />
                                <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                    Context snippet windows
                                </p>
                            </div>

                            {trace.context_snippets.length === 0 ? (
                                <EmptyPanel message="No snippet windows were captured." />
                            ) : (
                                <div className="mt-4 space-y-5">
                                    {trace.context_snippets.map((snippet, index) => (
                                        <div key={`${snippet.conversation_title}-${index}`} className="rounded-[24px] border border-border bg-background p-5">
                                            <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <p className="text-sm font-semibold text-foreground">
                                                        {snippet.conversation_title}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        Anchor: {snippet.matched_message.sender_name}
                                                        {snippet.matched_message.ranking_score !== null &&
                                                        snippet.matched_message.ranking_score !== undefined
                                                            ? ` · score ${formatScore(snippet.matched_message.ranking_score)}`
                                                            : ''}
                                                    </p>
                                                </div>
                                            </div>

                                            <div className="mt-4 space-y-3">
                                                {snippet.messages.map((message, messageIndex) => (
                                                    <div key={`${message.sender_name}-${messageIndex}`} className="rounded-2xl border border-border bg-card p-4">
                                                        <div className="text-xs font-semibold uppercase tracking-[0.2em] text-muted-foreground">
                                                            {message.sender_name}
                                                        </div>
                                                        <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-foreground">
                                                            {message.content}
                                                        </p>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </section>

                        <section className="mt-6 rounded-[28px] border border-border bg-card p-6 shadow-sm">
                            <div className="flex items-center gap-2">
                                <Sparkles className="size-4 text-primary" />
                                <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                    Final prompt
                                </p>
                            </div>

                            {trace.final_prompt.length === 0 ? (
                                <EmptyPanel message="Prompt data was not captured." />
                            ) : (
                                <div className="mt-4 space-y-3">
                                    {trace.final_prompt.map((message, index) => (
                                        <TranscriptMessage
                                            key={`${message.role}-${index}`}
                                            role={message.role}
                                            content={message.content}
                                            variant={
                                                message.role === 'system'
                                                    ? 'system'
                                                    : message.role === 'assistant'
                                                      ? 'assistant'
                                                      : 'user'
                                            }
                                        />
                                    ))}
                                </div>
                            )}
                        </section>
                    </div>
                </SidebarInset>
            </SidebarProvider>
        </>
    );
}
