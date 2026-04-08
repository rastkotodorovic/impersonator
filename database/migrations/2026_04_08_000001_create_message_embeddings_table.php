<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('message_embeddings', function (Blueprint $table) {
            $table->foreignId('message_id')->primary()->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE message_embeddings ADD COLUMN embedding vector(1536)');
        DB::statement("ALTER TABLE message_embeddings ADD COLUMN content_search tsvector GENERATED ALWAYS AS (to_tsvector('simple', coalesce(content, ''))) STORED");
        DB::statement('CREATE INDEX message_embeddings_content_search_index ON message_embeddings USING GIN (content_search)');
        DB::statement('CREATE INDEX message_embeddings_embedding_hnsw_index ON message_embeddings USING hnsw (embedding vector_cosine_ops)');
    }

    public function down(): void
    {
        Schema::dropIfExists('message_embeddings');
    }
};
