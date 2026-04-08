<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageEmbedding extends Model
{
    protected $primaryKey = 'message_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'message_id',
        'content',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
