<?php

namespace App\Http\Controllers\Trans;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateSubmitReportJob;
use App\Models\Application;
use App\Services\ApplicationService;
use App\Services\FileManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
    $data = [];
      $application = Application::find($application_id);
     $report = $application->report()->first();
            if ($request->hasFile('spj_file')) {
                $slug = 'spj-file';
                $spjFilePath = $application_id . '/'.$slug;
                $data[]= [
                        'dir_path'=>$spjFilePath,
                        'type'=>$slug,
                        'file_path'=>$spjFilePath,
                        'application_report_id'=>$report->id,
                    ];
            foreach ($request->file('spj_file') as $spjFile) {
                $spjFile->storeAs($spjFilePath.'/', $spjFile->getClientOriginalName(), 'minio');
            }
        }

        // Proses file Minutes
        if ($request->hasFile('minutes_file')) {
              $slug = 'minutes-file';
                $minutesFilePath = $application_id . '/'.$slug;

               $data[]= [
                    'dir_path'=>$minutesFilePath,
                    'type'=>$slug,
                    'file_path'=>$minutesFilePath,
                    'application_report_id'=>$report->id,
                ];
                // dd($data);
            foreach ($request->file('minutes_file') as $minutesFile) {
                // dd($minutesFile->getClientOriginalName());
                $minutesFile->storeAs($minutesFilePath.'/', $minutesFile->getClientOriginalName(), 'minio');
            }
        }

        // // Proses file Absensi
        if ($request->hasFile(key: 'attendence_files')) {
            $slug = 'attendence-files';
             $attendenceFilePath = $application_id .'/'.$slug;
            $data[]= [
                    'dir_path'=>$attendenceFilePath,
                    'type'=>$slug,
                    'file_path'=>$attendenceFilePath,
                    'application_report_id'=>$report->id,
                ];
            foreach ($request->file('attendence_files') as $attendenceFile) {
                $attendenceFile->storeAs($attendenceFilePath.'/',$attendenceFile->getClientOriginalName(), 'minio');
            }
        }

        // Proses Foto Dokumentasi
        if ($request->hasFile('documentation_photos')) {
            $slug = 'document-photos';
                $photoPath = $application_id . '/'.$slug;
                $data[]= [
                    'dir_path'=>$photoPath,
                    'type'=>$slug,
                    'file_path'=>$photoPath,
                    'application_report_id'=>$report->id,
                ];

            foreach ($request->file('documentation_photos') as $photo) {
                $photo->storeAs($photoPath.'/', $photo->getClientOriginalName(), 'minio');
           
            }
        }
    $res =  ApplicationService::submitReport($data,$application,$report);
    // GenerateSubmitReportJob::dispatch($request,$application_id);
    if ($res['status']) {
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
}