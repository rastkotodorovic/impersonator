import { Head, Link, useForm, usePage } from '@inertiajs/react';
import {
    Bot,
    BrainCircuit,
    CheckCircle2,
    KeyRound,
    Link2,
    LoaderCircle,
    Sparkles,
    Unplug,
} from 'lucide-react';

import { AppSidebar } from '@/components/app-sidebar';
import { ThemeToggle } from '@/components/theme-toggle';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';

function StatusBadge({ connected }) {
    return (
        <span
            className={`rounded-full border px-2.5 py-1 text-xs font-medium ${
                connected
                    ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                    : 'border-border bg-muted text-muted-foreground'
            }`}
        >
            {connected ? 'Connected' : 'Not connected'}
        </span>
    );
}

function FieldError({ message }) {
    if (!message) {
        return null;
    }

    return <p className="mt-2 text-sm text-destructive">{message}</p>;
}

function SectionCard({ title, description, badge, children }) {
    return (
        <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-lg font-semibold text-card-foreground">{title}</h2>
                    <p className="mt-2 text-sm leading-6 text-muted-foreground">{description}</p>
                </div>
                {badge}
            </div>
            <div className="mt-6">{children}</div>
        </section>
    );
}

function ProviderCard({
    title,
    description,
    connected,
    credentialSummary,
    apiForm,
    apiKeyField,
    apiKeyPlaceholder,
    modelForm,
    modelFields,
    disconnectLabel,
    onDisconnect,
    oauthUrl,
    oauthLabel,
    errors,
}) {
    return (
        <section className="rounded-[28px] border border-border bg-card p-6 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h3 className="text-lg font-semibold text-card-foreground">{title}</h3>
                    <p className="mt-2 text-sm leading-6 text-muted-foreground">{description}</p>
                </div>
                <StatusBadge connected={connected} />
            </div>

            {credentialSummary ? (
                <div className="mt-5 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-sm text-emerald-800 dark:text-emerald-200">
                    {credentialSummary}
                </div>
            ) : null}

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    apiForm.post(apiForm.url, { preserveScroll: true });
                }}
                className="mt-5 rounded-2xl border border-border bg-background p-5"
            >
                <label className="block text-sm font-medium text-foreground">{apiKeyField.label}</label>
                <div className="mt-3 flex flex-col gap-3 sm:flex-row">
                    <Input
                        type="password"
                        value={apiForm.data[apiKeyField.key]}
                        onChange={(event) => apiForm.setData(apiKeyField.key, event.target.value)}
                        placeholder={apiKeyPlaceholder}
                    />
                    <Button
                        type="submit"
                        className="bg-primary text-primary-foreground hover:bg-primary/90 sm:shrink-0"
                        disabled={apiForm.processing}
                    >
                        {apiForm.processing ? (
                            <>
                                <LoaderCircle className="size-4 animate-spin" />
                                Saving...
                            </>
                        ) : (
                            'Save'
                        )}
                    </Button>
                </div>
                <FieldError message={errors[apiKeyField.key]} />
            </form>

            <form
                onSubmit={(event) => {
                    event.preventDefault();
                    modelForm.post(modelForm.url, { preserveScroll: true });
                }}
                className="mt-4 rounded-2xl border border-border bg-background p-5"
            >
                <div className="space-y-4">
                    {modelFields.map((field) => (
                        <div key={field.key}>
                            <label className="block text-sm font-medium text-foreground" htmlFor={field.id}>
                                {field.label}
                            </label>
                            <Input
                                id={field.id}
                                value={modelForm.data[field.key]}
                                onChange={(event) => modelForm.setData(field.key, event.target.value)}
                                className="mt-2"
                                placeholder={field.placeholder}
                            />
                            <p className="mt-2 text-xs leading-5 text-muted-foreground">{field.help}</p>
                            <FieldError message={errors[field.key]} />
                        </div>
                    ))}
                </div>

                <Button
                    type="submit"
                    variant="outline"
                    className="mt-5"
                    disabled={modelForm.processing}
                >
                    {modelForm.processing ? (
                        <>
                            <LoaderCircle className="size-4 animate-spin" />
                            Saving...
                        </>
                    ) : (
                        'Save model settings'
                    )}
                </Button>
            </form>

            <div className="mt-4 flex flex-col gap-3 sm:flex-row">
                {oauthUrl ? (
                    <Button asChild variant="outline">
                        <a href={oauthUrl}>{oauthLabel}</a>
                    </Button>
                ) : null}

                {connected ? (
                    <Button type="button" variant="destructive" onClick={onDisconnect}>
                        <Unplug className="size-4" />
                        {disconnectLabel}
                    </Button>
                ) : null}
            </div>
        </section>
    );
}

export default function AiIndex({ auth, providerSelection, providers, urls }) {
    const { flash, errors } = usePage().props;

    const providerForm = useForm({
        chat_provider: providerSelection.chat_provider,
        embedding_provider: providerSelection.embedding_provider,
    });

    const openAiApiForm = useForm({ api_key: '' });
    openAiApiForm.url = urls.openaiApiKey;

    const openAiModelsForm = useForm({
        chat_model: providers.openai.chatModel ?? '',
        embedding_model: providers.openai.embeddingModel ?? '',
    });
    openAiModelsForm.url = urls.openaiModels;

    const anthropicApiForm = useForm({ anthropic_api_key: '' });
    anthropicApiForm.url = urls.anthropicApiKey;

    const anthropicModelsForm = useForm({
        chat_model: providers.anthropic.chatModel ?? '',
    });
    anthropicModelsForm.url = urls.anthropicModels;

    const voyageApiForm = useForm({ voyage_api_key: '' });
    voyageApiForm.url = urls.voyageApiKey;

    const voyageModelsForm = useForm({
        embedding_model: providers.voyage.embeddingModel ?? '',
    });
    voyageModelsForm.url = urls.voyageModels;

    function saveProviders(event) {
        event.preventDefault();
        providerForm.post(urls.providers, { preserveScroll: true });
    }

    function disconnectOpenAi() {
        openAiApiForm.delete(urls.openaiDisconnect, { preserveScroll: true });
    }

    function disconnectAnthropic() {
        anthropicApiForm.delete(urls.anthropicDisconnect, { preserveScroll: true });
    }

    function disconnectVoyage() {
        voyageApiForm.delete(urls.voyageDisconnect, { preserveScroll: true });
    }

    const openAiSummary = providers.openai.hasCredential
        ? providers.openai.authMethod === 'api_key'
            ? `Using API key sk-...${providers.openai.apiKeySuffix}`
            : `Signed in as ${providers.openai.externalEmail ?? 'OpenAI Account'}`
        : null;

    return (
        <>
            <Head title="AI Settings" />

            <SidebarProvider defaultOpen>
                <AppSidebar auth={auth} urls={urls} activePage="ai" />

                <SidebarInset className="bg-background">
                    <header className="sticky top-0 z-20 border-b border-border/70 bg-background/85 backdrop-blur">
                        <div className="flex flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                            <div className="flex items-start gap-3">
                                <SidebarTrigger className="mt-1" />
                                <Separator orientation="vertical" className="mt-1 hidden h-6 bg-border lg:block" />

                                <div>
                                    <div className="inline-flex items-center gap-2 rounded-full border border-primary/25 bg-primary/10 px-3 py-1 text-xs font-medium uppercase tracking-[0.22em] text-primary">
                                        <BrainCircuit className="size-3.5" />
                                        AI settings
                                    </div>
                                    <h1 className="mt-3 text-2xl font-semibold tracking-tight text-foreground">
                                        Manage providers, credentials, and model defaults
                                    </h1>
                                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                                        Choose the chat and embedding providers used across automated replies,
                                        retrieval, and prompt generation.
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
                                <ThemeToggle />
                                <Button asChild variant="outline">
                                    <Link href={urls.imports}>Open imports</Link>
                                </Button>
                                <Button asChild className="bg-primary text-primary-foreground hover:bg-primary/90">
                                    <Link href={urls.whatsapp}>Open WhatsApp</Link>
                                </Button>
                            </div>
                        </div>
                    </header>

                    <div className="flex-1 px-4 py-6 sm:px-6">
                        {flash?.success ? (
                            <div className="mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-800 dark:text-emerald-200">
                                {flash.success}
                            </div>
                        ) : null}

                        <div className="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
                            <SectionCard
                                title="Provider selection"
                                description="Choose the provider used for chat replies and the provider used for embeddings and retrieval."
                                badge={
                                    <div className="hidden rounded-2xl border border-border bg-background px-4 py-3 text-sm text-muted-foreground lg:block">
                                        Shared app defaults
                                    </div>
                                }
                            >
                                <form onSubmit={saveProviders} className="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label className="block text-sm font-medium text-foreground" htmlFor="chat_provider">
                                            Chat provider
                                        </label>
                                        <select
                                            id="chat_provider"
                                            value={providerForm.data.chat_provider}
                                            onChange={(event) => providerForm.setData('chat_provider', event.target.value)}
                                            className="mt-2 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring"
                                        >
                                            <option value="openai">OpenAI</option>
                                            <option value="anthropic">Claude (Anthropic)</option>
                                        </select>
                                        <p className="mt-2 text-xs leading-5 text-muted-foreground">
                                            Used for auto-reply generation and chat completions.
                                        </p>
                                        <FieldError message={errors.chat_provider} />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-foreground" htmlFor="embedding_provider">
                                            Embedding provider
                                        </label>
                                        <select
                                            id="embedding_provider"
                                            value={providerForm.data.embedding_provider}
                                            onChange={(event) => providerForm.setData('embedding_provider', event.target.value)}
                                            className="mt-2 block w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring"
                                        >
                                            <option value="openai">OpenAI</option>
                                            <option value="voyage">Voyage AI</option>
                                        </select>
                                        <p className="mt-2 text-xs leading-5 text-muted-foreground">
                                            Used for message embeddings and retrieval queries.
                                        </p>
                                        <FieldError message={errors.embedding_provider} />
                                    </div>

                                    <div className="md:col-span-2">
                                        <Button
                                            type="submit"
                                            className="bg-primary text-primary-foreground hover:bg-primary/90"
                                            disabled={providerForm.processing}
                                        >
                                            {providerForm.processing ? (
                                                <>
                                                    <LoaderCircle className="size-4 animate-spin" />
                                                    Saving...
                                                </>
                                            ) : (
                                                'Save provider preferences'
                                            )}
                                        </Button>
                                    </div>
                                </form>
                            </SectionCard>

                            <SectionCard
                                title="Current setup"
                                description="A quick view of the provider stack currently available to this account."
                                badge={
                                    <div className="hidden rounded-2xl border border-border bg-background px-4 py-3 text-sm text-muted-foreground lg:block">
                                        Credentials + models
                                    </div>
                                }
                            >
                                <div className="grid gap-4 md:grid-cols-3">
                                    <div className="rounded-2xl border border-border bg-background p-4">
                                        <div className="flex items-center gap-2 text-sm font-medium text-foreground">
                                            <Bot className="size-4 text-primary" />
                                            OpenAI
                                        </div>
                                        <p className="mt-3 text-sm text-muted-foreground">
                                            {providers.openai.hasCredential ? 'Connected' : 'Not connected'}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-border bg-background p-4">
                                        <div className="flex items-center gap-2 text-sm font-medium text-foreground">
                                            <Sparkles className="size-4 text-primary" />
                                            Claude
                                        </div>
                                        <p className="mt-3 text-sm text-muted-foreground">
                                            {providers.anthropic.hasCredential ? 'Connected' : 'Not connected'}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-border bg-background p-4">
                                        <div className="flex items-center gap-2 text-sm font-medium text-foreground">
                                            <KeyRound className="size-4 text-primary" />
                                            Voyage
                                        </div>
                                        <p className="mt-3 text-sm text-muted-foreground">
                                            {providers.voyage.hasCredential ? 'Connected' : 'Not connected'}
                                        </p>
                                    </div>
                                </div>
                            </SectionCard>
                        </div>

                        <div className="mt-6 grid gap-6 xl:grid-cols-3">
                            <ProviderCard
                                title="OpenAI"
                                description="Supports chat completions and embeddings. You can use an API key or sign in with your OpenAI account."
                                connected={providers.openai.hasCredential}
                                credentialSummary={openAiSummary}
                                apiForm={openAiApiForm}
                                apiKeyField={{ key: 'api_key', label: 'API key' }}
                                apiKeyPlaceholder="sk-..."
                                modelForm={openAiModelsForm}
                                modelFields={[
                                    {
                                        key: 'chat_model',
                                        id: 'openai_chat_model',
                                        label: 'Chat model',
                                        placeholder: 'gpt-4o',
                                        help: 'Used when OpenAI is the selected chat provider.',
                                    },
                                    {
                                        key: 'embedding_model',
                                        id: 'openai_embedding_model',
                                        label: 'Embedding model',
                                        placeholder: 'text-embedding-3-small',
                                        help: 'Used when OpenAI handles retrieval embeddings.',
                                    },
                                ]}
                                disconnectLabel="Disconnect OpenAI"
                                onDisconnect={disconnectOpenAi}
                                oauthUrl={urls.openaiRedirect}
                                oauthLabel="Sign in with OpenAI"
                                errors={errors}
                            />

                            <ProviderCard
                                title="Claude"
                                description="Anthropic chat provider for reply generation."
                                connected={providers.anthropic.hasCredential}
                                credentialSummary={null}
                                apiForm={anthropicApiForm}
                                apiKeyField={{ key: 'anthropic_api_key', label: 'API key' }}
                                apiKeyPlaceholder="Anthropic API key"
                                modelForm={anthropicModelsForm}
                                modelFields={[
                                    {
                                        key: 'chat_model',
                                        id: 'anthropic_chat_model',
                                        label: 'Chat model',
                                        placeholder: 'claude-3-7-sonnet-latest',
                                        help: 'Used when Claude is the selected chat provider.',
                                    },
                                ]}
                                disconnectLabel="Disconnect Claude"
                                onDisconnect={disconnectAnthropic}
                                errors={errors}
                            />

                            <ProviderCard
                                title="Voyage"
                                description="Embedding provider for message indexing and retrieval."
                                connected={providers.voyage.hasCredential}
                                credentialSummary={null}
                                apiForm={voyageApiForm}
                                apiKeyField={{ key: 'voyage_api_key', label: 'API key' }}
                                apiKeyPlaceholder="Voyage API key"
                                modelForm={voyageModelsForm}
                                modelFields={[
                                    {
                                        key: 'embedding_model',
                                        id: 'voyage_embedding_model',
                                        label: 'Embedding model',
                                        placeholder: 'voyage-3-lite',
                                        help: 'Used when Voyage is the selected embedding provider.',
                                    },
                                ]}
                                disconnectLabel="Disconnect Voyage"
                                onDisconnect={disconnectVoyage}
                                errors={errors}
                            />
                        </div>

                        <section className="mt-6 rounded-[28px] border border-border bg-card p-6 shadow-sm">
                            <div className="flex items-center gap-2">
                                <Link2 className="size-4 text-primary" />
                                <p className="text-sm font-medium uppercase tracking-[0.22em] text-muted-foreground">
                                    How this affects the app
                                </p>
                            </div>

                            <div className="mt-5 grid gap-4 lg:grid-cols-3">
                                <div className="rounded-[24px] border border-border bg-background p-5">
                                    <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-foreground">
                                        Chat replies
                                    </h3>
                                    <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                        The selected chat provider powers auto-reply generation and any app-side chat
                                        completion features.
                                    </p>
                                </div>
                                <div className="rounded-[24px] border border-border bg-background p-5">
                                    <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-foreground">
                                        Retrieval
                                    </h3>
                                    <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                        The selected embedding provider controls how imported message history is indexed
                                        and queried for style examples.
                                    </p>
                                </div>
                                <div className="rounded-[24px] border border-border bg-background p-5">
                                    <h3 className="text-sm font-semibold uppercase tracking-[0.18em] text-foreground">
                                        Credentials
                                    </h3>
                                    <p className="mt-3 text-sm leading-6 text-muted-foreground">
                                        OpenAI supports API keys or account sign-in. Anthropic and Voyage use API-key
                                        credentials in this app.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>
                </SidebarInset>
            </SidebarProvider>
        </>
    );
}
