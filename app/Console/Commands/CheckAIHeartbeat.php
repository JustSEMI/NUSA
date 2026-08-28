<?php

namespace App\Console\Commands;

use App\Services\AIHeartbeatService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('ai:heartbeat')]
#[Description('Check all AI models status')]
class CheckAIHeartbeat extends Command
{
    protected AIHeartbeatService $heartbeatService;

    public function __construct(AIHeartbeatService $heartbeatService)
    {
        parent::__construct();
        $this->heartbeatService = $heartbeatService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking AI models status...');
        
        $results = $this->heartbeatService->checkAllModels();
        
        foreach ($results as $modelKey => $result) {
            $status = $result['status'] === 'online' ? '✓' : '✗';
            $time = $result['response_time'] ?? 'N/A';
            $error = $result['error'] ?? '';
            
            $this->line(sprintf(
                '%s %s: %s (%s ms)%s',
                $status,
                $modelKey,
                $result['status'],
                $time,
                $error ? " - Error: {$error}" : ''
            ));
        }
        
        return Command::SUCCESS;
    }
}
