<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add timestamps to chat_messages if not exists
        if (!Schema::hasColumn('chat_messages', 'created_at')) {
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->timestamps();
            });
        }

        // Add system_prompt column to chat_sessions for custom instructions
        if (!Schema::hasColumn('chat_sessions', 'system_prompt')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->text('system_prompt')->nullable()->after('model_used');
            });
        }

        // Add temperature column for response creativity control
        if (!Schema::hasColumn('chat_sessions', 'temperature')) {
            Schema::table('chat_sessions', function (Blueprint $table) {
                $table->decimal('temperature', 3, 2)->default(0.7)->after('system_prompt');
            });
        }

        // Add index for faster search
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->index('chat_session_id');
            $table->index('role');
            // Fulltext index removed - SQLite doesn't support it
            // Search will use LIKE query instead
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('is_archived');
            $table->index('is_pinned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex(['chat_session_id']);
            $table->dropIndex(['role']);
            $table->dropFullText(['content']);
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['is_archived']);
            $table->dropIndex(['is_pinned']);
        });

        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['system_prompt', 'temperature']);
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
