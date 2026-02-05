<?php

namespace App\Livewire\FormLists\Applications;

use App\Models\Application;
use App\Models\Department;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\AuthService;
use App\Services\MasterManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class ApplicationDetail extends Component
{
    public $application_id = null;

    public $editable = [
        "activity_name"=>[
            "is_edit"=>false,
            "key"=>"activity_name",
            "value"=>"",
            "label"=>"Nama Kegiatan"
        ],
        "funding_source"=>[
            "is_edit"=>false,
            "key"=>"funding_source",
            "value"=>null,
            "label"=>"Sumber Pendanaan"
        ],
    ];

    public $is_editable_opened = false;

    public $modal_confirm =false;
    public function mount($application_id){
        $this->application_id = $application_id;

           $app = Application::find($this->application_id);
            $this->editable['activity_name']['value'] = $app->activity_name;
            $this->editable['funding_source']['value'] = $app->funding_source;


    }

    public function render()
    {
        $app = Application::find($this->application_id);
        $user_approvers = $app->userApprovals;
        $application_files = $app->applicationFiles()->with('fileType')->orderBy('order','asc')->get();
        return view('livewire.form-lists.applications.application-detail', compact('app','application_files','user_approvers'))
            ->extends('layouts.main');
    }



    public function downloadFile($path,$filename,$is_upload =0){

        $newPath = $is_upload == 1?$path:$path.'/'.$filename;
        // $url = Storage::disk('minio')->temporaryUrl($newPath, now()->addHours(1), [
        //         'ResponseContentType' => 'application/octet-stream',
        //         'ResponseContentDisposition' => 'attachment; '. $filename,
        //         'filename' => $filename,
        //     ]);

            
            // dd('sini');

            // if (app()->runningInConsole() === false) {
            //     // replace only for browser access
            //     $url = str_replace('http://minio:9000', 'http://minio-api.local', $url);
            // }
            // return redirect()->to($url);

             $stream = Storage::disk('minio')->readStream($newPath);

    if (!$stream) {
        abort(404, 'File not found.');
    }

    return response()->streamDownload(function () use ($stream) {
        fpassthru($stream);
    }, $filename, [
        'Content-Type' => 'application/octet-stream',
        'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ]);

    }


    public function destroyRecursive($id){
        $res = ApplicationService::destroyRecursive($id);
         if (!$res['status']) {
            return redirect()->back()->withInput()->withErrors(['error' => $res['message']]);
        }
        return redirect()->route('applications.index')->with('success', $res['message']);
    }
    public function mappingFile(){

    }

    public function enableEdit($field)
    {
        $this->is_editable_opened =true;
        $this->editable[$field]['is_edit'] = true;
    }

public function cancelEdit($field)
{
    $app = Application::find($this->application_id);
    $this->editable[$field]['value'] = $app->$field; // Kembalikan nilai dari database
    $this->editable[$field]['is_edit'] = false;
    $this->is_editable_opened =false;

}

public function openModalConfirm(){
    $this->dispatch('open-modal-confirm-submit');
}

public function closeModalConfirm(){
    $this->dispatch('close-modal-confirm-submit');

}

public function submitEdit()
{
    DB::beginTransaction(); // Memulai transaksi database
    try {
        $field = array_filter($this->editable, function ($item) {
            return $item['is_edit'] == true;
        });
        $field = array_values($field)[0]['key'];

        // Temukan aplikasi berdasarkan ID
        $app = Application::find($this->application_id);
        $old_val = $app->$field;

        // Perbarui field yang sedang diedit
        $app->$field = $this->editable[$field]['value'];
        $app->save();

        // Commit transaksi jika berhasil
        DB::commit();
        $message_toast = "Berhasil mengubah " . $this->editable[$field]["label"];
        $message_log = $this->editable[$field]["label"].' Dari "'.$old_val.'" Menjadi "'. $app->$field;

        MasterManagementService::storeLogActivity('edit-detail',$app->id,$message_log);

        // Tampilkan pesan sukses
        session()->flash('success', ucfirst($this->editable[$field]['label']) . ' berhasil diperbarui.');
        $this->editable[$field]['is_edit'] = false;
        $this->is_editable_opened =false;



        // Redirect dengan pesan sukses
        return redirect()->route('applications.detail', ['application_id' => $this->application_id])->with('success', $message_toast);
    } catch (\Exception $e) {
        // Rollback transaksi jika terjadi kesalahan
        DB::rollBack();

        // Tangani kesalahan dan tampilkan pesan error
        session()->flash('error', 'Terjadi kesalahan');
    }
}
}
