<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_import_runs', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->string('uploaded_filename');
            $table->string('storage_path');
            $table->string('me_name');
            $table->boolean('replace_existing')->default(false);
            $table->unsignedInteger('messages_imported')->nullable();
            $table->unsignedInteger('messages_skipped')->nullable();
            $table->unsignedInteger('conversations_count')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_import_runs');
    }
};
