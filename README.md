# Impersonator

> [!WARNING]
> This is a fun project inspired by "Be Right Back" from *Black Mirror* season 2, episode 1.
> It is not intended to be used as a real impersonator, and it should not be used in harmful, deceptive, or otherwise negative ways.

Impersonator is a Laravel application that experiments with AI-assisted WhatsApp auto-replies using your own historical message data.

The app connects to WhatsApp through [WAHA](https://waha.devlike.pro/), imports message history from a Facebook data export, indexes that history for semantic retrieval with Meilisearch, and uses OpenAI models to generate replies that resemble your tone and phrasing.

## What It Does

- Connects a WhatsApp session and exposes webhook-based message handling
- Imports historical conversation data from a Facebook export
- Generates embeddings and stores searchable message chunks in Meilisearch
- Retrieves relevant past messages to build context for replies
- Lets you test conversations in a chat UI before enabling automation
- Supports auto-reply toggles for specific WhatsApp contacts
- Supports OpenAI API key auth and OAuth-based credential flows

## How It Works

1. Connect your WhatsApp account through WAHA.
2. Import your Facebook-exported messages into the app database.
3. Generate embeddings so past messages can be searched semantically.
4. When a new message arrives, the app retrieves related conversation snippets and recent chat history.
5. OpenAI generates a reply based on that context.
6. If auto-reply is enabled for the sender, the app sends the reply back through WAHA.

## Tech Stack

- PHP 8.3
- Laravel 13
- Blade, Alpine.js, Tailwind CSS, Vite
- Laravel Breeze for auth scaffolding
- WAHA for WhatsApp session management
- OpenAI for embeddings and chat completions
- Meilisearch for semantic retrieval / RAG context lookup

## Project Structure

- `app/Services` contains the main integration and orchestration logic
- `app/Console/Commands` contains import and embedding generation commands
- `app/Http/Controllers` contains WhatsApp, OpenAI, chat, and auto-reply flows
- `routes/web.php` defines the authenticated UI and webhook endpoints
- `resources/views` contains the dashboard, WhatsApp, chat, and auth views
- `docs/facebook-data-export.md` documents the expected import data format

## Requirements

- PHP 8.3+
- Composer
- Node.js and npm
- Docker, if you want to run the bundled service stack with Sail

## Setup

```bash
composer run setup
./vendor/bin/sail up -d
composer run dev
```

The setup script installs PHP and JS dependencies, creates `.env` if needed, generates an app key, runs migrations, and builds frontend assets.

## Environment

At minimum, review these values in `.env`:

```dotenv
WAHA_API_URL=http://waha:3000
WAHA_API_KEY=
WAHA_SESSION_NAME=default

OPENAI_CLIENT_ID=
OPENAI_CLIENT_SECRET=
OPENAI_REDIRECT_URI=/openai/callback
OPENAI_DEFAULT_MODEL=gpt-4o
OPENAI_API_KEY=
OPENAI_EMBEDDING_MODEL=text-embedding-3-small

MEILISEARCH_HOST=http://meilisearch:7700
MEILISEARCH_KEY=
```

## Common Commands

```bash
composer run setup
./vendor/bin/sail up -d
composer run dev
composer run test
./vendor/bin/pint
php artisan embeddings:generate --fresh
```

## Main App Areas

- `/whatsapp` manages WAHA connection, QR code retrieval, status, and disconnect flow
- `/whatsapp/auto-reply` manages which contacts can receive automated replies
- `/openai/*` manages OpenAI credentials
- `/chat` provides a UI for testing prompt and reply behavior
- `/imports/facebook` uploads a Facebook messages export and rebuilds embeddings automatically
- `/webhooks/whatsapp` receives incoming WhatsApp events

## Development Notes

- Keep controllers thin and move external API logic into `app/Services`
- Format PHP changes with `./vendor/bin/pint`
- Put request and route behavior in `tests/Feature`
- Put isolated service behavior in `tests/Unit`
- When changing auto-reply behavior, verify queue processing and webhook handling together
- Facebook imports run synchronously from the upload request and rebuild embeddings immediately after import

## License

MIT
