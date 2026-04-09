# Impersonator

> [!WARNING]
> This is a fun project inspired by "Be Right Back" from *Black Mirror* season 2, episode 1.
> It is not intended to be used as a real impersonator, and it should not be used in harmful, deceptive, or otherwise negative ways.

Impersonator is a Laravel application that experiments with AI-assisted WhatsApp auto-replies using your own historical message data.

The app connects to WhatsApp through [WAHA](https://waha.devlike.pro/), imports message history from Facebook Messenger, Instagram, or WhatsApp exports, stores semantic embeddings in PostgreSQL with `pgvector`, and uses OpenAI models to generate replies that resemble your tone and phrasing.

## What It Does

- Connects a WhatsApp session and exposes webhook-based message handling
- Imports historical conversation data from Facebook Messenger, Instagram, or WhatsApp exports
- Generates embeddings and stores searchable message chunks in PostgreSQL with `pgvector`
- Retrieves relevant past messages to build context for replies
- Supports auto-reply toggles for specific WhatsApp contacts
- Supports OpenAI API key auth and OAuth-based credential flows

## How It Works

1. Connect your WhatsApp account through WAHA.
2. Import your Facebook Messenger, Instagram, or WhatsApp messages into the app database.
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
- PostgreSQL with `pgvector` for hybrid semantic retrieval / RAG context lookup

## Project Structure

- `app/Services` contains the main orchestration logic
- `app/Integrations` contains WAHA, OpenAI, and pgvector-specific integration helpers
- `app/Console/Commands` contains import and embedding generation commands
- `app/Http/Controllers` contains WhatsApp, OpenAI, import, and auto-reply flows
- `routes/web.php` defines the authenticated UI and webhook endpoints
- `resources/views` contains the dashboard, WhatsApp, imports, OpenAI settings, and auth views
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

If you are upgrading an existing checkout from the old Meilisearch-based setup, recreate the `pgsql` container after pulling the new `compose.yaml` so PostgreSQL includes the `pgvector` extension.

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
# Optional fallback for embeddings / CLI tasks when no OpenAI credential is connected in the app
OPENAI_API_KEY=
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
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
- `/openai` manages OpenAI credentials and connection settings
- `/imports/facebook` uploads a Facebook Messenger, Instagram, or WhatsApp messages export and rebuilds embeddings automatically
- `/webhooks/whatsapp` receives incoming WhatsApp events

## Development Notes

- Keep controllers thin and move orchestration into `app/Services` and third-party access into `app/Integrations`
- Format PHP changes with `./vendor/bin/pint`
- Put request and route behavior in `tests/Feature`
- Put isolated service behavior in `tests/Unit`
- When changing auto-reply behavior, verify queue processing and webhook handling together
- Message imports run synchronously from the upload request and rebuild embeddings immediately after import

## License

MIT
