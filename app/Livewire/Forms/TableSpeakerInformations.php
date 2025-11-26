<?php

namespace App\Livewire\Forms;

use App\Models\ApplicationParticipant;
use App\Models\Files;
use App\Models\ParticipantType;
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
    public $application;

    public function mount($application = null,$participants=null)
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
        $this->speakers = $participants->filter(function ($item) use ($speaker_type_id) {
            return $item->participant_type_id == $speaker_type_id;
        });
    }

    public function normalizeData()
    {
        $new_rows = $this->rows;
        return array_map(function($row){
            dd($row);
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


}
