<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_reply_contacts', function (Blueprint $table) {
            $table->foreignId('preferred_conversation_id')
                ->nullable()
                ->after('name')
                ->constrained('conversations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auto_reply_contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('preferred_conversation_id');
        });
    }
};
