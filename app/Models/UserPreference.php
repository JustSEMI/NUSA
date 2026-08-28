<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a user preference by key.
     */
    public static function get(?int $userId, string $key, $default = null)
    {
        if (!$userId) {
            return $default;
        }

        $preference = self::where('user_id', $userId)
            ->where('key', $key)
            ->first();

        return $preference?->value ?? $default;
    }

    /**
     * Set a user preference.
     */
    public static function set(int $userId, string $key, $value): void
    {
        self::updateOrCreate(
            ['user_id' => $userId, 'key' => $key],
            ['value' => $value]
        );
    }
}
