<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatSession extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'model_used',
        'system_prompt',
        'temperature',
        'is_archived',
        'is_pinned',
        'tags',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'is_pinned' => 'boolean',
        'tags' => 'array',
        'temperature' => 'decimal:2',
    ];

    /**
     * Scope untuk filter session berdasarkan user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
