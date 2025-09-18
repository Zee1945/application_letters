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
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ReportCreate extends AbstractComponent
{
    public $application_id = null;

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



    // Step 2
    public $speakers_info = [];
    public $rundowns = [];
    public $draft_costs = [];
    public $spj_file = null;
    public $old_spj_file = null;
    public $minutes_file = null;
    public $old_minutes_file = null;
    public $documentation_photos = [];
    public $old_documentation_photos = [];
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
        $this->draft_costs = $this->application->draftCostBudgets->toArray();

        $keysToKeep = ['id', 'letter_label', 'letter_name', 'type_field', 'letter_number'];
        $this->letter_numbers = array_map(function ($item) use ($keysToKeep) {
            return array_intersect_key($item, array_flip($keysToKeep));
        }, $this->application->letterNumbers->toArray());

        if ($this->application->report) {
            $this->loadData();
        }

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

    public function store($is_submit=false)
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
            'attachments' => $this->attachment_files,
            'department_id' => AuthService::currentAccess()['department_id'],
        ];
        $report = ApplicationService::storeReport($generals, $this->draft_costs,$this->speakers_info,$is_submit);
        if ($report['status']) {
            $this->redirectRoute('reports.create', ['application_id' => $this->application_id], false, true);
        }

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
        }

        $file_id_minutes =$this->application->report->attachments()->where('type','minutes-file')->first()->file_id??null;
        $file_id_spj =$this->application->report->attachments()->where('type','spj-file')->first()->file_id??null;
        $documentation_file_ids =$this->application->report->attachments()->where('type','document-photos')->get()->pluck('file_id');
        $this->old_minutes_file = FileManagementService::getFileStorageById($file_id_minutes);
        $this->old_spj_file = FileManagementService::getFileStorageById($file_id_spj);
        foreach ($documentation_file_ids ??[] as $key => $file_id) {
            $this->old_documentation_photos[] = FileManagementService::getFileStorageById($file_id);
        }
        // dd($this->old_spj_file);
    }

    public function openModalPreview($file_id)
    {
        // $draft_cost_budget = ApplicationParticipant::find($draft_cost_id);
        $files = Files::where('id',$file_id)->get()->toArray();
        // $file_ids = $draft_cost_budget->files()->get()->pluck('id')->toArray();
        $this->dispatch('open-modal-preview', [...$files]);
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

    public function clearAllParticipant()
    {
        $this->participants = [];
        $this->draft_costs = [];
        $this->rundowns = [];
    }
}
