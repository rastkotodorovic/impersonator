<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_reply_contacts', function (Blueprint $table) {
            $table->string('channel')->default('whatsapp')->after('user_id');
            $table->string('identifier')->nullable()->after('phone_number');
        });

        DB::table('auto_reply_contacts')->update([
            'identifier' => DB::raw('phone_number'),
        ]);

        Schema::table('auto_reply_contacts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'phone_number']);
            $table->unique(['user_id', 'channel', 'identifier'], 'auto_reply_contacts_user_channel_identifier_unique');
            $table->index(['user_id', 'channel', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('auto_reply_contacts', function (Blueprint $table) {
            $table->dropUnique('auto_reply_contacts_user_channel_identifier_unique');
            $table->dropIndex(['user_id', 'channel', 'is_active']);
            $table->dropColumn(['channel', 'identifier']);
            $table->unique(['user_id', 'phone_number']);
        });
    }
};
