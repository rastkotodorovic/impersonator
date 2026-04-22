import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useRef } from 'react';
import {
    CheckCircle2,
    Database,
    FileArchive,
    FileUp,
    History,
    LibraryBig,
    LoaderCircle,
    MessageSquareText,
    Sparkles,
    TriangleAlert,
    Upload,
} from 'lucide-react';

import { AppShell } from '@/components/app-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

const statCards = [
    {
        key: 'totalMessages',
        label: 'Library Messages',
        description: 'Messages currently available for retrieval.',
        icon: MessageSquareText,
        tone: 'bg-primary text-primary-foreground',
    },
    {
        key: 'totalConversations',
        label: 'Conversations',
        description: 'Distinct imported message threads.',
        icon: LibraryBig,
        tone: 'bg-card text-card-foreground border border-border',
    },
    {
        key: 'latestImported',
        label: 'Last Absorbed',
        description: 'Messages imported in the latest run.',
        icon: CheckCircle2,
        tone: 'bg-card text-card-foreground border border-border',
    },
    {
        key: 'latestSkipped',
        label: 'Last Skipped',
        description: 'Ignored placeholders in the latest run.',
        icon: TriangleAlert,
        tone: 'bg-card text-card-foreground border border-border',
    },
];

const exportGuides = [
    {
        title: 'Facebook Messenger',
        steps: [
            'Open Accounts Center and choose Download your information.',
            'Create a new export that includes only Messages.',
            'Choose JSON instead of HTML.',
            'Upload the ZIP or point the app at your_facebook_activity/messages.',
        ],
    },
    {
        title: 'Instagram',
        steps: [
            'Open Accounts Center and choose Download your information.',
            'Create an export that includes Messages.',
            'Choose JSON instead of HTML.',
            'Upload the ZIP or point the app at your_instagram_activity/messages.',
        ],
    },
    {
        title: 'WhatsApp Single Chat',
        steps: [
            'Open the WhatsApp conversation you want to reuse.',
            'Use Export chat and prefer without media for the simplest upload.',
            'Upload the exported .txt or .zip file.',
            'Or point the app at the exported _chat.txt path on this machine.',
        ],
    },
];

function StatusBadge({ status }) {
    const styles = {
        failed: 'bg-destructive/10 text-destructive border-destructive/20',
        completed: 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20 dark:text-emerald-300',
        pending: 'bg-primary/10 text-primary border-primary/20',
        processing: 'bg-primary/10 text-primary border-primary/20',
    };

    return (
        <span className={`rounded-full border px-2.5 py-1 text-xs font-medium ${styles[status] ?? styles.pending}`}>
            {status.charAt(0).toUpperCase() + status.slice(1)}
        </span>
    );
}

function FieldError({ message }) {
    if (!message) {
        return null;
    }

    return <p className="mt-2 text-sm text-destructive">{message}</p>;
}

export default function ImportsIndex({ auth, stats, latestRun, recentRuns, isImportRunning, defaultMeName, urls }) {
    const { flash, errors } = usePage().props;
    const archiveInputRef = useRef(null);
    const { data, setData, post, processing } = useForm({
        archive: null,
        source_path: '',
        me_name: defaultMeName,
        replace_existing: false,
    });

    function handleSubmit(event) {
        event.preventDefault();

        post(urls.store, {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    const submitDisabled = processing || isImportRunning;
    const selectedArchiveName = data.archive?.name ?? 'No file selected';

    return (
        <>
            <Head title="Imports" />
            {flash?.success ? (
                <div className="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                    <p className="font-medium">Import finished</p>
                    <p className="mt-1">{flash.success}</p>
                </div>
            ) : null}

            {flash?.error ? (
                <div className="mb-6 rounded-2xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                    {flash.error}
                </div>
            ) : null}

            <section className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {statCards.map((card) => {
                                const Icon = card.icon;

                                return (
                                    <article
                                        key={card.key}
                                        className={`rounded-[24px] p-5 shadow-sm ${card.tone}`}
                                    >
                                        <div className="flex items-center justify-between gap-4">
                                            <p className="text-xs font-medium uppercase tracking-[0.2em] opacity-80">
                                                {card.label}
                                            </p>
                                            <Icon className="size-5 opacity-80" />
                                        </div>
                                        <p className="mt-4 text-3xl font-semibold">
                                            {Number(stats[card.key] ?? 0).toLocaleString()}
                                        </p>
                                        <p className="mt-2 text-sm opacity-75">{card.description}</p>
                                    </article>
                                );
                            })}
            </section>

            <div className="mt-6 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                            <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                            Upload export
                                        </p>
                                        <h2 className="mt-2 text-xl font-semibold text-card-foreground">
                                            Start a new message import
                                        </h2>
                                        <p className="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                                            Upload a Facebook Messenger or Instagram ZIP export, or a WhatsApp .txt or
                                            .zip export. For very large exports, use a local path on this machine.
                                        </p>
                                    </div>

                                    <div className="hidden rounded-2xl border border-border bg-background px-4 py-3 text-sm text-muted-foreground lg:block">
                                        {isImportRunning ? 'An import is currently running' : 'Ready for a new import'}
                                    </div>
                                </div>

                                <form onSubmit={handleSubmit} className="mt-6 space-y-5">
                                    <div className="rounded-2xl border border-border bg-background p-5">
                                        <label className="block text-sm font-medium text-foreground" htmlFor="archive">
                                            Export file
                                        </label>
                                        <input
                                            id="archive"
                                            ref={archiveInputRef}
                                            type="file"
                                            accept=".zip,.txt"
                                            className="sr-only"
                                            onChange={(event) => setData('archive', event.target.files?.[0] ?? null)}
                                        />
                                        <div className="mt-3 rounded-2xl border border-dashed border-border bg-card/60 p-4">
                                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                                <div className="min-w-0">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex size-10 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                                            <FileArchive className="size-5" />
                                                        </div>
                                                        <div className="min-w-0">
                                                            <p className="truncate text-sm font-medium text-foreground">
                                                                {selectedArchiveName}
                                                            </p>
                                                            <p className="mt-1 text-xs text-muted-foreground">
                                                                Accepts `.zip` and `.txt` exports
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    className="shrink-0"
                                                    onClick={() => archiveInputRef.current?.click()}
                                                >
                                                    {data.archive ? 'Choose another file' : 'Choose file'}
                                                </Button>
                                            </div>
                                        </div>
                                        <p className="mt-2 text-xs leading-5 text-muted-foreground">
                                            Works with Facebook Messenger and Instagram ZIP archives, plus WhatsApp
                                            exported chat .txt or .zip files.
                                        </p>
                                        <FieldError message={errors.archive} />
                                    </div>

                                    <div className="rounded-2xl border border-border bg-background p-5">
                                        <label className="block text-sm font-medium text-foreground" htmlFor="source_path">
                                            Local export path
                                        </label>
                                        <Input
                                            id="source_path"
                                            value={data.source_path}
                                            onChange={(event) => setData('source_path', event.target.value)}
                                            className="mt-3"
                                            placeholder="data/your_facebook_activity/messages or data/whatsapp/_chat.txt"
                                        />
                                        <p className="mt-2 text-xs leading-5 text-muted-foreground">
                                            Best for very large exports. Point to your extracted messages folder or an
                                            exported WhatsApp _chat.txt file on this machine.
                                        </p>
                                        <FieldError message={errors.source_path} />
                                    </div>

                                    <div className="rounded-2xl border border-border bg-background p-5">
                                        <label className="block text-sm font-medium text-foreground" htmlFor="me_name">
                                            Your name in the export
                                        </label>
                                        <Input
                                            id="me_name"
                                            value={data.me_name}
                                            onChange={(event) => setData('me_name', event.target.value)}
                                            className="mt-3"
                                            placeholder="Your full name as shown in the export"
                                        />
                                        <p className="mt-2 text-xs leading-5 text-muted-foreground">
                                            Used to identify which imported messages are yours across Facebook,
                                            Instagram, or WhatsApp exports.
                                        </p>
                                        <FieldError message={errors.me_name} />
                                    </div>

                                    <label className="flex items-start gap-3 rounded-2xl border border-border bg-background p-5">
                                        <input
                                            type="checkbox"
                                            checked={data.replace_existing}
                                            onChange={(event) => setData('replace_existing', event.target.checked)}
                                            className="mt-1 rounded border-border text-primary focus:ring-primary"
                                        />
                                        <span>
                                            <span className="block text-sm font-medium text-foreground">
                                                Replace existing imported history
                                            </span>
                                            <span className="mt-1 block text-sm leading-6 text-muted-foreground">
                                                Deletes current conversations and messages before importing the new
                                                export, then rebuilds embeddings from scratch.
                                            </span>
                                        </span>
                                    </label>

                                    <div className="flex flex-col gap-3 sm:flex-row">
                                        <Button
                                            type="submit"
                                            disabled={submitDisabled}
                                            className="bg-primary text-primary-foreground hover:bg-primary/90"
                                        >
                                            {processing ? (
                                                <>
                                                    <LoaderCircle className="size-4 animate-spin" />
                                                    Uploading and importing...
                                                </>
                                            ) : isImportRunning ? (
                                                'Import in progress'
                                            ) : (
                                                <>
                                                    <Upload className="size-4" />
                                                    Start import
                                                </>
                                            )}
                                        </Button>

                                        <Button asChild variant="outline">
                                            <Link href={urls.dashboard}>Back to dashboard</Link>
                                        </Button>
                                    </div>
                                </form>
                            </section>

                            <div className="space-y-6">
                                <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                    <div className="flex items-center gap-2">
                                        <History className="size-4 text-primary" />
                                        <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                            Latest import
                                        </p>
                                    </div>

                                    {!latestRun ? (
                                        <p className="mt-5 text-sm text-muted-foreground">
                                            No message imports have been run yet.
                                        </p>
                                    ) : (
                                        <div className="mt-5 rounded-[24px] border border-border bg-background p-5">
                                            <div className="flex items-start justify-between gap-4">
                                                <div>
                                                    <p className="text-sm font-medium text-foreground">
                                                        {latestRun.uploaded_filename}
                                                    </p>
                                                    <p className="mt-1 text-xs text-muted-foreground">
                                                        {latestRun.created_at}
                                                    </p>
                                                </div>
                                                <StatusBadge status={latestRun.status} />
                                            </div>

                                            <div className="mt-5 grid gap-3 sm:grid-cols-3">
                                                <div className="rounded-2xl border border-border bg-card p-3">
                                                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                        Absorbed
                                                    </p>
                                                    <p className="mt-2 text-2xl font-semibold text-foreground">
                                                        {Number(latestRun.messages_imported).toLocaleString()}
                                                    </p>
                                                </div>
                                                <div className="rounded-2xl border border-border bg-card p-3">
                                                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                        Skipped
                                                    </p>
                                                    <p className="mt-2 text-2xl font-semibold text-foreground">
                                                        {Number(latestRun.messages_skipped).toLocaleString()}
                                                    </p>
                                                </div>
                                                <div className="rounded-2xl border border-border bg-card p-3">
                                                    <p className="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                                        Conversations
                                                    </p>
                                                    <p className="mt-2 text-2xl font-semibold text-foreground">
                                                        {Number(latestRun.conversations_count).toLocaleString()}
                                                    </p>
                                                </div>
                                            </div>

                                            {latestRun.error ? (
                                                <div className="mt-4 rounded-2xl border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive">
                                                    {latestRun.error}
                                                </div>
                                            ) : null}
                                        </div>
                                    )}
                                </section>

                                <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
                                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                        Recent runs
                                    </p>

                                    {recentRuns.length === 0 ? (
                                        <p className="mt-5 text-sm text-muted-foreground">No recent imports yet.</p>
                                    ) : (
                                        <div className="mt-5 space-y-3">
                                            {recentRuns.map((run) => (
                                                <div key={`${run.uploaded_filename}-${run.created_at}`} className="rounded-2xl border border-border bg-background p-4">
                                                    <div className="flex items-center justify-between gap-4">
                                                        <span className="truncate text-sm font-medium text-foreground">
                                                            {run.uploaded_filename}
                                                        </span>
                                                        <StatusBadge status={run.status} />
                                                    </div>
                                                    <p className="mt-2 text-xs text-muted-foreground">
                                                        {run.created_at}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </section>
                            </div>
            </div>

            <section className="mt-6 rounded-[28px] border border-border bg-card p-6 shadow-sm">
                <div className="flex items-center gap-2">
                    <FileArchive className="size-4 text-primary" />
                    <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                        Export guide
                    </p>
                </div>

                <div className="mt-5 grid gap-4 lg:grid-cols-3">
                    {exportGuides.map((guide) => (
                        <article key={guide.title} className="rounded-[24px] border border-border bg-background p-5">
                            <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-foreground">
                                {guide.title}
                            </h3>
                            <ol className="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-muted-foreground">
                                {guide.steps.map((step) => (
                                    <li key={step}>{step}</li>
                                ))}
                            </ol>
                        </article>
                    ))}
                </div>

                <div className="mt-5 rounded-[24px] border border-border bg-background p-5 text-sm leading-6 text-muted-foreground">
                    <p className="font-medium text-foreground">What gets imported</p>
                    <p className="mt-2">
                        Facebook and Instagram imports read only message data from inbox, e2ee_cutover, and
                        message_requests. Instagram attachment placeholders are skipped so they do not pollute retrieval
                        context.
                    </p>
                    <p className="mt-2">
                        WhatsApp system notices and placeholders like {'<Media omitted>'} are skipped for the same
                        reason. If the export is too large for browser upload, use the local path field instead.
                    </p>
                </div>
            </section>
        </>
    );
}

ImportsIndex.layout = (page) => (
    <AppShell
        activePage="imports"
        badge="Message imports"
        badgeIcon={Database}
        title="Import message history into the retrieval library"
        description="Upload Facebook, Instagram, or WhatsApp exports, rebuild embeddings, and keep the message library ready for tone matching."
    >
        {page}
    </AppShell>
);
