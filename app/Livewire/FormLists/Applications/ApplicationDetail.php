<?php

namespace App\Livewire\FormLists\Applications;

use App\Models\Application;
use App\Models\Department;
use App\Services\AuthService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ApplicationDetail extends Component
{
    public $application_id = null;
    public function mount($application_id){
        $this->application_id = $application_id;


    }

    public function render()
    {
        $app = Application::find($this->application_id);
        $application_files = $app->applicationFiles()->with('fileType')->orderBy('order','asc')->get();
        return view('livewire.form-lists.applications.application-detail', compact('app','application_files'))
            ->extends('layouts.main');
    }



    public function downloadFile($path,$filename,$is_upload =0){

        $newPath = $is_upload == 1?$path:$path.'/'.$filename;
        $url = Storage::disk('minio')->temporaryUrl($newPath, now()->addHours(1), [
                'ResponseContentType' => 'application/octet-stream',
                'ResponseContentDisposition' => 'attachment; '. $filename,
                'filename' => $filename,
            ]);
            return redirect()->to($url);
    }


    public function mappingFile(){

    }
}
