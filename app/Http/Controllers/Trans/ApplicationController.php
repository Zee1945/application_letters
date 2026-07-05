<?php

namespace App\Http\Controllers\Trans;

use App\Helpers\ViewHelper;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateSubmitReportJob;
use App\Models\Application;
use App\Services\ApplicationService;
use App\Services\FileManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ApplicationController extends Controller
{

    private $attachment_files = [];
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function preview(Request $request, $disk, $path)
    {
        $path = urldecode($path);
        $stream = Storage::disk($disk)->readStream($path);

        if (!$stream) {
            abort(404, 'File not found.');
        }

        $mime = Storage::disk($disk)->mimeType($path);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }
public function submitReport(Request $request, $application_id)
{
    $application = Application::find($application_id);

    // Validasi file
    $validation_result = $this->validateAttachments($request);
    if (!$validation_result['status']) {
        // Jika validasi gagal, kembalikan pesan error ke halaman sebelumnya
        return redirect()->back()->withErrors($validation_result['errors'])->withInput();
    }

    $report = $application->report()->first();

    // Pemetaan field upload => type attachment (slug).
    $attachmentMap = [
        'spj_file'             => 'spj-file',
        'minutes_file'         => 'minutes-file',
        'attendence_files'     => 'attendence-files',
        'documentation_photos' => 'document-photos',
    ];

    try {
        DB::beginTransaction();

        foreach ($attachmentMap as $field => $slug) {
            if (!$request->hasFile($field)) {
                continue;
            }

            $dir = FileManagementService::getPathStorage($application, 'report') . '/' . $slug;

            foreach ($request->file($field) as $file) {
                // Unggah fisik + buat record files sekaligus (tanpa round-trip).
                $stored = FileManagementService::storeFilesAlt($file, $dir, $file->getClientOriginalName(), $application, 'report');

                if ($stored['status']) {
                    $report->attachments()->create([
                        'file_id'               => $stored['data']->id,
                        'reference_id'          => null,
                        'application_report_id' => $report->id,
                        'type'                  => $slug,
                    ]);
                }
            }
        }

        DB::commit();
    } catch (\Throwable $th) {
        DB::rollBack();
        Log::error('Error saat submit laporan: ' . $th->getMessage(), ['application_id' => $application_id]);
        return redirect()->back()->with(false, 'Laporan gagal disubmit.');
    }

    if ($request->is_submit == '1') {
        ApplicationService::updateFlowApprovalStatus('submit-report', $application_id);
    }

    return redirect()->back()->with(true, 'Laporan berhasil disubmit.');
}

public function onAttachmentChanged($files,$type,$application_id)
    {
        $path = 'temp/report/'.$application_id.'/' . $type;
            // if (Storage::disk('minio')->exists($path)) {
            //         $files = Storage::disk('minio')->files($path); // Mendapatkan semua file dalam folder
            //         foreach ($files as $file) {
            //             Storage::disk('minio')->delete($file); // Hapus setiap file
            //         }
            // }

            $this->$attachment_files[$type]= [
                'file_path'=>$path,
                'type'=>$type,
                'application_report_id'=>$this->application->report->id,
            ];
            foreach ($files as $key => $file) {
                if ($file instanceof UploadedFile) {
                   $file->store($path, 'minio');
                }
            }
}
public function uploadSpeakerAttachment(Request $request,$application_id)
    {

        $paricipant_data = explode('_',$request->participant_data);
        $res = ApplicationService::storeDocSpeaker($request);
        if ($res['status']) {
            return redirect('/report/create/'.$application_id.'?step=2&search=1&selected_inf='.$paricipant_data[0])
                ->with('success', $res['message']);
        }
        return  redirect()->back()->with($res['status'], $res['message']);
   
}

public function submitRealization(Request $request, $application_id)
{
    try {
        $application = Application::find($application_id);

        if (!$application) {
            return redirect()->back()->with('error', 'Aplikasi tidak ditemukan');
        }

        $realizations = $request->input('realizations', []);

        // Process realization data
        foreach ($realizations as $draft_cost_id => $realization_data) {
            $draftCostBudget = \App\Models\ApplicationDraftCostBudget::find($draft_cost_id);

            if (!$draftCostBudget) {
                continue;
            }

            // Update realization data
            $draftCostBudget->volume_realization = $realization_data['volume_realization'] ?? 0;
            $draftCostBudget->unit_cost_realization = $realization_data['unit_cost_realization'] ?? 0;
            $draftCostBudget->realization = $realization_data['realization'] ?? 0;
            $draftCostBudget->save();

            // Handle file upload for bukti bayar
            if ($request->hasFile("realizations.{$draft_cost_id}.file_bukti")) {
                $file = $request->file("realizations.{$draft_cost_id}.file_bukti");
                $path = FileManagementService::getPathStorage($application,'report') . '/realization/' . $draft_cost_id;

                // Delete old files if exists
                if (Storage::disk('minio')->exists($path)) {
                    $files = Storage::disk('minio')->files($path);
                    foreach ($files as $oldFile) {
                        Storage::disk('minio')->delete($oldFile);
                    }
                }

                // Unggah fisik + simpan record files sekaligus.
                $stored = FileManagementService::storeFilesAlt($file, $path, $file->getClientOriginalName(), $application, 'realization');

                // Attach file to draft cost budget using pivot table
                if ($stored['status']) {
                    $draftCostBudget->files()->attach($stored['data']->id);
                }
            }
        }

        return redirect('/report/create/'.$application_id.'?step=3')
            ->with('success', 'Data realisasi berhasil disimpan');

    } catch (\Exception $e) {
        Log::error('Error submit realization: ' . $e->getMessage());
        return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan data realisasi: ' . $e->getMessage());
    }
}


public function validateAttachments(Request $request)
{
    // Ambil aturan validasi dari ApplicationService
    $upload_rules = ApplicationService::$upload_rules;

    // Siapkan array untuk aturan validasi
    $validation_rules = [];
    $validation_messages = [];

    foreach ($upload_rules as $key => $rule) {
        // Konversi MB ke KB untuk validasi Laravel (1 MB = 1024 KB)
        $max_size_kb = $rule['max_per_filesize'];
        
        
        // Aturan untuk setiap file
        $validation_rules["{$key}.*"] = [
            $rule['is_required'] ? 'required' : 'nullable', // Wajib jika is_required = true
            'file', // Harus berupa file
            'max:' . $max_size_kb, // Ukuran maksimal per file dalam KB
            'mimes:' . str_replace('.', '', $rule['accept']), // Format file yang diterima
        ];

        $filesize_text = ViewHelper::byteToText($rule['max_per_filesize']); 
        // Pesan error untuk setiap aturan (tampilkan dalam MB untuk user)
        $validation_messages["{$key}.*.required"] = "File {$key} wajib diunggah.";
        $validation_messages["{$key}.*.file"] = "File {$key} harus berupa file yang valid.";
        $validation_messages["{$key}.*.max"] = "Ukuran file {$key} tidak boleh lebih dari {$filesize_text}.";
        $validation_messages["{$key}.*.mimes"] = "Format file {$key} harus berupa: {$rule['accept']}.";
    }

    // Validasi request
    $validator = Validator::make($request->all(), $validation_rules, $validation_messages);

    if ($validator->fails()) {
        // Jika validasi gagal, kembalikan status dan pesan error
        return [
            'status' => false,
            'errors' => $validator->errors(),
        ];
    }

    // Jika validasi berhasil
    return ['status' => true];
}



}