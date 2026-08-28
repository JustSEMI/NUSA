<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_session_id',
        'role',
        'content',
        'attachments',
        'model_used',
        'tokens_used',
        'is_edited',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_edited' => 'boolean',
        'tokens_used' => 'integer',
    ];

    // Add timestamps by default
    const UPDATED_AT = 'updated_at';
    const CREATED_AT = 'created_at';

    public function chatSession(): BelongsTo
    {
        return $this->belongsTo(ChatSession::class);
    }
}
