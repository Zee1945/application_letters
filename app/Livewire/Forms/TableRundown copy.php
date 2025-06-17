<?php

namespace App\Livewire\Forms;

use App\Models\ApplicationParticipant;
use App\Models\ParticipantType;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class TableRundown extends Component
{

    public $get_moderators=[];
    public $get_speakers=[];

    public $participants=[];
    public $rundown = [
        [
            'date' => '',
            'start_time' => '',
            'end_time' => '',
            'event' => '',
            'speakers' => [],  // Array untuk menyimpan narasumber
            'moderators' => [], // Array untuk menyimpan moderator
        ],
    ];

    public function filterUserByType($participant_type_name){
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
            'start_time' => '',
            'end_time' => '',
            'event' => '',
            'speakers' => [],  // Inisialisasi array kosong untuk narasumber
            'moderators' => [], // Inisialisasi array kosong untuk moderator
        ];
    }

    public function removeRow($index)
    {
        if (count($this->rundown) > 1) {
            array_splice($this->rundown, $index, 1);
        }
    }

    #[On('transfer-rundowns')]
    public function receiveRundowns($rundowns){
        $this->participants = $rundowns;
        $this->get_moderators = $this->filterUserByType('moderator');
        $this->get_speakers = $this->filterUserByType('narasumber');
    }

    public function debugger(){
        dd($this->rundown);
    }

    public function addSpeaker($index)
    {
        $this->rundown[$index]['speakers'][] = '';  // Menambah input narasumber baru
    }

    public function removeSpeaker($index, $subIndex)
    {
        if (count($this->rundown[$index]['speakers']) > 0) {
            array_splice($this->rundown[$index]['speakers'], $subIndex, 1); // Menghapus narasumber
        }
    }

    public function addModerator($index)
    {
        $this->rundown[$index]['moderators'][] = '';  // Menambah input moderator baru
    }

    public function removeModerator($index, $subIndex)
    {
        if (count($this->rundown[$index]['moderators']) > 0) {
            array_splice($this->rundown[$index]['moderators'], $subIndex, 1); // Menghapus moderator
        }
    }


    public function render()
    {
        return view('livewire.forms.table-rundown');
    }
}
