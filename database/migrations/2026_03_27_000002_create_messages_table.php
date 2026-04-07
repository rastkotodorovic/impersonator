<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->string('sender_name');
            $table->boolean('is_from_me')->default(false);
            $table->text('content');
            $table->unsignedBigInteger('timestamp_ms');
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->unique(['conversation_id', 'timestamp_ms', 'sender_name'], 'messages_unique_msg');
            $table->index(['conversation_id', 'sent_at']);
            $table->index('is_from_me');
            $table->index('sent_at');
        });

        DB::statement("CREATE INDEX messages_content_fulltext ON messages USING GIN (to_tsvector('simple', content))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS messages_content_fulltext');
        Schema::dropIfExists('messages');
    }
};
