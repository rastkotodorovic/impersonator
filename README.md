# Impersonator

A WhatsApp auto-reply bot that impersonates you using your own messaging history.

Impersonator connects to your WhatsApp account via [WAHA](https://waha.devlike.pro/), ingests your Facebook-exported message data into a vector database, and uses RAG (Retrieval-Augmented Generation) with an LLM to generate replies that match your communication style.

## How It Works

1. **Connect WhatsApp** - Link your WhatsApp account by scanning a QR code
2. **Import Messages** - Upload your Facebook data export to build a conversation history
3. **Vector Search** - Incoming messages are matched against your historical conversations using semantic search
4. **AI Reply** - An LLM generates a response based on the retrieved context, mimicking your tone and style

## Requirements

- PHP 8.3+
- Node.js & npm
- Composer
- Docker (for WAHA and optional services)

## Setup

```bash
# Clone and install
composer run setup

# Start Docker services (WAHA, MySQL, etc.)
./vendor/bin/sail up -d

# Start development servers
composer run dev
```

Copy `.env.example` to `.env` and configure your WAHA connection:

```
WAHA_API_URL=http://waha:3000
WAHA_API_KEY=your-api-key
WAHA_SESSION_NAME=default
```

## Development

```bash
composer run dev      # Runs PHP server, queue worker, log viewer, and Vite concurrently
composer run test     # Run test suite
./vendor/bin/pint     # Fix code style
```

## Tech Stack

- **Backend:** Laravel 13, PHP 8.3
- **Frontend:** Blade, Alpine.js, Tailwind CSS
- **WhatsApp:** WAHA (WhatsApp HTTP API)
- **Build:** Vite
- **Auth:** Laravel Breeze

## License

MIT
