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
        return view('livewire.form-lists.applications.application-detail', compact('app'))
            ->extends('layouts.main');
    }



    public function downloadFile($path,$filename){
        $url = Storage::disk('minio')->temporaryUrl($path.'/'.$filename, now()->addHours(1), [
                'ResponseContentType' => 'application/octet-stream',
                'ResponseContentDisposition' => 'attachment; '. $filename,
                'filename' => $filename,
            ]);
            return redirect()->to($url);
    }
}
