<?php

namespace App\Livewire\FormLists\Applications;

use App\Livewire\AbstractComponent;
use App\Models\Application;
use App\Services\AuthService;
use Livewire\Attributes\On;
use Livewire\Component;

class ApplicationCreateDraft extends AbstractComponent
{
    public $content2 = '';
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

    public function mount($application_id = null)
    {
       $this->permissionApplication($application_id);

    }
    public function render()
    {
        return view('livewire.form-lists.applications.application-create-draft')->extends('layouts.main');
    }

    public function saveDraft($last_saved){
        $this->last_saved = $last_saved;
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
            'activity_location' => $this->activity_location
        ];
    }

    #[on('update-participant')]
    public function updateParticipant(){

    }
    public function submit(){

    }

}
