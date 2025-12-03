<?php

namespace App\Http\Controllers\Trans;

use App\Helpers\ViewHelper;
use App\Http\Controllers\Controller;
use App\Jobs\GenerateSubmitReportJob;
use App\Models\Application;
use App\Services\ApplicationService;
use App\Services\FileManagementService;
use Illuminate\Http\Request;
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
    $upload_rules = ApplicationService::$upload_rules;
    // Validasi request


   // Validasi file
    // $validation_result = $this->validateAttachments($request);

    // if (!$validation_result['status']) {
    //     // Jika validasi gagal, kembalikan pesan error ke halaman sebelumnya
    //     return redirect()->back()->withErrors($validation_result['errors'])->withInput();
    // }

    // Proses file setelah validasi berhasil
    $data = [];
    $application = Application::find($application_id);
    $report = $application->report()->first();

    // Proses file sesuai dengan aturan
    foreach ($upload_rules as $key => $rule) {
        if ($request->hasFile($key)) {
            $slug = $rule['name'];
            $filePath = $application_id . '/' . $slug;

            $data[] = [
                'dir_path' => $filePath,
                'type' => $slug,
                'file_path' => $filePath,
                'application_report_id' => $report->id,
            ];

            foreach ($request->file($key) as $file) {
                $file->storeAs($filePath . '/', $file->getClientOriginalName(), 'minio');
            }
        }
    }

    // Submit laporan
    $res = ApplicationService::submitReport($data, $application, $report);

    if ($res['status'] && $request->is_submit == '1') {
        ApplicationService::updateFlowApprovalStatus('submit-report', $application_id);
    }

    return redirect()->back()->with($res['status'], $res['message']);
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

public function validateAttachments(Request $request)
{
    // Ambil aturan validasi dari ApplicationService
    $upload_rules = ApplicationService::$upload_rules;

    // Siapkan array untuk aturan validasi
    $validation_rules = [];
    $validation_messages = [];

    foreach ($upload_rules as $key => $rule) {
        // Aturan untuk setiap file
        $validation_rules["{$key}.*"] = [
            $rule['is_required'] ? 'required' : 'nullable', // Wajib jika is_required = true
            'file', // Harus berupa file
            'max:' . $rule['max_per_filesize'], // Ukuran maksimal per file
            'mimes:' . str_replace('.', '', $rule['accept']), // Format file yang diterima
        ];
        $conversionFilesize = ViewHelper::byteToText($rule['max_per_filesize']);
        // Pesan error untuk setiap aturan
        $validation_messages["{$key}.*.required"] = "File {$key} wajib diunggah.";
        $validation_messages["{$key}.*.file"] = "File {$key} harus berupa file yang valid.";
        $validation_messages["{$key}.*.max"] = "Ukuran file {$key} tidak boleh lebih dari {$conversionFilesize} KB.";
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