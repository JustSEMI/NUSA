<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiApiService
{
    /**
     * Send message to AI API with conversation context.
     */
    public function sendMessage(array $messages, string $model, ?string $apiKey = null): array
    {
        $apiKey = $apiKey ?? config('services.ai.api_key');
        $baseUrl = config('services.ai.base_url');
        $maxTokens = config('services.ai.max_tokens', 8192);
        $timeout = config('services.ai.timeout', 60);

        Log::info('Making AI API request', [
            'model' => $model,
            'messages_count' => count($messages),
            'max_tokens' => $maxTokens,
        ]);

        $response = $this->makeApiRequest($baseUrl, $apiKey, $model, $messages, $maxTokens, $timeout);

        // Try backup API key if primary fails
        if (!$response->successful()) {
            $backupApiKey = config('services.ai.api_key2');
            if ($backupApiKey) {
                Log::warning('Primary AI API failed, trying backup key...');
                $response = $this->makeApiRequest($baseUrl, $backupApiKey, $model, $messages, $maxTokens, $timeout);
            }
        }

        return $this->parseResponse($response);
    }

    /**
     * Make API request to AI provider.
     */
    private function makeApiRequest(
        string $baseUrl,
        string $apiKey,
        string $model,
        array $messages,
        int $maxTokens,
        int $timeout
    ) {
        return Http::withOptions(['verify' => true])
            ->timeout($timeout)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])
            ->post($baseUrl . '/v1/messages', [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => $maxTokens,
            ]);
    }

    /**
     * Parse AI API response.
     */
    private function parseResponse($response): array
    {
        $data = $response->json();
        $statusCode = $response->status();

        Log::info('AI API Response', [
            'status' => $statusCode,
            'has_content' => isset($data['content']),
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'reply' => $this->extractReply($data),
                'usage' => $this->extractUsage($data),
                'raw_data' => $data,
            ];
        }

        return [
            'success' => false,
            'error' => $this->extractError($response, $data),
            'status_code' => $statusCode,
            'raw_body' => $response->body(),
        ];
    }

    /**
     * Extract reply from Anthropic-format response.
     */
    private function extractReply(array $data): string
    {
        if (isset($data['content']) && is_array($data['content']) && count($data['content']) > 0) {
            $reply = $data['content'][0]['text'] ?? null;
            if ($reply) {
                return $reply;
            }
        }

        Log::warning('No content in AI API response', ['data' => $data]);
        return 'Maaf, saya tidak bisa memproses permintaan Anda.';
    }

    /**
     * Extract token usage from response.
     */
    private function extractUsage(array $data): array
    {
        if (!isset($data['usage'])) {
            return [];
        }

        return [
            'input_tokens' => $data['usage']['input_tokens'] ?? 0,
            'output_tokens' => $data['usage']['output_tokens'] ?? 0,
        ];
    }

    /**
     * Extract error message from failed response.
     */
    private function extractError($response, array $data): string
    {
        if (isset($data['error'])) {
            if (is_string($data['error'])) {
                return $data['error'];
            }
            if (isset($data['error']['message'])) {
                return $data['error']['message'];
            }
        }

        if (isset($data['message'])) {
            return $data['message'];
        }

        return $response->body() ?: 'Terjadi kesalahan pada server AI';
    }

    /**
     * Get list of available AI models from config.
     */
    public function getAvailableModels(): array
    {
        $models = array_keys(config('services.ai.available_models', []));
        
        if (empty($models)) {
            Log::error('No available models configured');
            return [config('services.ai.model', 'qwen-3.5-flash')];
        }
        
        return $models;
    }

    /**
     * Get selected model from request or config default.
     */
    public function getSelectedModel(?string $requestedModel = null): string
    {
        $model = $requestedModel ?? config('services.ai.model', 'qwen-3.5-flash');
        
        Log::info('Model selected', ['model' => $model]);
        
        return $model;
    }

}
