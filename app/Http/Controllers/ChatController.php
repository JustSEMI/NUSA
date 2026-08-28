<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use App\Services\ChatSessionService;
use App\Services\AiApiService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    use ApiResponse;

    protected ChatSessionService $sessionService;
    protected AiApiService $apiService;
    protected \App\Services\FileAttachmentService $attachmentService;

    public function __construct(
        ChatSessionService $sessionService,
        AiApiService $apiService,
        \App\Services\FileAttachmentService $attachmentService
    ) {
        $this->sessionService = $sessionService;
        $this->apiService = $apiService;
        $this->attachmentService = $attachmentService;
    }

    /**
     * Tampilkan halaman chatbot NUSA.
     */
    public function index(Request $request): View
    {
        $history = collect($this->sessionService->getUserSessions($request->user()->id));
        return view('chat.index', ['initialHistory' => $history]);
    }

    /**
     * Upload attachment file (gambar atau dokumen, multipart atau base64 paste).
     */
    public function uploadAttachment(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            $uploadedAttachments = [];

            // Check for base64 upload (e.g. pasted clipboard image)
            if ($request->filled('base64_data')) {
                $request->validate([
                    'base64_data' => ['required', 'string'],
                    'custom_name' => ['nullable', 'string', 'max:255'],
                ]);

                $attachment = $this->attachmentService->processBase64File(
                    $request->input('base64_data'),
                    $userId,
                    $request->input('custom_name')
                );
                $uploadedAttachments[] = $attachment;
            }

            // Check for multipart file upload (single file or multiple files)
            if ($request->hasFile('file')) {
                $request->validate([
                    'file' => ['required', 'file', 'max:25600'], // 25MB max
                ]);
                $attachment = $this->attachmentService->processUploadedFile($request->file('file'), $userId);
                $uploadedAttachments[] = $attachment;
            } elseif ($request->hasFile('files')) {
                $request->validate([
                    'files' => ['required', 'array', 'max:10'],
                    'files.*' => ['file', 'max:25600'],
                ]);
                foreach ($request->file('files') as $file) {
                    $uploadedAttachments[] = $this->attachmentService->processUploadedFile($file, $userId);
                }
            }

            if (empty($uploadedAttachments)) {
                return $this->errorResponse('Tidak ada file yang diunggah.', null, 400);
            }

            return $this->successResponse([
                'attachments' => $uploadedAttachments,
            ], 'File berhasil diunggah.');
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors());
        } catch (\Exception $e) {
            Log::error('Upload attachment failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverErrorResponse('Gagal mengunggah file: ' . $e->getMessage());
        }
    }

    /**
     * Hapus attachment file sebelum dikirim (opsional).
     */
    public function deleteAttachment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string'],
        ]);

        $userId = $request->user()->id;
        if (!str_starts_with($validated['path'], "attachments/{$userId}/")) {
            return $this->errorResponse('Anda tidak memiliki akses untuk menghapus file ini.', null, 403);
        }

        $this->attachmentService->deleteAttachmentFile($validated['path']);
        return $this->successResponse(null, 'File berhasil dihapus.');
    }

    /**
     * Handle chat message dan kirim ke AI API.
     */
    public function chat(Request $request): JsonResponse
    {
        try {
            $validated = $this->validateChatRequest($request);
            $userId = $request->user()->id;
            $messageText = $validated['message'] ?? '';
            $attachments = $validated['attachments'] ?? [];
            
            Log::info('Chat request received', [
                'user_id' => $userId,
                'message_length' => strlen($messageText),
                'attachments_count' => count($attachments),
                'model' => $validated['model'] ?? 'default',
            ]);

            $model = $this->apiService->getSelectedModel($validated['model'] ?? null);
            
            $initialTitle = !empty($messageText)
                ? $messageText
                : (count($attachments) > 0 ? $attachments[0]['name'] : 'Pesan Baru');

            $sessionId = $this->sessionService->getOrCreateSession(
                $userId,
                $validated['session_id'] ?? null,
                $initialTitle,
                $model
            );
            
            $this->sessionService->saveUserMessage($sessionId, $messageText, $model, $attachments);
            
            return $this->processAiResponse($messageText, $model, $sessionId);
            
        } catch (ValidationException $e) {
            Log::error('Chat validation failed', [
                'errors' => $e->errors(),
                'input' => $request->all(),
            ]);
            return $this->validationErrorResponse($e->errors());
            
        } catch (\Exception $e) {
            Log::error('Chat error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverErrorResponse('Gagal memproses permintaan. Silakan coba lagi.');
        }
    }

    /**
     * Validate chat request.
     */
    private function validateChatRequest(Request $request): array
    {
        $availableModels = $this->apiService->getAvailableModels();
        
        return $request->validate([
            'message' => ['nullable', 'string', 'max:50000', 'required_without:attachments'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*.id' => ['required', 'string'],
            'attachments.*.name' => ['required', 'string'],
            'attachments.*.path' => ['required', 'string'],
            'attachments.*.url' => ['required', 'string'],
            'attachments.*.mime_type' => ['nullable', 'string'],
            'attachments.*.is_image' => ['nullable', 'boolean'],
            'attachments.*.extracted_text' => ['nullable', 'string'],
            'model' => ['nullable', 'string', 'in:' . implode(',', $availableModels)],
            'session_id' => ['nullable', 'exists:chat_sessions,id'],
        ], [
            'message.required_without' => 'Pesan atau file lampiran tidak boleh kosong.',
            'message.max' => 'Pesan terlalu panjang.',
            'model.in' => 'Model AI tidak valid.',
            'session_id.exists' => 'Sesi chat tidak ditemukan.',
        ]);
    }



    /**
     * Process AI API response.
     */
    private function processAiResponse(string $message, string $model, int $sessionId): JsonResponse
    {
        $messagesHistory = $this->sessionService->getConversationHistory($sessionId);
        
        // Fallback jika history kosong
        if (empty($messagesHistory)) {
            $messagesHistory[] = ['role' => 'user', 'content' => $message];
        }

        $apiResult = $this->apiService->sendMessage($messagesHistory, $model);

        if ($apiResult['success']) {
            return $this->handleSuccess($apiResult, $sessionId, $model);
        }

        return $this->handleApiFailure($apiResult);
    }

    /**
     * Handle successful AI response.
     */
    private function handleSuccess(array $apiResult, int $sessionId, string $model): JsonResponse
    {
        $this->sessionService->saveAssistantMessage(
            $sessionId,
            $apiResult['reply'],
            $model,
            $apiResult['usage'] ?? []
        );

        return $this->successResponse([
            'reply' => $apiResult['reply'],
            'session_id' => $sessionId,
            'usage' => $apiResult['usage'] ?? [],
        ]);
    }

    /**
     * Handle failed AI response.
     */
    private function handleApiFailure(array $apiResult): JsonResponse
    {
        Log::warning('AI API failed', [
            'error' => $apiResult['error'] ?? 'Unknown error',
            'status_code' => $apiResult['status_code'] ?? 500,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'AI Error',
            'error' => $apiResult['error'] ?? 'Terjadi kesalahan pada server AI',
            'status_code' => $apiResult['status_code'] ?? 500,
        ], 500);
    }

    /**
     * Get user's chat history (list of sessions).
     */
    public function history(Request $request): JsonResponse
    {
        $sessions = $this->sessionService->getUserSessions($request->user()->id);

        return $this->successResponse(['sessions' => $sessions]);
    }

    /**
     * Get specific chat session with messages.
     */
    public function showChat(Request $request, int $id): JsonResponse
    {
        $result = $this->sessionService->getSessionWithMessages($request->user()->id, $id);

        if (!$result) {
            return $this->notFoundResponse('Sesi chat tidak ditemukan.');
        }

        return $this->successResponse($result);
    }

    /**
     * Create new chat session.
     */
    public function createSession(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'model_used' => ['nullable', 'string'],
        ]);

        $sessionId = $this->sessionService->createEmptySession(
            $request->user()->id,
            $validated['title'],
            $validated['model_used'] ?? null
        );

        return $this->successResponse(['session_id' => $sessionId]);
    }

    /**
     * Delete chat session.
     */
    public function deleteSession(Request $request, int $id): JsonResponse
    {
        $deleted = $this->sessionService->deleteSession($request->user()->id, $id);

        if (!$deleted) {
            return $this->notFoundResponse('Sesi chat tidak ditemukan.');
        }

        return $this->successResponse(null, 'Sesi chat berhasil dihapus.');
    }

    /**
     * Rename chat session.
     */
    public function renameSession(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $updated = $this->sessionService->renameSession(
            $request->user()->id,
            $id,
            $validated['title']
        );

        if (!$updated) {
            return $this->notFoundResponse('Sesi chat tidak ditemukan.');
        }

        return $this->successResponse(null, 'Judul chat berhasil diubah.');
    }

    /**
     * Update session settings (system prompt, temperature).
     */
    public function updateSessionSettings(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'system_prompt' => ['nullable', 'string', 'max:5000'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
        ]);

        $updated = $this->sessionService->updateSessionSettings(
            $request->user()->id,
            $id,
            $validated
        );

        if (!$updated) {
            return $this->notFoundResponse('Sesi chat tidak ditemukan.');
        }

        return $this->successResponse(null, 'Pengaturan chat berhasil diubah.');
    }

    /**
     * Get session settings.
     */
    public function getSessionSettings(Request $request, int $id): JsonResponse
    {
        $settings = $this->sessionService->getSessionSettings($request->user()->id, $id);

        if (!$settings) {
            return $this->notFoundResponse('Sesi chat tidak ditemukan.');
        }

        return $this->successResponse($settings);
    }

    /**
     * Delete individual message.
     */
    public function deleteMessage(Request $request, int $sessionId, int $messageId): JsonResponse
    {
        $deleted = $this->sessionService->deleteMessage(
            $request->user()->id,
            $sessionId,
            $messageId
        );

        if (!$deleted) {
            return $this->notFoundResponse('Pesan tidak ditemukan.');
        }

        return $this->successResponse(null, 'Pesan berhasil dihapus.');
    }

    /**
     * Search in chat session.
     */
    public function searchInSession(Request $request, int $sessionId): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
        ]);

        $results = $this->sessionService->searchInSession(
            $request->user()->id,
            $sessionId,
            $validated['q']
        );

        return $this->successResponse($results);
    }

    /**
     * Search across all sessions.
     */
    public function searchAllSessions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $results = $this->sessionService->searchAllSessions(
            $request->user()->id,
            $validated['q'],
            $validated['limit'] ?? 50
        );

        return $this->successResponse($results);
    }

    /**
     * Clear all chat sessions.
     */
    public function clearAllSessions(Request $request): JsonResponse
    {
        $count = $this->sessionService->clearAllSessions($request->user()->id);

        return $this->successResponse(
            ['deleted_count' => $count],
            "Semua {$count} chat berhasil dihapus."
        );
    }

    /**
     * Export chat session.
     */
    public function exportSession(Request $request, int $id): JsonResponse
    {
        $data = $this->sessionService->exportSession($request->user()->id, $id);

        if (!$data) {
            return $this->notFoundResponse('Sesi chat tidak ditemukan.');
        }

        return $this->successResponse($data);
    }

    /**
     * Truncate messages in a session from a specific index onwards.
     */
    public function truncateMessages(Request $request, int $id, int $index): JsonResponse
    {
        $deletedCount = $this->sessionService->truncateMessages($request->user()->id, $id, $index);

        if ($deletedCount === false) {
            return $this->notFoundResponse('Sesi chat tidak ditemukan.');
        }

        return $this->successResponse(['deleted_count' => $deletedCount], 'Berhasil menghapus pesan lama.');
    }
}
