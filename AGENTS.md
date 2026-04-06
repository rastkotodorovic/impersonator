Impersonator is a Laravel 13 application for AI-assisted message replies across WhatsApp and Telegram. It combines webhook ingestion, queued auto-reply jobs, retrieval over imported message history, and OpenAI-generated responses. Treat changes as part of a message-processing pipeline rather than isolated controller work.
- `app/Http/Controllers` handles UI actions, webhook entrypoints, and auth flows. Keep controllers thin and move orchestration into services.
- `app/Services` contains the core business logic and third-party integrations, including WAHA, Telegram, OpenAI, Meilisearch, prompt building, and channel abstractions.
- `app/Services/Channels` and `app/Contracts/MessageChannelInterface` define channel-specific send behavior. If you add or change channel support, update the shared abstractions and the concrete channel implementations together.
- `app/Jobs` contains async processing for inbound auto-replies. Changes that affect reply generation usually also affect queue jobs, webhook handlers, and logging/tracing models.
- `app/Models` includes message logs, traces, conversations, credentials, sessions, and auto-reply contact state. Preserve existing naming and relationships when extending data flow.
- `routes/web.php` contains both authenticated UI routes and public webhook endpoints. Be careful not to accidentally add auth middleware to webhook routes.
- `resources/views` contains Blade UI for WhatsApp, Telegram, chat testing, OpenAI credential flows, and trace/log screens.
- `docs/` stores import-format and operational notes. Add or update docs here when changing import expectations or operator workflows.
Understand these flows before editing related code:
- WhatsApp auto-reply flow: webhook -> controller -> queued job -> `AutoReplyService` -> retrieval/context building -> channel send -> message log / AI trace updates.
- Telegram flow mirrors the same pattern conceptually; keep behavior aligned when shared logic changes.
- Chat testing flow in `ChatController` should stay consistent with the prompt-building logic used for automated replies when appropriate.
- Import and embedding flow depends on historical message ingestion plus `php artisan embeddings:generate --fresh` to rebuild retrieval data.
- OpenAI credential handling supports both API key and OAuth-style flows; do not break one while modifying the other.
Core application code lives in `app/`. Use these conventions:
- HTTP controllers: `app/Http/Controllers`
- Form requests: `app/Http/Requests`
- Services and integrations: `app/Services`
- Queue jobs: `app/Jobs`
- Console commands: `app/Console/Commands`
- Eloquent models: `app/Models`
- Routes: `routes/`
- Blade views and frontend assets: `resources/`
- Migrations, factories, seeders: `database/`
- Feature and unit tests: `tests/Feature`, `tests/Unit`
- Follow Laravel conventions and PSR-12 with 4-space indentation.
- Use `PascalCase` for classes, `camelCase` for methods/variables, and snake_case for database columns and migration names.
- Prefer constructor injection and service classes over static helpers or fat controllers.
- Use Form Request classes for non-trivial validation.
- Keep prompt construction, retrieval, and third-party API calls out of controllers.
- Reuse existing patterns for logging incoming/outgoing messages and AI traces instead of creating parallel mechanisms.
- Keep changes minimal and localized; do not rename stable routes, env vars, or config keys without a strong reason.
This app depends on several external systems configured through `.env` and `config/services.php`:
- WAHA for WhatsApp session management and message sending
- Telegram bot/webhook configuration
- OpenAI for embeddings and reply generation
- Meilisearch for semantic retrieval
- Database-backed queues for async auto-replies
When changing integration behavior:
- Verify expected request/response shapes before changing service methods.
- Preserve timeout/error-handling patterns in service classes.
- Keep webhook URLs, session names, and external identifiers stable unless the task explicitly changes them.
- Never commit secrets from `.env`, auth exports, or imported personal message history.
Use the existing scripts first:
- `composer run setup` installs dependencies, prepares `.env`, generates the app key, runs migrations, and builds frontend assets.
- `./vendor/bin/sail up -d` starts Docker-backed services such as Postgres, Meilisearch, and WAHA when using Sail.
- `composer run dev` runs the Laravel server, queue worker, log tailer, and Vite dev server concurrently.
- `composer run test` clears config and runs the full test suite.
- `php artisan test tests/Unit/AutoReplyServiceTest.php` runs a focused test file.
- `./vendor/bin/pint` formats PHP changes.
- `php artisan embeddings:generate --fresh` rebuilds the retrieval index after imports.
- Put request, route, controller, and UI behavior in `tests/Feature`.
- Put isolated service, prompt, and retrieval logic in `tests/Unit`.
- Add or update focused tests when changing auto-reply generation, channel dispatching, webhook handling, or credential flows.
- For queue-related changes, verify the job entrypoint and the downstream service behavior together.
- If changing retrieval or prompt-building behavior, prefer assertions that cover recent conversation inclusion, retrieved snippets, and trace/log persistence.
When working in this repo:
- Read the relevant controller, service, job, model, and route together for any workflow change.
- Check for existing tests before adding new abstractions.
- Prefer small, surgical changes over broad refactors.
- Update docs in `README.md` or `docs/` when setup, imports, env vars, or operator-visible behavior changes.
- Run targeted tests first, then broader validation if the environment allows.
- Format changed PHP files with Pint before finishing.
- This project handles personal message history and generated impersonation-style replies; treat all imported chat data as sensitive.
- Avoid logging secrets, raw credentials, or unnecessary personal content.
- Be careful with webhook endpoints because they are public routes.
- When modifying auto-reply logic, validate that opt-in contact controls and async processing still behave safely.
Use imperative, scoped commit messages such as `Add Telegram trace details to auto-reply logs`. In pull requests, include:
- a short user-visible summary
- any migration, queue, webhook, or env changes
- test coverage notes
- screenshots when updating Blade views or dashboard flows
