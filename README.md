# Impersonator

An experimental Laravel application for AI-assisted WhatsApp replies built from your own historical message data.

> [!WARNING]
> This project is research-only software.
> It is inspired by the "Be Right Back" episode of *Black Mirror*, but it is not intended for deception, identity fraud, or covert impersonation.
> Do not use it to mislead people, bypass consent, or automate conversations in harmful or manipulative ways.

Impersonator explores a specific question: what happens if you combine message-history retrieval, prompt construction, and WhatsApp automation into a single end-to-end pipeline for stylistically similar replies?

The app imports message archives, generates embeddings, retrieves relevant examples from prior conversations, and uses an LLM to draft or send WhatsApp-style replies through WAHA. It also includes trace views so you can inspect what context was retrieved, what prompt was assembled, and what reply the model produced.

## Why This Exists

This repository is best understood as a research prototype around:

- retrieval-augmented generation over personal message history
- style imitation from past conversations
- operator-controlled WhatsApp auto-reply workflows
- prompt and trace inspection for debugging message pipelines

It is not presented as a product for real-world impersonation, and it should not be used that way.

## Core Features

- WhatsApp session management through [WAHA](https://waha.devlike.pro/) with QR-based device linking
- Public webhook ingestion for incoming WhatsApp events
- Database-backed queued auto-reply processing
- Import support for Facebook Messenger, Instagram, and WhatsApp exports
- Embedding generation and semantic retrieval with PostgreSQL + `pgvector`
- Configurable AI providers for chat and embeddings
- Per-contact auto-reply allowlist with optional custom instructions
- Per-contact preferred source conversation to bias style retrieval
- AI trace inspection showing recent context, retrieved hits, final prompt, latency, and usage
- Authenticated operator UI built with Laravel Breeze, Blade, Alpine.js, Tailwind CSS, and Vite

## How The Pipeline Works

1. Connect a WhatsApp account from `/whatsapp`.
2. Import prior message history from `/imports/messages`.
3. Build embeddings for retrieval from the imported message library.
4. Allow auto-replies for specific contacts from `/whatsapp/auto-reply`.
5. WAHA posts incoming message events to `/webhooks/whatsapp`.
6. The app queues a reply job for eligible contacts.
7. The reply service pulls recent WhatsApp context plus semantically similar historical messages.
8. The selected chat provider generates a reply.
9. The app sends the reply through WAHA and stores logs plus an AI trace for inspection.

## Tech Stack

- PHP 8.3
- Laravel 13
- PostgreSQL with `pgvector`
- Blade, Alpine.js, Tailwind CSS, Vite
- Laravel Breeze
- WAHA for WhatsApp connectivity and sending
- OpenAI and Anthropic for chat generation
- OpenAI and Voyage AI for embeddings
- Database-backed queues

## Supported Data Sources

The app currently supports importing:

- Facebook Messenger JSON exports
- Instagram JSON exports
- WhatsApp single-chat exports as `.txt` or `.zip`

Message imports intentionally skip non-text or low-signal content such as many system notices, attachment placeholders, calls, stickers, and similar noise so the retrieval layer stays focused on usable text examples.

More details live in [docs/message-imports.md](docs/message-imports.md).

## Requirements

- Docker
- Docker Compose

The default development flow uses Laravel Sail and the bundled service stack, so you do not need to manually install PHP, Composer, Node.js, PostgreSQL, or WAHA on your machine just to get the project running locally.

## Quick Start

### 1. Install dependencies and bootstrap the app

```bash
composer run setup
```

That script:

- installs Composer dependencies
- creates `.env` from `.env.example` if needed
- generates the application key
- runs migrations
- installs frontend dependencies
- builds frontend assets

If you prefer a fully container-first workflow, run the equivalent commands through Sail in your own environment. The README keeps `composer run setup` as the shortest path because it matches the scripts already defined in this repository.

### 2. Start supporting services

If you are using Sail:

```bash
./vendor/bin/sail up -d
```

If you already had an older local environment before the project switched to `pgvector`, recreate the `pgsql` container so the `vector` extension is available before running migrations or tests.

### 3. Review your environment configuration

At minimum, check the infrastructure-level values in `.env`:

```dotenv
APP_URL=http://localhost

DB_CONNECTION=pgsql

QUEUE_CONNECTION=database

WAHA_API_URL=http://waha:3000
WAHA_API_KEY=
WAHA_SESSION_NAME=default
```

Notes:

- webhook behavior depends on a public or otherwise reachable URL for `/webhooks/whatsapp`
- queues are part of the reply pipeline, so the worker must be running
- WAHA session names and webhook URLs should stay stable unless you intentionally change the integration
- AI credentials, active providers, and model choices are primarily managed from the app UI at `/ai`
- in normal use, contributors should configure providers through the settings page rather than editing provider credentials directly in `.env`

### 4. Run the application

```bash
composer run dev
```

This starts:

- the Laravel development server
- the queue listener
- the Laravel log tailer
- the Vite dev server

Open the app, register or log in, then visit:

- `/whatsapp` to connect a WhatsApp session
- `/ai` to configure chat providers, embedding providers, credentials, and model choices
- `/imports/messages` to import historical data
- `/whatsapp/auto-reply` to choose which contacts are allowed to receive automated replies

## Typical Local Workflow

### Connect WhatsApp

Go to `/whatsapp`, start a session, and scan the QR code. WAHA reports session updates back through the WhatsApp webhook, and the app maps those into `WhatsappSession` records.

### Configure AI providers

Go to `/ai` and choose:

- which provider should generate replies
- which provider should generate embeddings

OpenAI supports both API-key and OAuth-style credential flows in this app, and the repo also supports Anthropic for chat plus Voyage AI for embeddings.

### Import message history

Go to `/imports/messages` and upload:

- a Facebook Messenger export ZIP
- an Instagram export ZIP
- a WhatsApp `_chat.txt` or export ZIP

You can also provide a local path for large exports. After import, embeddings are rebuilt so the message library is ready for retrieval.

### Enable auto-reply for specific contacts

Go to `/whatsapp/auto-reply` and add a contact identifier. You can also:

- choose a preferred imported conversation as the strongest style source
- attach contact-specific AI instructions
- pause or remove contacts later

### Inspect reply traces

When replies are generated, recent activity on `/whatsapp/auto-reply` links to an AI trace inspector showing:

- the incoming message
- recent WhatsApp conversation context
- retrieved historical matches
- context snippet windows
- the final prompt sent to the model
- the generated reply, usage, and latency

## Security, Privacy, and Ethics

This project handles sensitive personal message history.

- Do not commit exports, credentials, or private conversation data.
- Do not log raw secrets or unnecessary personal content.
- Treat webhook routes as public entrypoints and keep them carefully scoped.
- Be especially cautious when experimenting with automated replies that simulate a person's style or tone.

If you share demos publicly, use scrubbed or synthetic data.

## Screenshots

These screenshots highlight the main operator workflow: importing message history, configuring AI providers, enabling per-contact automation, and connecting WhatsApp.

### Message import and retrieval library

Shows the historical message ingestion flow, import stats, and retrieval-library scale.

![Message Import](docs/screenshots/message-import.png)

### AI provider configuration

Shows provider selection plus credential and model configuration from the settings page.

![AI Settings](docs/screenshots/ai-settings.png)

### Contact-level auto-reply controls

Shows the allowlist model and per-contact customization for automated replies.

![Auto-Reply Contacts](docs/screenshots/auto-reply-contacts.png)

### WhatsApp connection flow

Shows the WhatsApp connection entry point in the operator UI.

![WhatsApp Connection](docs/screenshots/whatsapp-connect.png)

## Contributing

Small, focused changes are best.

- keep controllers thin
- prefer service-layer orchestration
- preserve the existing webhook, queue, and message-log pipeline
- update docs when changing import expectations, setup, or operator-facing workflows
- add focused tests for reply generation, retrieval, imports, credential flows, or WhatsApp handling changes

## License

MIT
