<?php

namespace App\Livewire\Forms;

use App\Models\ApplicationParticipant;
use App\Models\ParticipantType;
use Livewire\Attributes\On;
use Livewire\Component;

class TableRundown extends Component
{
    public $get_moderators = [];
    public $get_speakers = [];
    public $participants = [];

    public $options = ['opt_moderators' => [], 'opt_speakers' => []];

    // Default 4 rows
    public $rundown = [
        ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => []],
        ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => []],
        ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => []],
        ['date' => '', 'start_date' => '', 'end_date' => '', 'name' => null, 'speaker_text' => [], 'moderator_text' => []],
    ];

    public function mount($rundowns)
    {
        $this->rundown = count($rundowns) >0? $this->denormalizeData($rundowns) : $this->rundown;
    }
    public function render()
    {
        // $normalizeData = $this->normalizeData($this->rundown);
        // $this->dispatch('transfer-rundowns',[...$normalizeData]);
        return view('livewire.forms.table-rundown');
    }
    public function filterUserByType($participant_type_name)
    {
        $ids = ParticipantType::whereIn('name', [$participant_type_name])->get()->pluck('id')->toArray();
        return array_filter($this->participants, function ($item) use ($ids) {
            if (in_array($item['participant_type_id'], $ids)) {
                return $item;
            }
        });
    }

    public function addRow()
    {
        $this->rundown[] = [
            'date' => '',
            'start_date' => '',
            'end_date' => '',
            'name' => '',
            'speaker_text' => [],
            'moderator_text' => [],
        ];
    }

    public function denormalizeData($rundowns)
    {
        $data = $rundowns;

        foreach ($data as &$row) {
            // Ambil jam dari start_date dan end_date
            if (!empty($row['start_date'])) {
                $row['start_date'] = date('H:i', strtotime($row['start_date']));  // Ambil hanya jam dan menit
            }
            if (!empty($row['end_date'])) {
                $row['end_date'] = date('H:i', strtotime($row['end_date']));  // Ambil hanya jam dan menit
            }

            // Konversi speakers dan moderators yang menggunakan format "nama-instansi" menjadi array
            if (!empty($row['speaker_text'])) {
                $row['speaker_text'] = explode(';', $row['speaker_text']);
            }

            if (!empty($row['moderator_text'])) {
                $row['moderator_text'] = explode(';', $row['moderator_text']);
            }
        }

        return $data;
    }

    public function normalizeData($rundowns)
    {
        $fulfil_data = array_filter($rundowns,function($item){
            return !empty($item['date']) && !empty($item['start_date']) && !empty($item['start_date']) && !empty($item['name']) ;
        });
        $data = $fulfil_data;

        foreach ($data as &$row) {
            // Gabungkan date dengan start_date dan end_date untuk membentuk datetime
            if (!empty($row['date']) && !empty($row['start_date'])) {
                $row['start_date'] = $row['date'] . ' ' . $row['start_date'];  // Gabungkan tanggal dengan waktu mulai
            }

            if (!empty($row['date']) && !empty($row['end_date'])) {
                $row['end_date'] = $row['date'] . ' ' . $row['end_date'];  // Gabungkan tanggal dengan waktu selesai
            }

            // Konversi array speakers dan moderators kembali menjadi string dengan pemisah ";"
            if (!empty($row['speaker_text'])) {
                $row['speaker_text'] = implode(';', $row['speaker_text']);  // Gabungkan array menjadi string dengan pemisah ;
            } else {
                $row['speaker_text'] = null;
            }

            if (!empty($row['moderator_text'])) {
                $row['moderator_text'] = implode(';', $row['moderator_text']);  // Gabungkan array menjadi string dengan pemisah ;
            }else{
                $row['moderator_text'] = null;
            }
        }

        return $data;
    }

    public function debug(){
        // dd($this->normalizeData($this->rundown));
        $normalizeData = $this->normalizeData($this->rundown);
        $this->dispatch('transfer-rundowns',[...$normalizeData]);

    }

    public function syncRundown($name, $value)
    {
        // Parse nama dari $name untuk mendapatkan index dan field
        $parts = explode('.', $name);
        $index = $parts[1];
        $field = $parts[2];

        // Perbarui data di dalam $rundown
        if ($field =='speaker_text' || $field == 'moderator_text') {
            // Jika value sudah ada dalam array, maka hapus
            if (in_array($value, $this->rundown[$index][$field])) {
                // Hapus value dari array
                $this->rundown[$index][$field] = array_diff($this->rundown[$index][$field], [$value]);
            } else {
                // Jika tidak ada, tambahkan value ke array
                $this->rundown[$index][$field][] = $value;
            }
        }else {
            $this->rundown[$index][$field] = $value;
        }

        // Normalisasi data setelah perubahan
        $normalizeData = $this->normalizeData($this->rundown);

        // Dispatch event untuk mengirimkan data yang sudah diperbarui
        $this->dispatch('transfer-rundowns', [...$normalizeData]);

        // Optional: Jika Anda ingin menambahkan event khusus setelah update
        // $this->dispatch('rundownUpdated');
    }




    public function removeRow($index)
    {
        if (count($this->rundown) > 1) {
            array_splice($this->rundown, $index, 1);
        }
    }

    public function addSpeaker($index)
    {
        $this->rundown[$index]['speaker_text'][] = '';  // Menambah input narasumber baru
        $this->rundown = $this->rundown; // Menetapkan ulang array untuk memicu pembaruan
        $this->dispatch('rundownUpdated'); // Menggunakan dispatch untuk memicu pembaruan
    }

    public function removeSpeaker($index, $subIndex)
    {
        if (count($this->rundown[$index]['speaker_text']) > 0) {
            array_splice($this->rundown[$index]['speaker_text'], $subIndex, 1); // Menghapus narasumber
            $this->rundown = $this->rundown; // Menetapkan ulang array untuk memicu pembaruan
            $this->dispatch('rundownUpdated'); // Menggunakan dispatch untuk memicu pembaruan
        }
    }

    public function addModerator($index)
    {
        $this->rundown[$index]['moderator_text'][] = '';  // Menambah input narasumber baru
        $this->rundown = $this->rundown; // Menetapkan ulang array untuk memicu pembaruan
        $this->dispatch('rundownUpdated'); // Menggunakan dispatch untuk memicu pembaruan
    }

    public function removeModerator($index, $subIndex)
    {
        if (count($this->rundown[$index]['moderator_text']) > 0) {
            array_splice($this->rundown[$index]['moderator_text'], $subIndex, 1); // Menghapus narasumber
            $this->rundown = $this->rundown; // Menetapkan ulang array untuk memicu pembaruan
            $this->dispatch('rundownUpdated'); // Menggunakan dispatch untuk memicu pembaruan
        }
    }

    #[On('transfer-participant-to-rundown')]
    public function receiveParticipant($participant)
    {
        // dd($participant);
        $moderator_id = ParticipantType::where('name','Moderator')->first()->id;
        $speaker_id = ParticipantType::where('name','Narasumber')->first()->id;
        foreach ($participant??[] as $key => $pt) {
               if ($pt['participant_type_id'] == $moderator_id) {
                $this->options['opt_moderators'][]=['text'=>$pt['name'].'-' .$pt['institution']];
               }
               if ($pt['participant_type_id'] == $speaker_id) {
                $this->options['opt_speakers'][]=['text'=>$pt['name'].'-' .$pt['institution']];
               }
        }
    }


}
