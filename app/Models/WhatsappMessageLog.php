<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappMessageLog extends Model
{
    protected $table = 'whatsapp_message_log';

    protected $fillable = [
        'user_id',
        'contact_phone',
        'direction',
        'body',
        'waha_message_id',
        'context_messages_used',
        'error',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
