<?php

namespace App\Jobs;

use App\Models\ApplicationDetail;
use App\Models\ApplicationFile;
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
    protected $is_regenerate;
    public function __construct($application,$is_regenerate = false)
    {

         $this->application = $application;
         $this->is_regenerate = $is_regenerate;
    
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         $app_file = $this->application->applicationFiles()->findCode('laporan_kegiatan')->first();
         TemplateProcessorService::generateDocumentToPDF($this->application, 'laporan_kegiatan',$app_file);
         ApplicationService::storeAttachmentToDetails($this->application,$this->is_regenerate);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job gagal: ' . $exception->getMessage());
    }
}
