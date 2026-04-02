<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTrace extends Model
{
    protected $fillable = [
        'user_id',
        'contact_phone',
        'incoming_whatsapp_message_log_id',
        'outgoing_whatsapp_message_log_id',
        'status',
        'input_message',
        'retrieval_query',
        'recent_conversation',
        'retrieval_hits',
        'context_snippets',
        'final_prompt',
        'model',
        'model_response',
        'usage',
        'latency_ms',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'recent_conversation' => 'array',
            'retrieval_hits' => 'array',
            'context_snippets' => 'array',
            'final_prompt' => 'array',
            'usage' => 'array',
            'latency_ms' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incomingLog(): BelongsTo
    {
        return $this->belongsTo(WhatsappMessageLog::class, 'incoming_whatsapp_message_log_id');
    }

    public function outgoingLog(): BelongsTo
    {
        return $this->belongsTo(WhatsappMessageLog::class, 'outgoing_whatsapp_message_log_id');
    }
}
