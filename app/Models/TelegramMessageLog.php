<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramMessageLog extends Model
{
    protected $fillable = [
        'user_id',
        'chat_id',
        'contact_identifier',
        'contact_name',
        'direction',
        'body',
        'telegram_message_id',
        'context_messages_used',
        'error',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
