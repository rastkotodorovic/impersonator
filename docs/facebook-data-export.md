# Message Data Export

Documentation of the Facebook Messenger and Instagram message export structure, import pipeline, and resulting database contents.

## Export Location

```
data/your_facebook_activity/messages/
data/your_instagram_activity/messages/
```

## Relevant Source Directories

### `inbox/` — Primary conversations (17 threads)

Standard Messenger conversations. Each thread is a directory containing one or more `message_N.json` files.

### `e2ee_cutover/` — E2E encrypted conversations (Facebook only)

Conversations that migrated to end-to-end encryption. Same JSON format as inbox.

### `message_requests/` — Message requests (1 thread)

Messages from people outside the friends list. Same format, low volume.

## Message JSON Structure

Facebook Messenger and Instagram exports both provide `message_N.json` files with the same core structure:

```json
{
  "participants": [
    { "name": "Contact Name" },
    { "name": "Rastko Todorovic" }
  ],
  "messages": [
    {
      "sender_name": "Contact Name",
      "timestamp_ms": 1718032680308,
      "content": "message text here",
      "is_geoblocked_for_viewer": false,
      "is_unsent_image_by_messenger_kid_parent": false
    }
  ],
  "title": "Contact Name",
  "thread_path": "inbox/username_id"
}
```

### Key fields for RAG ingestion

| Field | Description |
|---|---|
| `participants[].name` | Names of people in the conversation |
| `messages[].sender_name` | Who sent the message — used to distinguish Rastko's replies from others |
| `messages[].timestamp_ms` | Unix timestamp in milliseconds — messages are ordered newest-first in the JSON |
| `messages[].content` | The message text (not always present — calls, photos, etc. omit this) |

### Messages skipped during import

Some message entries represent non-text or low-value events and are skipped during import:
- **Calls** — have `call_duration` field (sometimes alongside a system-generated `content`)
- **Photos/videos** — have `photos` or `videos` array
- **Stickers** — have `sticker` object
- **Shares** — have `share` object (links)
- **Instagram attachment placeholders** — entries like `Bojan sent an attachment.` paired with attachment metadata

## Text Encoding

Facebook Messenger and Instagram exports use the same **UTF-8 double-encoded** text quirk. After `json_decode()`, the string contains UTF-8 byte sequences interpreted as individual Unicode codepoints. The fix is to convert from UTF-8 back down to ISO-8859-1 (single-byte), which recovers the original UTF-8 bytes:

```php
mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
```

This applies to `sender_name`, `content`, participant names, and conversation titles.

## Import Command

```bash
php artisan import:facebook-messages
php artisan import:facebook-messages --path=data/your_facebook_activity/messages --me="Rastko Todorovic"
php artisan import:facebook-messages --path=data/your_instagram_activity/messages --me="Rastko Todorovic"
```

The command scans available source directories, decodes text, skips non-text messages and Instagram attachment placeholders, and batch-upserts into the database. It is idempotent — re-running produces the same data without duplicates. The unique constraint is `(conversation_id, timestamp_ms, sender_name)`.

## UI Upload Flow

The app also provides an authenticated upload page at `/imports/facebook`.

Facebook Messenger:
1. Open Facebook and go to `Profile` then `Settings & privacy`
2. Open `Accounts Center`
3. Go to `Your information and permissions`
4. Choose `Download your information`
5. Start a new export and deselect everything except `Messages`
6. Set format to `JSON`, not HTML
7. Download the ZIP archive
8. For smaller exports, upload that ZIP in the app
9. For very large exports, extract the archive locally and import from the `your_facebook_activity/messages` folder path in the UI instead of browser upload

Instagram:
1. Open Instagram and go to `Settings and activity`
2. Open `Accounts Center`
3. Go to `Your information and permissions`
4. Choose `Download your information`
5. Create an export that includes `Messages`
6. Set format to `JSON`, not HTML
7. Download the ZIP archive
8. For smaller exports, upload that ZIP in the app
9. For very large exports, extract the archive locally and import from the `your_instagram_activity/messages` folder path in the UI instead of browser upload

The import page supports both browser ZIP uploads and direct local-path imports. For very large exports, local-path import is more reliable because it avoids sending multi-GB files through the browser request.

## Database Schema

### `conversations` table

| Column | Type | Description |
|---|---|---|
| id | bigint PK | |
| thread_path | string, unique | e.g. `inbox/dzil_5333691003376664` |
| title | string | Decoded contact or group name |
| source | string | `inbox`, `e2ee_cutover`, or `message_requests` |
| participants | json | Array of decoded participant names |
| participant_count | smallint | Number of participants |
| is_group_chat | boolean | True when participant_count > 2 |

### `messages` table

| Column | Type | Description |
|---|---|---|
| id | bigint PK | |
| conversation_id | FK → conversations | Cascade delete |
| sender_name | string | Decoded sender name |
| is_from_me | boolean | True when sender is "Rastko Todorovic" |
| content | text | Message text |
| timestamp_ms | unsigned bigint | Raw millisecond timestamp (used for dedup) |
| sent_at | timestamp | Derived from timestamp_ms (used for queries) |

**Indexes:** `(conversation_id, sent_at)` for chronological context retrieval, `is_from_me` for filtering, `sent_at` for time-range queries.

## What We Have Now

Data spans **March 2023 – March 2026** (3 years).

### Summary

| Metric | Count |
|---|---|
| Conversations | 52 |
| Total text messages | 44,483 |
| Messages from me (Rastko) | 13,524 (30%) |
| Messages from others | 30,959 (70%) |
| Skipped non-text entries | 3,593 |
| 1-on-1 chats | 41 |
| Group chats | 11 |

### By source

| Source | Conversations | Messages |
|---|---|---|
| inbox | 17 | 29,868 |
| e2ee_cutover | 34 | 14,599 |
| message_requests | 1 | 16 |

### Top 10 conversations by volume

| Conversation | Source | Total msgs | My msgs |
|---|---|---|---|
| Dzil | inbox | 25,570 | 6,153 |
| Горан Дурић | e2ee_cutover | 2,898 | 1,388 |
| Pauza | inbox | 2,722 | 637 |
| Nikola Jovanovic | e2ee_cutover | 2,209 | 1,021 |
| Огњен Дачић | e2ee_cutover | 1,822 | 688 |
| Mile Panić | e2ee_cutover | 1,433 | 618 |
| Bojan Panic | e2ee_cutover | 1,415 | 665 |
| Владан Суботић | e2ee_cutover | 812 | 399 |
| Anja Petrović | e2ee_cutover | 783 | 337 |
| Mihajlo Stevanovic | e2ee_cutover | 576 | 253 |

### Notes for RAG

- The Dzil conversation alone accounts for 57% of all messages and 45% of "my" messages — it will dominate style training unless weighted.
- 30% of messages are from Rastko, giving a solid corpus for response style modeling.
- Group chats (11) contain multi-party dynamics — useful for understanding how Rastko responds in group settings vs 1-on-1.
- The data is multilingual (Serbian Cyrillic/Latin and English).

## Not Relevant

These files in the export contain no usable conversation data:

| File | Why it's excluded |
|---|---|
| `ai_conversations.json` | Meta AI chatbot settings, not user conversations |
| `autofill_information.json` | Personal info (email, phone, address) — sensitive, do not ingest |
| `secret_conversations.json` | Device metadata and IP addresses only, no message content |
| `information_about_your_devices.json` | Device info |
| `messaging_settings.json` | App settings |
| `messenger_active_status_settings.json` | Status toggle settings |
| `messenger_active_status_platform_settings.json` | Platform status settings |
| `messenger_ui_settings.json` | UI preferences |
| `your_chat_settings_on_web.json` | Web chat settings |
| `encrypted_messaging_backup_settings.json` | Backup toggle |
| `chat_invites_received.json` | Group chat invites |
| `your_end-to-end_encryption_enabled_messenger_device.json` | E2EE device info |
| `your_messenger_app_install_information.json` | Install metadata |
| `photos/` | Shared images — no text value for RAG |
| `stickers_used/` | Sticker image files |
