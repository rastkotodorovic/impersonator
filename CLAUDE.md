# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Impersonator** is a WhatsApp auto-reply application that impersonates the user. It connects to WhatsApp via WAHA (WhatsApp HTTP API), ingests the user's Facebook-exported message history into a vector database, and uses RAG (Retrieval-Augmented Generation) to generate responses that mimic the user's communication style. When someone messages the user on WhatsApp, the system retrieves relevant past conversations and uses an LLM to craft a reply.

### Current State

V1 has the WhatsApp connection layer working (QR code auth via WAHA, session management, webhooks). Still to be built: Facebook data import, vector database integration, RAG pipeline, and LLM-powered response generation.

## Commands

```bash
composer run setup    # Full first-time setup (deps, env, key, migrate, npm build)
composer run dev      # Start all dev services concurrently (server, queue, logs, vite)
composer run test     # Clear config cache + run PHPUnit tests
php artisan test --filter=SomeTest  # Run a single test class
php artisan test --filter=test_method_name  # Run a single test method
./vendor/bin/pint     # Run Laravel Pint code style fixer
```

## Architecture

### Stack

- **Backend:** Laravel 13, PHP 8.3
- **Frontend:** Blade + Alpine.js + Tailwind CSS, built with Vite
- **Auth:** Laravel Breeze (session-based)
- **Database:** SQLite (dev), MySQL 8.4 (Docker/prod)
- **Queue/Cache/Session:** All database-backed
- **WhatsApp:** WAHA service in Docker (port 3000)

### WhatsApp Integration Flow

1. User authenticates and visits `/whatsapp`
2. `WhatsappController::connect()` creates a WAHA session and stores it in `whatsapp_sessions`
3. Frontend polls `GET /whatsapp/qr-code` every 2.5s to display QR code
4. User scans QR with their phone; WAHA reports status via `POST /webhooks/whatsapp`
5. Session status transitions: `disconnected` -> `qr_pending` -> `connected`

### Key Services

- **WahaService** (`app/Services/WahaService.php`) - HTTP client wrapper for WAHA API. Registered as singleton in `AppServiceProvider`. Config sourced from `services.waha.*` (env vars `WAHA_API_URL`, `WAHA_API_KEY`).

### Models

- **User** - Standard Breeze user, `hasOne` WhatsappSession
- **WhatsappSession** - Tracks WAHA session state per user (session_name, status, phone_number, connected_at, metadata JSON)

### Docker Services (compose.yaml)

Laravel app, MySQL, Redis, Meilisearch, Mailpit, Selenium, and WAHA are all available via `./vendor/bin/sail`.
