<?php

namespace App\Jobs;

use App\Services\ApplicationService;
use App\Services\TemplateProcessorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateSubmitReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $requestFiles;
    protected $application_id;
    public function __construct($request ,$application_id)
    {

         $this->requestFiles = $request;
         $this->application_id = $application_id;
    
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        ApplicationService::submitReport($this->requestFiles,$this->application_id);
        ApplicationService::updateFlowApprovalStatus('submit-report', $this->application_id);

    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job gagal: ' . $exception->getMessage());
    }
}
