<?php

namespace App\Jobs;

use App\Models\ApplicationDetail;
use App\Models\ApplicationFile;
use App\Services\ApplicationService;
use App\Services\TemplateProcessorService;
use App\Services\PdfMergerService;
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

    /**
     * Batas waktu eksekusi job (detik). Diperpanjang karena proses merge
     * melibatkan download dari MinIO, konversi OnlyOffice, dan merge PDF.
     */
    public $timeout = 300;

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
        
         $app_file->refresh();
         // Hanya merge jika LPJ berhasil di-generate (status_ready = 3).
         // Pakai == karena status_ready bisa berupa string dari DB (tidak di-cast).
         if($app_file->status_ready == 3){
            // Merge dibungkus try-catch terpisah: kegagalan merge TIDAK boleh
            // menjatuhkan job ini, karena LPJ utama sudah berhasil di-generate.
            // Status kegagalan merge sendiri sudah dicatat di merge_status oleh service.
            try {
                $merger = new PdfMergerService();
                $merger->mergeLpjWithAttachments($this->application);
            } catch (\Throwable $e) {
                Log::error('Merge LPJ gagal (LPJ utama tetap tersimpan): ' . $e->getMessage(), [
                    'application_id' => $this->application->id,
                ]);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job gagal: ' . $exception->getMessage());
    }
}
