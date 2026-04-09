<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageImportRun extends Model
{
    protected $table = 'facebook_import_runs';

    protected $fillable = [
        'status',
        'uploaded_filename',
        'storage_path',
        'me_name',
        'replace_existing',
        'messages_imported',
        'messages_skipped',
        'conversations_count',
        'error',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'replace_existing' => 'boolean',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
