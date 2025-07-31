<?php

namespace App\Livewire\FormLists\Reports;

use App\Imports\ApplicationsImport;
use App\Livewire\AbstractComponent;
use App\Models\Application;
use App\Services\ApplicationService;
use App\Services\AuthService;
use App\Services\TemplateProcessorService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;

class ReportCreate extends AbstractComponent
{
    use WithFileUploads;
    public $application_id = null;

    public $open_modal_confirm = null;
    public $notes = null;
    // step 1
    public $introduction;
    public $activity_description;
    public $obstacles;
    public $conclusion;
    public $recommendations;
    public $closing;



    // Step 2
    public $speakers_info = [];
    public $rundowns = [];
    public $draft_costs = [];
    public $excel_participant = null;

    public $letter_numbers = [];


    // public function __set($name, $value)
    // {
    //     if (property_exists($this, $name)) {
    //         $this->$name = $value;
    //     }
    // }

    public function mount($application_id = null)
    {
        $this->application = Application::find($application_id);

        $this->step = $this->application->draft_step_saved;
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

        return view('livewire.form-lists.reports.report-create')->extends('layouts.main');
    }

    #[On('transfer-speakerInformation')]
    public function receiveSpeakerInformation($speaker_info){
        $this->speakers_info = $speaker_info;
    }

    #[On('transfer-realization')]
    public function receiveRealization($draft_costs)
    {
        $this->draft_costs = $draft_costs;
    }

    public function store($is_submit=false)
    {
        $generals = [
            'introduction' => $this->introduction,
            'activity_description' => $this->activity_description,
            'obstacles' => $this->obstacles,
            'conclusion' => $this->conclusion, // lom ada
            'recommendations' => $this->recommendations,
            'closing' => $this->closing,
            'application_id' => $this->application_id,
            'department_id' => AuthService::currentAccess()['department_id'],
        ];

        // dd($generals);

        $report = ApplicationService::storeReport($generals, $this->draft_costs,$this->speakers_info,$is_submit);
        if ($report['status']) {
            $this->redirectRoute('reports.create', ['application_id' => $this->application_id], false, true);
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

    public function debug()
    {
        $app_file = $this->application->applicationFiles()->findCode('laporan_kegiatan')->first();
        TemplateProcessorService::generateDocumentToPDF($this->application,'laporan_kegiatan',$app_file);
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
    public function downloadTemplateExcel()
    {
        $savePath = public_path('referensi/template upload data.xlsx');
        return response()->download($savePath);
    }






    public function clearAllParticipant()
    {
        $this->participants = [];
        $this->draft_costs = [];
        $this->rundowns = [];
    }
}
