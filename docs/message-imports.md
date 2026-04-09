# Message Imports

Documentation of the Facebook Messenger, Instagram, and WhatsApp message export structures, import pipeline, and resulting database contents.

## Export Location

```
data/your_facebook_activity/messages/
data/your_instagram_activity/messages/
data/whatsapp/_chat.txt
```

## Relevant Source Directories

### `inbox/` — Primary conversations

Standard Messenger conversations. Each thread is a directory containing one or more `message_N.json` files.

### `e2ee_cutover/` — E2E encrypted conversations (Facebook only)

Conversations that migrated to end-to-end encryption. Same JSON format as inbox.

### `message_requests/` — Message requests

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
| `messages[].sender_name` | Who sent the message |
| `messages[].timestamp_ms` | Unix timestamp in milliseconds |
| `messages[].content` | The message text |

### Messages skipped during import

Some message entries represent non-text or low-value events and are skipped during import:
- **Calls** — have `call_duration` field
- **Photos/videos** — have `photos` or `videos` array
- **Stickers** — have `sticker` object
- **Shares** — have `share` object
- **Instagram attachment placeholders** — entries like `Bojan sent an attachment.` paired with attachment metadata

## Text Encoding

Facebook Messenger and Instagram exports use the same UTF-8 double-encoded text quirk. After `json_decode()`, the string contains UTF-8 byte sequences interpreted as individual Unicode codepoints. The fix is to convert from UTF-8 back down to ISO-8859-1:

```php
mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
```

## WhatsApp Single Chat Export

WhatsApp exports a single conversation as plain text. A typical export includes a `_chat.txt` file and, if media is included, additional attachment files beside it.

Example line format:

```text
[20. 3. 2026., 5:40:04 PM] Mama: Ee
```

Notes:
- Multi-line messages continue on following lines without a repeated timestamp header.
- System notices and placeholder lines like `Messages and calls are end-to-end encrypted...` and `<Media omitted>` are skipped during import.
- The authenticated upload page accepts either a direct WhatsApp `.txt` upload, a WhatsApp `.zip`, or a local `_chat.txt` path.

## Import Command

```bash
php artisan import:messages
php artisan import:messages --path=data/your_facebook_activity/messages --me="Rastko Todorovic"
php artisan import:messages --path=data/your_instagram_activity/messages --me="Rastko Todorovic"
php artisan import:messages --path=data/whatsapp/_chat.txt --me="Rastko Todorovic"
```

## UI Upload Flow

The app provides an authenticated upload page at `/imports/messages`.

The import page supports browser uploads and direct local-path imports. For very large exports, local-path import is more reliable because it avoids sending multi-GB files through the browser request.
