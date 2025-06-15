<?php

namespace App\Livewire\FormLists\Applications;

use App\Imports\ApplicationsImport;
use App\Livewire\AbstractComponent;
use App\Models\Application;
use App\Services\AuthService;
use Livewire\Attributes\On;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationCreateDraft extends AbstractComponent
{

    public $application_id =null;
    // step 1
    public $activity_output;
    public $performance_indicator;
    public $activity_volume;
    public $general_description;
    public $objectives;
    public $beneficiaries;
    public $activity_scope;
    public $implementation_method;
    public $implementation_stages;
    public $activity_start_date;
    public $activity_end_date;
    public $activity_location;


    // Step 2
    public $participants= [];
    public $excel_participant = null;

    public function mount($application_id = null)
    {

       $this->permissionApplication($application_id);

       $this->application_id = $application_id;

    }
    public function render()
    {
        return view('livewire.form-lists.applications.application-create-draft')->extends('layouts.main');
    }

    public function saveDraft($last_saved){
        $this->step = $last_saved;
      $data = [
            'draft_step_saved'=> $this->last_saved,
            'activity_output' => $this->activity_output,
            'performance_indicator' => $this->performance_indicator,
            'activity_volume' => $this->activity_volume, // lom ada
            'general_description' => $this->general_description,
            'objectives' => $this->objectives,
            'beneficiaries' => $this->beneficiaries,
            'activity_scope' => $this->activity_scope,
            'implementation_method' => $this->implementation_method,
            'implementation_stages' => $this->implementation_stages,
            'activity_start_date' => $this->activity_start_date,
            'activity_end_date' => $this->activity_end_date,
            'activity_location' => $this->activity_location,
        ];
    }
    public function submit(){

    }
    public function nextStep()
    {
        $step= $this->step+1;
        $this->directStep($step);
    }
    public function prevStep()
    {
        $step = $this->step-1;
        $this->directStep($step);
    }
    public function directStep($step){
        $this->step = $step;
    }



    public function importParticipant(){

        $this->import('participant');
    }


    public function import($type){
        // $this->validate([
        //     'excel_participant' => 'required|mimes:xlsx,xls', // Maksimal 10MB
        //     // 'excel_participant' => 'required|mimes:xlsx,xls|max:102400', // Maksimal 10MB
        // ]);

        switch ($type) {
            case 'participant':
                $importer = new ApplicationsImport($this->application_id);
                Excel::import($importer, $this->excel_participant,'local', \Maatwebsite\Excel\Excel::XLSX);
                $rows = $importer->finest_data; // Ambil hasil olahan
                $this->participants = $rows;

                $this->dispatch('transfer-participant', [...$this->participants]);
                break;
            default:
                # code...
                break;
        }
    }

    public function clearAllParticipant(){
        $this->participants = [];
    }

}
