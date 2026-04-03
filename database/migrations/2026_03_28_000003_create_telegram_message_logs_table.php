<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('chat_id');
            $table->string('contact_identifier');
            $table->string('contact_name')->nullable();
            $table->string('direction');
            $table->text('body');
            $table->string('telegram_message_id')->nullable()->unique();
            $table->unsignedInteger('context_messages_used')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'chat_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_message_logs');
    }
};
