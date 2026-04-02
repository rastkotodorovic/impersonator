<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function aiTrace(): HasOne
    {
        return $this->hasOne(AiTrace::class, 'outgoing_whatsapp_message_log_id');
    }
}
