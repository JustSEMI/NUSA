<?php

namespace App\Services;

use App\Models\AIStatus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIHeartbeatService
{
    /**
     * Check all AI models status.
     */
    public function checkAllModels(): array
    {
        $models = config('services.ai.available_models', []);
        $results = [];

        foreach ($models as $modelKey => $modelInfo) {
            $results[$modelKey] = $this->checkModel($modelKey);
        }

        return $results;
    }

    /**
     * Check single model status.
     */
    public function checkModel(string $modelKey): array
    {
        $startTime = microtime(true);
        
        try {
            $response = $this->sendHeartbeatRequest($modelKey);
            $responseTime = round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return $this->handleSuccessfulHeartbeat($modelKey, $responseTime);
            }

            throw new \Exception('API returned status: ' . $response->status());

        } catch (\Exception $e) {
            $responseTime = round((microtime(true) - $startTime) * 1000);
            return $this->handleFailedHeartbeat($modelKey, $responseTime, $e);
        }
    }

    /**
     * Send heartbeat request to AI API.
     */
    private function sendHeartbeatRequest(string $modelKey)
    {
        return Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . config('services.ai.api_key'),
        ])->timeout(config('services.ai.heartbeat_timeout', 30))->post(config('services.ai.base_url') . '/v1/chat/completions', [
            'model' => $modelKey,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Ping (heartbeat check)',
                ],
            ],
            'max_tokens' => 10,
        ]);
    }

    /**
     * Handle successful heartbeat check.
     */
    private function handleSuccessfulHeartbeat(string $modelKey, int $responseTime): array
    {
        AIStatus::updateOrCreate(
            ['model_name' => $modelKey],
            [
                'is_online' => true,
                'response_time_ms' => $responseTime,
                'last_check_at' => now(),
                'last_error' => null,
            ]
        );

        return [
            'model' => $modelKey,
            'status' => 'online',
            'response_time' => $responseTime,
            'error' => null,
        ];
    }

    /**
     * Handle failed heartbeat check.
     */
    private function handleFailedHeartbeat(string $modelKey, int $responseTime, \Exception $e): array
    {
        AIStatus::updateOrCreate(
            ['model_name' => $modelKey],
            [
                'is_online' => false,
                'response_time_ms' => $responseTime,
                'last_check_at' => now(),
                'last_error' => $e->getMessage(),
            ]
        );

        Log::warning('AI Heartbeat failed for model: ' . $modelKey, [
            'error' => $e->getMessage(),
        ]);

        return [
            'model' => $modelKey,
            'status' => 'offline',
            'response_time' => $responseTime,
            'error' => $e->getMessage(),
        ];
    }

    /**
     * Get all models status from database.
     */
    public function getAllStatuses(): array
    {
        $availableKeys = array_keys(config('services.ai.available_models', []));
        $statuses = AIStatus::whereIn('model_name', $availableKeys)->orderBy('model_name')->get();

        return $statuses->map(function ($status) {
            return [
                'model_name' => $status->model_name,
                'display_name' => config('services.ai.available_models.' . $status->model_name . '.name', $status->model_name),
                'is_online' => $status->is_online,
                'response_time_ms' => $status->response_time_ms,
                'last_check_at' => $status->last_check_at?->diffForHumans(),
                'last_error' => $status->last_error,
            ];
        })->toArray();
    }

    /**
     * Initialize status for all models (first time setup).
     */
    public function initializeStatuses(): void
    {
        $models = config('services.ai.available_models', []);

        foreach ($models as $modelKey => $modelInfo) {
            AIStatus::firstOrCreate(
                ['model_name' => $modelKey],
                [
                    'is_online' => true,
                    'response_time_ms' => null,
                    'last_check_at' => null,
                    'last_error' => null,
                ]
            );
        }
    }
}
