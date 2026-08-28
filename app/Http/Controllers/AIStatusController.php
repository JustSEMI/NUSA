<?php

namespace App\Http\Controllers;

use App\Services\AIHeartbeatService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIStatusController extends Controller
{
    use ApiResponse;

    protected AIHeartbeatService $heartbeatService;

    public function __construct(AIHeartbeatService $heartbeatService)
    {
        $this->heartbeatService = $heartbeatService;
    }

    /**
     * Get current status of all AI models.
     */
    public function index(): JsonResponse
    {
        $statuses = $this->heartbeatService->getAllStatuses();

        return $this->successResponse(['statuses' => array_values($statuses)]);
    }

    /**
     * Manual heartbeat check (admin only).
     * 
     * @param string|null $model Optional model name to check single model
     */
    public function check(?string $model = null): JsonResponse
    {
        $results = $model 
            ? $this->heartbeatService->checkModel($model)
            : $this->heartbeatService->checkAllModels();

        return $this->successResponse($results);
    }
}
