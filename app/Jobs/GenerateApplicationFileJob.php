<?php

namespace App\Jobs;

use App\Services\TemplateProcessorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateApplicationFileJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */

     protected $application;
    public function __construct($application)
    {
        //
        $this->application = $application;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //

        TemplateProcessorService::generateApplicationDocument($this->application);
    }

    public function failed(\Throwable $exception): void
    {
        // Ini dipanggil kalau job gagal total
        Log::error('Job gagal: ' . $exception->getMessage());
    }
}
