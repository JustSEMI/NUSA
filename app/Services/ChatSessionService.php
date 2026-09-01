<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\ChatMessage;
use Illuminate\Support\Str;

class ChatSessionService
{
    protected FileAttachmentService $attachmentService;

    public function __construct(FileAttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Get existing session or create new one.
     */
    public function getOrCreateSession(int $userId, ?int $sessionId, string $message, string $model): int
    {
        if ($sessionId) {
            return $sessionId;
        }

        return $this->createSession($userId, $message, $model);
    }

    /**
     * Create a new chat session.
     */
    public function createSession(int $userId, string $message, string $model): int
    {
        $title = !empty(trim($message)) ? $this->generateTitle($message) : 'Lampiran Baru';

        $session = ChatSession::create([
            'user_id' => $userId,
            'title' => $title,
            'model_used' => $model,
        ]);

        return $session->id;
    }

    /**
     * Generate session title from message.
     */
    private function generateTitle(string $message): string
    {
        return Str::limit($message, 50);
    }

    /**
     * Save user message to database.
     */
    public function saveUserMessage(int $sessionId, string $message, string $model, ?array $attachments = null): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $sessionId,
            'role' => 'user',
            'content' => $message,
            'attachments' => $attachments,
            'model_used' => $model,
        ]);
    }

    /**
     * Save assistant message to database.
     */
    public function saveAssistantMessage(int $sessionId, string $reply, string $model, array $usage = []): ChatMessage
    {
        return ChatMessage::create([
            'chat_session_id' => $sessionId,
            'role' => 'assistant',
            'content' => $reply,
            'model_used' => $model,
            'tokens_used' => $usage['output_tokens'] ?? null,
        ]);
    }

    /**
     * Get conversation history for context formatted for AI API.
     */
    public function getConversationHistory(int $sessionId, int $limit = null): array
    {
        $limit = $limit ?? config('services.ai.context_limit', 10);

        $messages = ChatMessage::where('chat_session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get();

        $history = [];

        foreach ($messages as $msg) {
            $formattedContent = $this->formatMessageContent($msg);
            $history[] = [
                'role' => $msg->role,
                'content' => $formattedContent,
            ];
        }

        return $this->ensureAlternatingRoles($history);
    }

    /**
     * Format message content (handling text + images + document attachments).
     */
    protected function formatMessageContent(ChatMessage $msg): string|array
    {
        if ($msg->role === 'assistant' || empty($msg->attachments)) {
            return $msg->content;
        }

        $hasImages = false;
        $contentBlocks = [];
        $documentTextParts = [];

        foreach ($msg->attachments as $att) {
            if (!empty($att['is_image']) && !empty($att['path'])) {
                $imgData = $this->attachmentService->getImageBase64($att['path']);
                if ($imgData) {
                    $hasImages = true;
                    $contentBlocks[] = [
                        'type' => 'image',
                        'source' => [
                            'type' => 'base64',
                            'media_type' => $imgData['media_type'],
                            'data' => $imgData['data'],
                        ],
                    ];
                }
            } elseif (!empty($att['extracted_text'])) {
                $docName = $att['name'] ?? 'Dokumen';
                $documentTextParts[] = "[Lampiran Dokumen: {$docName}]\n--- Isi Dokumen ---\n{$att['extracted_text']}\n--- Akhir Dokumen ---";
            }
        }

        $userText = trim($msg->content);
        $fullText = '';

        if (!empty($documentTextParts)) {
            $fullText .= implode("\n\n", $documentTextParts) . "\n\n";
        }

        if (!empty($userText)) {
            $fullText .= $userText;
        } elseif (empty($fullText) && $hasImages) {
            $fullText = "Tolong analisis dan jelaskan gambar yang saya lampirkan.";
        }

        if ($hasImages) {
            if (!empty($fullText)) {
                $contentBlocks[] = [
                    'type' => 'text',
                    'text' => $fullText,
                ];
            }
            return $contentBlocks;
        }

        return !empty($fullText) ? $fullText : $msg->content;
    }

    /**
     * Ensure messages have alternating roles (user/assistant).
     * Anthropic requires: user -> assistant -> user -> assistant ...
     */
    private function ensureAlternatingRoles(array $messages): array
    {
        if (empty($messages)) {
            return $messages;
        }

        $cleaned = [];

        foreach ($messages as $msg) {
            $last = count($cleaned) > 0 ? $cleaned[count($cleaned) - 1] : null;

            // Merge consecutive messages with same role
            if ($last && $msg['role'] === $last['role']) {
                $lastContent = $cleaned[count($cleaned) - 1]['content'];
                $currContent = $msg['content'];

                if (is_string($lastContent) && is_string($currContent)) {
                    $cleaned[count($cleaned) - 1]['content'] .= "\n\n" . $currContent;
                } else {
                    $mergedBlocks = [];
                    // Normalize last
                    if (is_string($lastContent)) {
                        $mergedBlocks[] = ['type' => 'text', 'text' => $lastContent];
                    } elseif (is_array($lastContent)) {
                        $mergedBlocks = array_merge($mergedBlocks, $lastContent);
                    }
                    // Normalize current
                    if (is_string($currContent)) {
                        $mergedBlocks[] = ['type' => 'text', 'text' => $currContent];
                    } elseif (is_array($currContent)) {
                        $mergedBlocks = array_merge($mergedBlocks, $currContent);
                    }
                    $cleaned[count($cleaned) - 1]['content'] = $mergedBlocks;
                }
                continue;
            }

            $cleaned[] = $msg;
        }

        return $cleaned;
    }

    /**
     * Get user's chat sessions.
     */
    public function getUserSessions(int $userId): array
    {
        return ChatSession::where('user_id', $userId)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get chat session with messages.
     */
    public function getSessionWithMessages(int $userId, int $sessionId): ?array
    {
        $session = $this->findSessionByUser($userId, $sessionId);

        if (!$session) {
            return null;
        }

        $messages = ChatMessage::where('chat_session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();

        return [
            'session' => $session,
            'messages' => $messages,
        ];
    }

    /**
     * Find session by user ID (ownership check).
     */
    protected function findSessionByUser(int $userId, int $sessionId): ?ChatSession
    {
        return ChatSession::forUser($userId)->find($sessionId);
    }

    /**
     * Delete chat session.
     */
    public function deleteSession(int $userId, int $sessionId): bool
    {
        $session = $this->findSessionByUser($userId, $sessionId);

        if (!$session) {
            return false;
        }

        $session->delete();
        return true;
    }

    /**
     * Toggle pin status for a chat session.
     */
    public function togglePinSession(int $userId, int $sessionId): bool
    {
        $session = $this->findSessionByUser($userId, $sessionId);

        if (!$session) {
            return false;
        }

        $session->is_pinned = !$session->is_pinned;
        $session->save();
        return true;
    }

    /**
     * Create empty session with custom title.
     */
    public function createEmptySession(int $userId, string $title, string $model = null): int
    {
        $session = ChatSession::create([
            'user_id' => $userId,
            'title' => $title,
            'model_used' => $model ?? config('services.ai.model', 'qwen-3.5-flash'),
        ]);

        return $session->id;
    }

    /**
     * Rename chat session.
     */
    public function renameSession(int $userId, int $sessionId, string $newTitle): bool
    {
        $session = $this->findSessionByUser($userId, $sessionId);
        
        if (!$session) {
            return false;
        }
        
        return $session->update(['title' => $newTitle]);
    }

    /**
     * Update session settings (system prompt, temperature).
     */
    public function updateSessionSettings(int $userId, int $sessionId, array $settings): bool
    {
        $session = $this->findSessionByUser($userId, $sessionId);
        
        if (!$session) {
            return false;
        }
        
        return $session->update($settings);
    }

    /**
     * Delete individual message.
     */
    public function deleteMessage(int $userId, int $sessionId, int $messageId): bool
    {
        $message = ChatMessage::where('id', $messageId)
            ->where('chat_session_id', $sessionId)
            ->first();

        if (!$message) {
            return false;
        }

        return $message->delete();
    }

    /**
     * Search messages in a session.
     */
    public function searchInSession(int $userId, int $sessionId, string $query): array
    {
        $messages = ChatMessage::where('chat_session_id', $sessionId)
            ->where('content', 'LIKE', "%{$query}%")
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'role' => $msg->role,
                'content' => $msg->content,
                'attachments' => $msg->attachments,
                'created_at' => $msg->created_at?->toIso8601String(),
            ])
            ->toArray();

        return $messages;
    }

    /**
     * Search across all user sessions.
     */
    public function searchAllSessions(int $userId, string $query, int $limit = 50): array
    {
        $messages = ChatMessage::join('chat_sessions', 'chat_messages.chat_session_id', '=', 'chat_sessions.id')
            ->where('chat_sessions.user_id', $userId)
            ->where('chat_messages.content', 'LIKE', "%{$query}%")
            ->select('chat_messages.*', 'chat_sessions.title as session_title')
            ->orderBy('chat_messages.created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($msg) => [
                'id' => $msg->id,
                'session_id' => $msg->chat_session_id,
                'session_title' => $msg->session_title,
                'role' => $msg->role,
                'content' => $msg->content,
                'attachments' => $msg->attachments,
                'created_at' => $msg->created_at?->toIso8601String(),
            ])
            ->toArray();

        return $messages;
    }

    /**
     * Clear all chat sessions for user.
     */
    public function clearAllSessions(int $userId): int
    {
        $sessionIds = ChatSession::where('user_id', $userId)->pluck('id');
        ChatMessage::whereIn('chat_session_id', $sessionIds)->delete();
        
        return ChatSession::where('user_id', $userId)->delete();
    }

    /**
     * Export chat session data.
     */
    public function exportSession(int $userId, int $sessionId): ?array
    {
        $session = ChatSession::where('user_id', $userId)
            ->where('id', $sessionId)
            ->with('messages')
            ->first();

        if (!$session) {
            return null;
        }

        return [
            'session' => [
                'id' => $session->id,
                'title' => $session->title,
                'model_used' => $session->model_used,
                'created_at' => $session->created_at?->toIso8601String(),
            ],
            'messages' => $session->messages->map(fn($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
                'attachments' => $msg->attachments,
                'model_used' => $msg->model_used,
                'tokens_used' => $msg->tokens_used,
                'created_at' => $msg->created_at?->toIso8601String(),
            ])->toArray(),
            'exported_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get session settings.
     */
    public function getSessionSettings(int $userId, int $sessionId): ?array
    {
        $session = ChatSession::where('user_id', $userId)
            ->where('id', $sessionId)
            ->first(['id', 'title', 'model_used', 'system_prompt', 'temperature']);

        if (!$session) {
            return null;
        }

        return [
            'title' => $session->title,
            'model_used' => $session->model_used,
            'system_prompt' => $session->system_prompt,
            'temperature' => (float) $session->temperature,
        ];
    }

    /**
     * Truncate messages in a session from a specific index onwards.
     */
    public function truncateMessages(int $userId, int $sessionId, int $index): int|bool
    {
        $session = ChatSession::where('user_id', $userId)->where('id', $sessionId)->first();
        if (!$session) {
            return false;
        }

        $messages = ChatMessage::where('chat_session_id', $sessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        $toDelete = $messages->slice($index);
        $deletedCount = 0;
        foreach ($toDelete as $msg) {
            $msg->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }
}
