<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds columns for:
     * - Message edit tracking
     * - Token usage tracking
     * - Conversation export
     * - User preferences
     */
    public function up(): void
    {
        // Add token usage tracking to chat_messages
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->integer('tokens_used')->nullable()->after('model_used');
            $table->boolean('is_edited')->default(false)->after('tokens_used');
        });
        
        // Add user preferences table
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'key']);
        });
        
        // Add archived status to chat_sessions
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('model_used');
            $table->boolean('is_pinned')->default(false)->after('is_archived');
            $table->string('tags')->nullable()->after('is_pinned'); // JSON array of tags
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_sessions', function (Blueprint $table) {
            $table->dropColumn(['is_archived', 'is_pinned', 'tags']);
        });
        
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['tokens_used', 'is_edited']);
        });
        
        Schema::dropIfExists('user_preferences');
    }
};
