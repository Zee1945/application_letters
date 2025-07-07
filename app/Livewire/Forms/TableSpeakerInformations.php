<?php

namespace App\Livewire\Forms;

use App\Models\ParticipantType;
use Livewire\Component;
use Livewire\WithFileUploads;

class TableSpeakerInformations extends Component
{
    use WithFileUploads;

    public $speakers;
    public $rows = [];
    public $file_cv = [];
    public $img_ktp = [];
    public $img_npwp = [];

    public function mount($participants = null)
    {
        $this->filterSpeakers($participants);
        $this->rows = array_fill(0, count($this->speakers), ['participant_id' => null, 'file_cv' => null, 'img_ktp' => null, 'img_npwp' => null]);
        $speakers = $this->speakers->values();
        $this->rows = array_map(function ($item, $index) use ($speakers) {
            $item['participant_id'] = $speakers[$index]['id'];  // Pastikan index valid di $speakers
            return $item;
        }, $this->rows, array_keys($this->rows));
    }

    public function render()
    {
        $new_index = 0;
        $this->dispatch('transfer-speakerInformation',[...$this->rows]);
        return view('livewire.forms.table-speaker-informations', compact('new_index'));
    }

    public function filterSpeakers($participants)
    {
        $speaker_type_id = ParticipantType::whereName('Narasumber')->first()->id;
        $this->speakers = $participants->filter(function ($item) use ($speaker_type_id) {
            return $item->participant_type_id == $speaker_type_id;
        });
    }

    public function storeFilesToMinio()
    {
        $directory = '07-2025/1/report/speakers-information'; // Path dasar untuk MinIO

        foreach ($this->rows as $row) {
            $participant_id = $row['participant_id']; // Ambil ID peserta

            // Simpan setiap file ke MinIO
            $files = [
                'file_cv' => $row['file_cv'],
                'img_ktp' => $row['img_ktp'],
                'img_npwp' => $row['img_npwp']
            ];

            foreach ($files as $key => $file) {
                if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                    // Tentukan path file yang akan disimpan di MinIO
                    $fileName = $participant_id . '-' . $key . '.' . $file->getClientOriginalExtension();
                    $path = $directory . '/' . $participant_id . '/' . $fileName;

                    // Simpan file ke MinIO
                    $file->storeAs($path, $fileName, 's3'); // Gunakan 's3' sesuai disk MinIO
                }
            }
        }
    }
}
