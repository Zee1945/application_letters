<?php

namespace App\Livewire\FormLists\Applications;

use App\Exports\ApplicationsExport;
use App\Imports\ApplicationsImport;
use App\Jobs\GenerateApplicationFileJob;
use App\Livewire\AbstractComponent;
use App\Models\Application;
use App\Models\ApplicationFile;
use App\Services\ApplicationService;
use App\Services\AuthService;
use App\Services\TemplateProcessorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ApplicationCreateDraft extends AbstractComponent
{

    public $application_id =null;

    public $open_modal_confirm=null;
    public $notes = null;
    // step 1
    public $activity_output;
    public $activity_outcome;
    public $performance_indicator;
    public $activity_volume;
    public $general_description;
    public $objectives;
    public $beneficiaries;
    public $activity_scope;
    public $implementation_method;
    public $implementation_stages;
    public $activity_dates;
    public $activity_location;
    public $unit_of_measurment;

    public $sameDay =true;

    public $is_submit_letter_number;



    // Step 2
    public $participants= [];
    public $rundowns= [];
    public $draft_costs= [];
    public $excel_participant = null;

    public $letter_numbers=[];


    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    public function mount($application_id = null)
    {
      
       $this->application = Application::find($application_id);
       $this->step = $this->application->draft_step_saved;
       $this->participants = $this->application->participants->toArray();
       $this->rundowns = $this->application->schedules->toArray();
    //    dd($this->application->schedules);
       $this->draft_costs = $this->application->draftCostBudgets->toArray();
    //    dd($this->draft_costs);
       $this->application_id = $application_id;

        if (count($this->participants) > 0) $this->dispatch('transfer-participant-to-rundown', [...$this->participants]);
        // if (count($this->draft_costs) > 0) $this->dispatch('transfer-draft-costs', [...$this->draft_costs]);

        // if (count($this->rundowns) > 0) $this->dispatch('transfer-rundowns', [...$this->rundowns]);


        $keysToKeep = ['id', 'letter_label', 'letter_name', 'letter_date', 'is_with_date', 'type_field', 'letter_number'];
        $this->letter_numbers = array_map(function ($item) use ($keysToKeep) {
            return array_intersect_key($item, array_flip($keysToKeep));
        }, $this->application->letterNumbers->toArray());
        // if (count($this->rundowns) > 0) $this->dispatch('transfer-rundowns', [...$this->rundowns]);

        $this->permissionApplication($application_id);


        //  $this->dispatch('transfer-rundowns', [...$this->rundowns]);
        //  $this->dispatch('transfer-draft-costs', [...$this->draft_costs]);
    }
    public function render()
    {
//           $is_relevan_admin = AuthService::adminHasAccess($app_departemn_id);
// dd($is_relevan_admin);
        if (count($this->draft_costs) > 0) $this->dispatch('transfer-draft-costs', [...$this->draft_costs]);
        if ($this->application->detail) {
            $this->loadData();
        }
        // if (count($this->participants) > 0) $this->dispatch('transfer-participant-to-rundown', [...$this->participants]);


        return view('livewire.form-lists.applications.application-create-draft')->extends('layouts.main');
    }

    public function saveDraftLetterNumber(){
        $res = ApplicationService::updateLetterNumber($this->letter_numbers,$this->application,false);
        if ($res) {
            $this->redirectRoute('applications.create.draft',['application_id'=> $this->application_id],false,true);
        }

    }
    public function saveDraft($last_saved,$is_submit=false){
        $this->step = $last_saved;

      $generals = [
            'draft_step_saved'=> $this->step,
            'activity_output' => $this->activity_output,
            'activity_outcome' => $this->activity_outcome,
            'performance_indicator' => $this->performance_indicator,
            'unit_of_measurment' => $this->unit_of_measurment,
            'activity_volume' => $this->activity_volume, // lom ada
            'general_description' => $this->general_description,
            'objectives' => $this->objectives,
            'beneficiaries' => $this->beneficiaries,
            'activity_scope' => $this->activity_scope,
            'implementation_method' => $this->implementation_method,
            'implementation_stages' => $this->implementation_stages,
            'activity_dates' => $this->activity_dates,
            'activity_location' => $this->activity_location,
            'application_id' => $this->application_id,
            'department_id' => AuthService::currentAccess()['department_id'],
        ];
        $application = ApplicationService::storeApplicationDetails($generals,$this->participants,$this->rundowns,$this->draft_costs,$is_submit);

        $this->redirectRoute('applications.create.draft',['application_id'=> $this->application_id],false,true);
    }



    public function injectDocument(){
        return TemplateProcessorService::generateWord($this->application);
    }


    public function openModalConfirm($type='reject'){
        $this->open_modal_confirm =$type;
        $this->dispatch('open-modal');
    }
    public function openModalConfirmSubmit($is_letter_number=false){
        $this->is_submit_letter_number = $is_letter_number;
        $this->dispatch('open-modal-confirm-submit');
    }

    public function closeModalConfirmSubmit(){
        $this->is_submit_letter_number = false;
        $this->dispatch('close-modal-confirm-submit');
    }

    public function closeModalConfirm(){
        $this->open_modal_confirm =null;
        $this->notes='';
        $this->dispatch('close-modal');
    }

    #[On('transfer-rundowns')]
    public function receiveRundowns($rundowns)
    {
        $this->rundowns = $rundowns;

    }

    public function submitModalConfirm(){
        $this->updateFlowStatus($this->open_modal_confirm, $this->notes);
        $this->dispatch('open-modal');
    }

    
    public function regenerateDocument(){
        GenerateApplicationFileJob::dispatch($this->application);
    }





    public function loadData(){
        foreach ($this->application->detail->getAttributes() as $key => $value) {
            $this->$key = $value;
        }
    }
#[On('update-letter-number')]
    public function updateLetterNumber(){
            
        $this->closeModalConfirmSubmit();
        $res = ApplicationService::updateLetterNumber($this->letter_numbers,$this->application);
            // $res = true;
            if ($res) {
                $this->dispatch('close-modal-loading-generate-doc');
                // $this->process_document_status = 'success';
                $this->dispatch('open-modal-loading-generate-doc',...[
                    'status' => $this->process_document_status,
                ]);
                // $this->redirectRoute('applications.create.draft', ['application_id' => $this->application_id], false, true);
            }else{
                $this->process_document_status = 'failed';
            }
    }

    

    public function syncData(){

        sleep(0.5);
        // $this->saveDraft(3);

    }
    public function openModalLoadingGenerateDoc(){
        $this->updateLetterNumber();
        $this->dispatch('open-modal-loading-generate-doc',...[
                    'status' => $this->process_document_status,
                ]);
        // $this->dispatch('open-modal-loading-generate-doc');

    }
    public function closeModalLoadingGenerateDoc(){
        $this->process_document_status = 'nothing';
        // $this->dispatch('open-modal-loading-generate-doc');
        $this->redirectRoute('applications.create.draft', ['application_id' => $this->application_id], false, true);

    }

    public function downloadDocx()
    {
        return response()->download(TemplateProcessorService::downloadDocxGenerated());
    }

    public function updateFlowStatus($action,$note=''){
        $tes = ApplicationService::updateFlowApprovalStatus($action,$this->application_id,$note);
        if ($tes['status']) {
            $this->dispatch('closeModalConfirm');
            $this->redirectRoute('applications.create.draft', ['application_id' => $this->application_id], false, true);
        }

    }


    public function debug(){

        try {
            // $this->updateLetterNumber();
            // GenerateApplicationFileJob::dispatch($this->application);
            // TemplateProcessorService::generateDocumentToPDF($this->application,'tor');
            // $code = 'surat_permohonan_narasumber';
            // $app_files = $this->application->applicationFiles()->findCode($code)->get();
            // foreach ($app_files as $key => $app_file) {
            //     TemplateProcessorService::generateDocumentToPDF($this->application,$code,$app_file);
            // }
            // TemplateProcessorService::generateApplicationDocument($this->application);
            // TemplateProcessorService::generateApplicationDocument($app);
        } catch (\Exception $e) {
            // Tangkap pesan kesalahan dan tampilkan
            dd($e);
        }
        // dd($tes);
        // TemplateProcessorService::generateWord($this->application);
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
    public function downloadTemplateExcel(){
        $savePath = public_path('referensi/template upload Peserta dan RAB.xlsx');
        return response()->download($savePath);
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
                $rows = $importer; // Ambil hasil olahan
                $this->participants = $rows->finest_participant_data;
                // $this->rundowns = $rows->finest_rundown_data;
                $this->draft_costs = $rows->finest_draft_cost_data;

                // $this->dispatch('transfer-rundowns', [...$this->rundowns]);
                $this->dispatch('transfer-draft-costs', [...$this->draft_costs]);
                 if (count($this->participants) > 0) $this->dispatch('transfer-participant-to-rundown', [...$this->participants]);

                break;
            default:
                # code...
                break;
        }
    }

    public function exportPreviousData(){
            $filename = preg_replace('/[\/\\\:\*\?"<>\|]/', ' ', $this->application->activity_name) . '.xlsx';
            return Excel::download(new ApplicationsExport($this->participants,$this->draft_costs), $filename);
    }

    public function clearAllParticipant(){
        $this->participants = [];
        $this->draft_costs = [];
        $this->rundowns = [];
        $this->dispatch('resetRundown');
    }

}
