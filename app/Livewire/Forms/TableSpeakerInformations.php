<?php

namespace App\Livewire\Forms;

use App\Models\ApplicationParticipant;
use App\Models\Files;
use App\Models\ParticipantType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class TableSpeakerInformations extends Component
{
    use WithFileUploads;

    public $speakers;
    public $rows = [];
    public $cv_file_id = [];
    public $idcard_file_id = [];
    public $npwp_file_id = [];
    public $material_file_id = [];
    public $selected_inf_id = '';
    public $application;

    public function mount($application = null,$participants=null, $selectedInfId='')
    {
        $this->application = $application;
        $this->filterSpeakers($participants);
        $this->rows = array_fill(0, count($this->speakers), ['participant_id' => null, 'cv_file_id' => null, 'idcard_file_id' => null, 'npwp_file_id' => null,'material_file_id'=>null]);
        $speakers = $this->speakers->values();
        $this->rows = array_map(function ($item, $index) use ($speakers) {
            $item['participant_id'] = $speakers[$index]['id'];  // Pastikan index valid di $speakers
            $item['cv_file_id'] = $speakers[$index]['cv_file_id'];  // Pastikan index valid di $speakers
            $item['idcard_file_id'] = $speakers[$index]['idcard_file_id'];  // Pastikan index valid di $speakers
            $item['npwp_file_id'] = $speakers[$index]['npwp_file_id'];  // Pastikan index valid di $speakers
            $item['material_file_id'] = $speakers[$index]['material_file_id'];  // Pastikan index valid di $speakers
            return $item;
        }, $this->rows, array_keys($this->rows));


        if (empty($selectedInfId)) {
          $this->selected_inf_id = 'part_'.$this->speakers[0]['id'].'_'.$this->speakers[0]['participant_type_id'];
        }else{
              $selected = $this->speakers->filter(function ($row) use ($selectedInfId) {
                    return $row['id'] === $selectedInfId;
                })->values()[0];
            $this->selected_inf_id = 'part_'.$selected['id'].'_'.$selected['participant_type_id'];
        }
    }

    public function render()
    {
        $new_index = 0;
        $normalizeData = $this->normalizeData();
        $this->dispatch('transfer-speakerInformation',[...$normalizeData]);
        return view('livewire.forms.table-speaker-informations', compact('new_index'));
    }


    

    public function syncValueSpeaker()
    {
        $this->render();
    }
    public function filterSpeakers($participants)
    {
        $speaker_type_id = ParticipantType::whereName('Narasumber')->first()->id;
        $moderator_type_id = ParticipantType::whereName('Moderator')->first()->id;
        $this->speakers = $participants->filter(function ($item) use ($speaker_type_id,$moderator_type_id) {
            return $item->participant_type_id == $speaker_type_id || $item->participant_type_id == $moderator_type_id ;
        });
    }
    
    public function setSelectedInf($id,$type){
        $this->selected_inf_id = 'part_'.$id.'_'.$type;
        $this->dispatch('set-inf-id',['id'=>$id]);
    }

    public function remove($id,$type){
        $this->selected_inf_id = 'part_'.$id.'_'.$type;
        $this->dispatch('set-inf-id',['id'=>$id]);
    }

    public function normalizeData()
    {
        $new_rows = $this->rows;
        return array_map(function($row){
                $participant_id = $row['participant_id']; // Ambil ID peserta

            // Simpan setiap file ke MinIO
            $files = [
                'cv_file_id' => $row['cv_file_id'],
                'idcard_file_id' => $row['idcard_file_id'],
                'npwp_file_id' => $row['npwp_file_id'],
                'material_file_id' => $row['material_file_id']
            ];
            $directory = 'temp/report/speaker-information/' . $participant_id;
            if (Storage::disk('minio')->exists($directory)) {
                $old_files = Storage::disk('minio')->files($directory);
                foreach ($old_files as $old_file) {
                    Storage::disk('minio')->delete($old_file);
                }
            }

            foreach ($files as $key => $file) {
                if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    // Tentukan path file yang akan disimpan di MinIO
                    $fileName = $file->getClientOriginalName().'-'.$participant_id . '-' . $key . '.' . $file->getClientOriginalExtension();
                    $path = $directory.'/'.$fileName;
                    $row[$key]=$path;
                    // Simpan file ke MinIO
                    $file->storeAs($directory, $fileName, 'minio'); // Gunakan 's3' sesuai disk MinIO
                }
            }

            return $row;
        },$new_rows);
    }
    public function debug()
    {
        $normalizeData = $this->normalizeData();
        $this->dispatch('transfer-speakerInformation',[...$normalizeData]);

        // dd($normalizeData);
    }
    public function openModalPreview($file_id)
    {
        // $draft_cost_budget = ApplicationParticipant::find($draft_cost_id);
        $files = Files::where('id',$file_id)->get()->toArray();
        // $file_ids = $draft_cost_budget->files()->get()->pluck('id')->toArray();
        $this->dispatch('open-modal-preview', [...$files]);
    }

    public function destroyAttachment($file_id,$index_row,$column)
    {
        $res = $this->dispatch('on-destroy-attachment', ['file_id'=>$file_id,'related_table'=>['application_participants'],'props'=>null,'is_need_return'=>true]);
        if ($res) {
             // Hapus nilai pada kolom tertentu di baris yang sesuai
        $this->removeArrayValue($index_row, $column);
        
        // Update tampilan
        $this->syncValueSpeaker();
        }
    }

    public function removeArrayValue($index, $column)
{
    // Pastikan index valid
    if (isset($this->rows[$index])) {
        // Pastikan kolom ada dalam array
        if (array_key_exists($column, $this->rows[$index])) {
            // Set nilai kolom menjadi null
            $this->rows[$index][$column] = null;
            
            Log::info("Nilai kolom '{$column}' pada index {$index} berhasil dihapus");
        } else {
            Log::warning("Kolom '{$column}' tidak ditemukan pada index {$index}");
        }
    } else {
        Log::warning("Index {$index} tidak valid dalam array rows");
    }
}


}
