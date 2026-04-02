<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('contact_phone');
            $table->foreignId('incoming_whatsapp_message_log_id')->nullable()->constrained('whatsapp_message_log')->nullOnDelete();
            $table->foreignId('outgoing_whatsapp_message_log_id')->nullable()->unique()->constrained('whatsapp_message_log')->nullOnDelete();
            $table->string('status')->default('processing');
            $table->text('input_message');
            $table->string('retrieval_query')->nullable();
            $table->json('recent_conversation')->nullable();
            $table->json('retrieval_hits')->nullable();
            $table->json('context_snippets')->nullable();
            $table->json('final_prompt')->nullable();
            $table->string('model')->nullable();
            $table->longText('model_response')->nullable();
            $table->json('usage')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['contact_phone', 'created_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_traces');
    }
};
