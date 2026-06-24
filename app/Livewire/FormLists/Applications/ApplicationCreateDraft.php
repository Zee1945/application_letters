<?php

namespace App\Livewire\FormLists\Applications;

use App\Exports\ApplicationsExport;
use App\Imports\ParticipantsImport;
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
    public $alert_title = null;
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

    /**
     * State modal edit & konfirmasi hapus peserta.
     * edit_participant_index / delete_participant_index = index pada array $participants.
     */
    public $show_edit_participant_modal = false;
    public $edit_participant_index = null;
    public $show_delete_participant_modal = false;
    public $delete_participant_index = null;
    // Field yang bisa diedit (participant_type TIDAK termasuk).
    public $ep_name = '';
    public $ep_institution = '';
    public $ep_nip = '';
    public $ep_rank = '';
    public $ep_functional_position = '';
    public $ep_type_label = ''; // hanya untuk ditampilkan (read-only)

    /**
     * State modal "Tambah Partisipan / Opsi PJ".
     * pj_source: 'rundown' (tab Susunan Acara) => is_option_rundown=1,
     *            'participant' (tab Peran) => is_option_rundown=0.
     * pj_mode: 'existing' = pilih dari peserta, 'new' = buat baru.
     */
    public $participant_types = [];
    public $show_pj_modal = false;
    public $pj_source = 'rundown';
    public $pj_mode = 'existing';
    public $pj_participant_type_id = '';
    public $pj_new_type_name = '';
    public $pj_selected_participant = '';
    public $pj_name = '';
    public $pj_institution = '';
    public $pj_nip = '';
    public $pj_rank = '';
    public $pj_functional_position = '';

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
       $this->participant_types = \App\Models\ParticipantType::get()->toArray();
       $this->participants = $this->application->participants->toArray();
       $this->rundowns = $this->application->schedules->toArray();
    //    dd($this->application->schedules);
       $this->alert_title = $this->application->current_seq_user_approval > 4? "Laporan":"Pengajuan";
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
        // draft_costs disinkronkan ke komponen child via wire:model (#[Modelable]),
        // jadi tidak perlu dispatch event manual lagi.
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
if (!$application['status']) {
    # code...
        //  return redirect()->back()->withInput()->with('error', $application['message']);
         return redirect()->route('applications.create.draft',['application_id'=> $this->application_id])->with('error', $application['message']);
}else{
    $this->redirectRoute('applications.create.draft',['application_id'=> $this->application_id],false,true);
}
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


    /* ===================== Modal Tambah Partisipan / Opsi PJ ===================== */

    /**
     * Daftar peserta yang ada, difilter berdasarkan peran terpilih.
     * Dipakai select "Nama" pada mode 'existing'. Key = index asli array participants.
     */
    public function getPjFilteredParticipantsProperty()
    {
        if (empty($this->pj_participant_type_id)) {
            return [];
        }
        $result = [];
        foreach ($this->participants as $idx => $p) {
            if ((string)($p['participant_type_id'] ?? '') === (string)$this->pj_participant_type_id) {
                $result[$idx] = $p;
            }
        }
        return $result;
    }

    #[On('open-pj-modal')]
    public function openPjModal($source = 'rundown')
    {
        $this->resetPjForm();
        $this->pj_source = in_array($source, ['rundown', 'participant'], true) ? $source : 'rundown';
        $this->show_pj_modal = true;
    }

    public function closePjModal()
    {
        $this->show_pj_modal = false;
        $this->resetPjForm();
    }

    private function resetPjForm(): void
    {
        $this->reset([
            'pj_mode', 'pj_participant_type_id', 'pj_new_type_name', 'pj_selected_participant',
            'pj_name', 'pj_institution', 'pj_nip', 'pj_rank', 'pj_functional_position',
        ]);
        $this->pj_mode = 'existing';
    }

    public function updatedPjMode()
    {
        $this->pj_participant_type_id = '';
        $this->pj_new_type_name = '';
        $this->pj_selected_participant = '';
        $this->pj_name = '';
        $this->pj_institution = '';
        $this->pj_nip = '';
        $this->pj_rank = '';
        $this->pj_functional_position = '';
    }

    public function updatedPjParticipantTypeId()
    {
        $this->pj_selected_participant = '';
        $this->pj_name = '';
        $this->pj_institution = '';
    }

    public function updatedPjSelectedParticipant($value)
    {
        if ($value === '' || !isset($this->participants[$value])) {
            $this->pj_name = '';
            $this->pj_institution = '';
            return;
        }
        $p = $this->participants[$value];
        $this->pj_name = $p['name'] ?? '';
        $this->pj_institution = $p['institution'] ?? '';
    }

    /**
     * Submit modal: bangun peserta, set is_option_rundown sesuai asal modal,
     * store langsung ke DB bila draft sudah ada, masukkan ke array, lalu (bila
     * dari rundown) kabari TableRundown agar opsi officer ikut diperbarui.
     */
    public function submitPj()
    {
        // Tentukan participant_type_id; buat tipe baru bila mode 'new' + nama tipe diisi.
        if ($this->pj_mode === 'new' && trim($this->pj_new_type_name) !== '') {
            $typeName = trim($this->pj_new_type_name);

            $existing = null;
            foreach ($this->participant_types as $pt) {
                if (strcasecmp($pt['name'], $typeName) === 0) {
                    $existing = $pt;
                    break;
                }
            }

            if ($existing) {
                $typeId = $existing['id'];
            } else {
                $newType = \App\Models\ParticipantType::create([
                    'name' => $typeName,
                    'slug' => \Illuminate\Support\Str::slug($typeName),
                ]);
                $typeId = $newType->id;
                $this->participant_types = \App\Models\ParticipantType::get()->toArray();
            }
        } else {
            $typeId = $this->pj_participant_type_id;
        }

        $this->validate(
            ['pj_name' => 'required|string', 'pj_institution' => 'nullable|string'],
            ['pj_name.required' => 'Nama wajib diisi.']
        );

        if (empty($typeId)) {
            $this->addError('pj_participant_type_id', 'Peran wajib dipilih.');
            return;
        }

        $participant = [
            'application_id'      => $this->application_id,
            'participant_type_id' => (int) $typeId,
            'name'                => trim($this->pj_name),
            'institution'         => trim($this->pj_institution),
            'nip'                 => $this->pj_mode === 'new' ? ($this->pj_nip ?: null) : null,
            'rank'                => $this->pj_mode === 'new' ? ($this->pj_rank ?: null) : null,
            'functional_position' => $this->pj_mode === 'new' ? ($this->pj_functional_position ?: null) : null,
            'is_option_rundown'   => $this->pj_source === 'rundown' ? 1 : 0,
        ];

        // Jaring pengaman: store langsung bila draft sudah tersimpan (anti-hilang).
        // id sengaja tidak dimasukkan ke array (clearData+rebuild saat Save Draft).
        if (!empty($participant['application_id'])) {
            \App\Models\ApplicationParticipant::create($participant);
        }

        $this->participants[] = $participant;

        // Bila PJ opsi rundown, kabari TableRundown agar opsi officer diperbarui.
        if ($this->pj_source === 'rundown') {
            $this->dispatch('transfer-participant-to-rundown', [...$this->participants]);
        }

        $this->closePjModal();
        session()->flash('success', $this->pj_source === 'rundown' ? 'Opsi PJ berhasil ditambahkan.' : 'Partisipan berhasil ditambahkan.');
    }

    /* ===================== End Modal Tambah Partisipan / Opsi PJ ===================== */

    /* ===================== Edit & Hapus Peserta ===================== */

    /**
     * Buka modal edit untuk peserta pada index tertentu (index = key array $participants).
     * Dispatch dari komponen table-participants.
     */
    #[On('edit-participant')]
    public function editParticipant($index)
    {
        if (!isset($this->participants[$index])) {
            return;
        }

        $p = $this->participants[$index];
        $this->edit_participant_index = $index;
        $this->ep_name = $p['name'] ?? '';
        $this->ep_institution = $p['institution'] ?? '';
        $this->ep_nip = $p['nip'] ?? '';
        $this->ep_rank = $p['rank'] ?? '';
        $this->ep_functional_position = $p['functional_position'] ?? '';

        // Label peran hanya untuk ditampilkan (participant_type tidak boleh diubah).
        $type = \App\Models\ParticipantType::find($p['participant_type_id'] ?? null);
        $this->ep_type_label = $type->name ?? '-';

        $this->show_edit_participant_modal = true;
    }

    public function closeEditParticipant()
    {
        $this->show_edit_participant_modal = false;
        $this->reset([
            'edit_participant_index', 'ep_name', 'ep_institution',
            'ep_nip', 'ep_rank', 'ep_functional_position', 'ep_type_label',
        ]);
    }

    /**
     * Simpan perubahan peserta ke array. participant_type_id sengaja TIDAK diubah.
     * Persisten ke DB saat Save Draft (sesuai alur participants lain).
     */
    public function updateParticipant()
    {
        $this->validate(
            ['ep_name' => 'required|string'],
            ['ep_name.required' => 'Nama wajib diisi.']
        );

        $i = $this->edit_participant_index;
        if (!isset($this->participants[$i])) {
            $this->closeEditParticipant();
            return;
        }

        $this->participants[$i]['name'] = trim($this->ep_name);
        $this->participants[$i]['institution'] = trim($this->ep_institution);
        $this->participants[$i]['nip'] = $this->ep_nip ?: null;
        $this->participants[$i]['rank'] = $this->ep_rank ?: null;
        $this->participants[$i]['functional_position'] = $this->ep_functional_position ?: null;

        $this->dispatch('transfer-participant-to-rundown', [...$this->participants]);
        $this->closeEditParticipant();
    }

    /**
     * Buka konfirmasi hapus untuk peserta pada index tertentu.
     */
    #[On('confirm-delete-participant')]
    public function confirmDeleteParticipant($index)
    {
        if (!isset($this->participants[$index])) {
            return;
        }
        $this->delete_participant_index = $index;
        $this->show_delete_participant_modal = true;
    }

    public function closeDeleteParticipant()
    {
        $this->show_delete_participant_modal = false;
        $this->delete_participant_index = null;
    }

    /**
     * Hapus peserta: jika sudah ada di DB (punya id), hapus record + file-nya
     * via destroyParticipant (cegah orphan). Lalu buang dari array.
     */
    public function deleteParticipant()
    {
        $i = $this->delete_participant_index;
        if (!isset($this->participants[$i])) {
            $this->closeDeleteParticipant();
            return;
        }

        $participant = $this->participants[$i];
        if (!empty($participant['id'])) {
            ApplicationService::destroyParticipant($participant['id']);
        }

        // Buang dari array & re-index agar key konsisten.
        unset($this->participants[$i]);
        $this->participants = array_values($this->participants);

        $this->dispatch('transfer-participant-to-rundown', [...$this->participants]);
        $this->closeDeleteParticipant();
        session()->flash('success', 'Peserta berhasil dihapus.');
    }

    /* ===================== End Edit & Hapus Peserta ===================== */


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
        $savePath = public_path('referensi/template upload Peserta.xlsx');
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
                $importer = new ParticipantsImport($this->application_id);
                Excel::import($importer, $this->excel_participant,'local', \Maatwebsite\Excel\Excel::XLSX);
                $rows = $importer; // Ambil hasil olahan
                $this->participants = $rows->finest_participant_data;
                // Draft cost kini punya importer & komponen sendiri (DraftCostImport),
                // jadi tidak lagi diproses dari import participant ini.

                if (count($this->participants) > 0) $this->dispatch('transfer-participant-to-rundown', [...$this->participants]);

                break;
            default:
                # code...
                break;
        }
    }

    public function exportPreviousData(){
            $filename = preg_replace('/[\/\\\:\*\?"<>\|]/', ' ', $this->application->activity_name) . '.xlsx';
            return Excel::download(new ApplicationsExport($this->participants), $filename);
    }

    public function clearAllParticipant(){
        $this->participants = [];
    }

}
