<?php

namespace App\Services;

use Illuminate\Support\ServiceProvider;
use PhpOffice\PhpWord\TemplateProcessor;


class TemplateProcessorService
{
    /**
     * Register services.
     *
     * @return void
     */
    public static function generateWord()
    {
            // $templatePath = storage_path('app/templates/template.docx');;
            $templatePath = public_path('referensi/dummy_inject_word.docx');
            // dd($templatePath);
            $savePath = public_path('referensi/generated.docx');

            $templateProcessor = new TemplateProcessor($templatePath);
            // Inject variabel
            $templateProcessor->setValue('nama_user', 'Zul Fachrie');

            $templateProcessor->saveAs($savePath);
            return response()->download($savePath);
    }
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
