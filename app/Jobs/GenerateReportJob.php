<?php

namespace App\Jobs;

use App\Services\ApplicationService;
use App\Services\TemplateProcessorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    protected $application;
    public function __construct($application)
    {

         $this->application = $application;
    
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $app_file = $this->application->applicationFiles()->findCode('laporan_kegiatan')->first();
        TemplateProcessorService::generateDocumentToPDF($this->application, 'laporan_kegiatan',$app_file);
         ApplicationService::storeAttachmentToDetails($this->application);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job gagal: ' . $exception->getMessage());
    }
}
