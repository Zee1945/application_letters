<?php

namespace App\Livewire\FormLists\Reports;

use App\Imports\ApplicationsImport;
use App\Livewire\AbstractComponent;
use App\Models\Application;
use App\Models\Files;
use App\Services\ApplicationService;
use App\Services\AuthService;
use App\Services\FileManagementService;
use App\Services\TemplateProcessorService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ReportCreate extends AbstractComponent
{
    public $application_id = null;

    #[Url(except: '')]
    public $q_inf = '';

    #[Url(except: '')]
    public $selected_inf = '';

    public $open_modal_confirm = null;
    public $notes = null;
    // step 1
    public $introduction;
    public $activity_description;
    public $obstacles;
    public $conclusion;
    public $recommendations;
    public $speaker_material;
    public $background;

    public $attachment_files = [];

    public $row_attachments = [];



    // Step 2
    public $speakers_info = [];
    public $rundowns = [];
    public $draft_costs = [];
    public $spj_file = [];
    public $old_spj_file = []; 
    public $minutes_file = [];
    public $old_minutes_file = []; 
    public $documentation_photos = [];
    public $old_documentation_photos = [];
    public $attendence_files = [];
    public $old_attendence_files = [];
    public $excel_participant = null;

    public $letter_numbers = [];

    public $total_participants = 0;
    public $total_participants_not_present = 0;
    public $total_participants_present = 0;

    public $temp_attachments = [];


    // public function __set($name, $value)
    // {
    //     if (property_exists($this, $name)) {
    //         $this->$name = $value;
    //     }
    // }

    public function mount($application_id = null)
    {

        $this->application = Application::find($application_id);

        // $this->step = $this->application->draft_step_saved;
        $this->application_id = $application_id;
        // dd($this->application->detail->general_description);
        // $this->background = $this->application->detail->general_description;
        $this->draft_costs = $this->application->draftCostBudgets->toArray();

        $keysToKeep = ['id', 'letter_label', 'letter_name', 'type_field', 'letter_number'];
        $this->letter_numbers = array_map(function ($item) use ($keysToKeep) {
            return array_intersect_key($item, array_flip($keysToKeep));
        }, $this->application->letterNumbers->toArray());

        if ($this->application->report) {
            $this->loadData();
        }
        $this->row_attachments = ApplicationService::$upload_rules;

        $this->setupElementUpload();

        $this->permissionApplication($application_id);

        //  $this->dispatch('transfer-rundowns', [...$this->rundowns]);
        //  $this->dispatch('transfer-draft-costs', [...$this->draft_costs]);
    }
    public function render()
    {

        // if (!empty($this->spj_file)) {
        //     dd($this->spj_file);
        // }

        return view('livewire.form-lists.reports.report-create')->extends('layouts.main');
    }

    #[On('transfer-speakerInformation')]
    public function receiveSpeakerInformation($speaker_info){
        $this->speakers_info = $speaker_info;

        // if (count($this->speakers_info)>0) {
        //     # code...
        //     dd($this->speakers_info);
        // }
        
    }

    #[On('transfer-realization')]
    public function receiveRealization($draft_costs)
    {

        // dd('sett',$draft_costs);
        $this->draft_costs = $draft_costs;
    }

    public function store($is_submit=false,$goToStep=null)
    {
        $generals = [
            'introduction' => $this->introduction,
            'activity_description' => $this->activity_description,
            'background' => $this->background,
            'speaker_material' => $this->speaker_material,
            'obstacles' => $this->obstacles,
            'conclusion' => $this->conclusion, // lom ada
            'recommendations' => $this->recommendations,
            'application_id' => $this->application_id,
            // 'attachments' => $this->attachment_files,
            'department_id' => AuthService::currentAccess()['department_id'],
        ];
        $report = ApplicationService::storeReport($generals, $this->draft_costs,$this->speakers_info,$is_submit);
        if ($report['status']) {
        if (empty($goToStep) && $is_submit) {
            $this->redirectRoute('reports.create', ['application_id' => $this->application_id], false, true);
        }else{
            $this->step = $goToStep;
        }
        }

    }

    
    public function storeNext($step=1){
        $this->store();
        return $this->directStep($step);
    }



    public function updatedSpjFile(){
        if (!empty($this->spj_file)) {
            $this->old_spj_file = $this->spj_file;
            $this->onAttachmentChanged([$this->spj_file],'spj-file');
        }
    }
    public function updatedMinutesFile(){
        // dd($this->minutes_file);
        if (!empty($this->minutes_file)) {
            $this->old_minutes_file = $this->minutes_file;
            $this->onAttachmentChanged([$this->minutes_file],'minutes-file');
        }
    }
    public function updatedDocumentationPhotos(){
        if (!empty($this->documentation_photos)) {
            $this->old_documentation_photos = $this->documentation_photos;
            $this->onAttachmentChanged($this->documentation_photos,'document-photos');
        }
    }
    public function updatedAttendenceFiles(){
        if (!empty($this->attendence_files)) {
            $this->old_attendence_files = $this->attendence_files;
            $this->onAttachmentChanged($this->attendence_files,'attendence-files');
        }
    }


    public function onAttachmentChanged($files,$type)
    {
        $path = 'temp/report/'.$this->application->id.'/' . $type;
            if (Storage::disk('minio')->exists($path)) {
                    $files = Storage::disk('minio')->files($path); // Mendapatkan semua file dalam folder
                    foreach ($files as $file) {
                        Storage::disk('minio')->delete($file); // Hapus setiap file
                    }
            }

            $this->attachment_files[$type]= [
                'file_path'=>$path,
                'type'=>$type,
                'application_report_id'=>$this->application->report->id,
            ];
            foreach ($files as $key => $file) {
                if ($file instanceof TemporaryUploadedFile) {
                   $file->store($path, 'minio');
                }
            }
            
}
    public function injectDocument()
    {
        return TemplateProcessorService::generateWord($this->application);
    }


    public function openModalConfirm($type = 'reject')
    {
        $this->open_modal_confirm = $type;
        $this->dispatch('open-modal');
    }

    public function closeModalConfirm()
    {
        $this->open_modal_confirm = null;
        $this->notes = '';
        $this->dispatch('close-modal');
    }

    public function submitModalConfirm()
    {
        $this->updateFlowStatus($this->open_modal_confirm, $this->notes);
        $this->dispatch('open-modal');
    }





    public function loadData()
    {
        foreach ($this->application->report->getAttributes() as $key => $value) {
            $this->$key = $value;
            if ($key == 'background') {
                $this->$key = $this->application->detail->general_description;
            }
        }

        $file_id_minutes =$this->application->report->attachments()->where('type','minutes-file')->get()->pluck('file_id');
        $file_id_spj =$this->application->report->attachments()->where('type','spj-file')->get()->pluck('file_id');
        $documentation_file_ids =$this->application->report->attachments()->where('type','document-photos')->get()->pluck('file_id');
        $attendence_file_ids =$this->application->report->attachments()->where('type','attendence-files')->get()->pluck('file_id');
        
        foreach ($file_id_minutes ??[] as $key => $file_id) {
            $this->old_minutes_file[] = FileManagementService::getFileStorageById($file_id);
        }
        foreach ($file_id_spj ??[] as $key => $file_id) {
            $this->old_spj_file[] = FileManagementService::getFileStorageById($file_id);
        }
        foreach ($documentation_file_ids ??[] as $key => $file_id) {
            $this->old_documentation_photos[] = FileManagementService::getFileStorageById($file_id);
        }
        foreach ($attendence_file_ids ??[] as $key => $file_id) {
            $this->old_attendence_files[] = FileManagementService::getFileStorageById($file_id);
        }
        // dd($file_id_minutes,$this->old_minutes_file);

    }

    public function openModalPreview($file_id)
    {
        // $draft_cost_budget = ApplicationParticipant::find($draft_cost_id);
        $files = Files::where('id',$file_id)->get()->toArray();
        // $file_ids = $draft_cost_budget->files()->get()->pluck('id')->toArray();
        $this->dispatch('open-modal-preview', [...$files]);
    }


    #[On('on-destroy-attachment')]
    public function receiveAttachmentToDestroy($params)
    {
        $this->destroyUploadedAttachment($params['file_id'],$params['related_table'],$params['props'],$params['is_need_return']);
    }

    public function destroyUploadedAttachment($file_id,$related_table=[],$props=null,$is_need_return=false)
    {
        $res = ApplicationService::destroyAttachments($file_id,$related_table);
        if ($res['status']) {
           if ($props && isset($this->{$props})) {
            // Filter array untuk menghapus elemen dengan file_id yang sesuai
            $this->{$props} = array_filter($this->{$props}, function ($file) use ($file_id) {
                return (string)$file['file_id'] !== $file_id;
            });
                $this->{$props} = array_values($this->{$props});
            }
            if ($is_need_return) {
                return $res['status'];
            }
        }

   
        // return session()->flash(($res['status']?'success':'error'), $res['message']);
    }

    public function updateLetterNumber()
    {
        $res = ApplicationService::updateLetterNumber($this->letter_numbers, $this->application);
        if ($res) {
            $this->redirectRoute('applications.create.draft', ['application_id' => $this->application_id], false, true);
        }
    }


    public function downloadDocx()
    {
        return response()->download(TemplateProcessorService::downloadDocxGenerated());
    }

    public function updateFlowStatus($action, $note = '')
    {
        $tes = ApplicationService::updateFlowApprovalStatus($action, $this->application_id, $note);
        if ($tes['status']) {
            $this->dispatch('closeModalConfirm');
            $this->redirectRoute('reports.create', ['application_id' => $this->application_id], false, true);
        }
    }

     public function downloadTemplateMinutes(){
        $savePath = public_path('templates/Form notulensi.docx');
        return response()->download($savePath);
    }

    public function debug()
    {
        $app_file = $this->application->applicationFiles()->findCode('notulensi')->first();
        TemplateProcessorService::generateDocumentToPDF($this->application,'notulensi',$app_file);
    }
    public function nextStep()
    {
        $step = $this->step + 1;
        $this->directStep($step);
    }
    public function prevStep()
    {
        $step = $this->step - 1;
        $this->directStep($step);
    }
    public function directStep($step)
    {
        $this->step = $step;
    }

public function addRowAttachment($name)
{
    // Pastikan elemen dengan nama yang sesuai ada di dalam row_attachments
    if (isset($this->row_attachments[$name]['elements'])) {
        // Ambil elemen terakhir dari array elements
        $lastElement = end($this->row_attachments[$name]['elements']);

        // Ekstrak angka terakhir dari elemen terakhir
        $lastIndex = (int) filter_var($lastElement, FILTER_SANITIZE_NUMBER_INT);

        // Tambahkan elemen baru dengan index yang ditingkatkan
        $newElement = $name . '_' . ($lastIndex + 1);
        $this->row_attachments[$name]['elements'][] = $newElement;

        $this->dispatch('add-input-element', [$newElement]);
    } else {
        // Jika nama tidak ditemukan di row_attachments
        logger()->error("Attachment name '{$name}' not found in row_attachments");
    }
}

public function removeRowAttachment($key, $name)
{
    // Pastikan elemen dengan nama yang sesuai ada di dalam elements
    if (isset($this->row_attachments[$name]['elements'])) {
        // Cari index elemen yang sesuai dengan $key
        $index = array_search($key, $this->row_attachments[$name]['elements']);

        if ($index !== false) {
            // Hapus elemen dari array elements
            unset($this->row_attachments[$name]['elements'][$index]);

            // Reindex array untuk menjaga konsistensi
            $this->row_attachments[$name]['elements'] = array_values($this->row_attachments[$name]['elements']);

            // Emit event untuk menghapus elemen di frontend
            $this->dispatch('remove-input-element', [$key]);
        } else {
            // Jika elemen tidak ditemukan
            logger()->warning("Element with key '{$key}' not found in row_attachments['{$name}']['elements']");
        }
    } else {
        // Jika nama tidak ditemukan di row_attachments
        logger()->error("Attachment name '{$name}' not found in row_attachments");
    }
}


    public function setupElementUpload(){
        foreach ($this->row_attachments as $key => $value) {
            $old = 'old_'.$key;
            $total_oldfile = count($this->{$old});

         
            $compare_limit = ($value['max_file']? ($total_oldfile < $value['max_file'] ? ($value['max_file']-$total_oldfile):0) :3);
          if ($compare_limit > 0) {
            for ($i=0; $i < $compare_limit ; $i++) { 
            $this->row_attachments[$key]['elements'][]=$key.'_'.$i;

          }
          }
          
        }
    }

    #[On('set-inf-id')]
    public function setInfId($params)
    {
        $this->selected_inf = $params['id'];
    }
}
