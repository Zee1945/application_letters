<?php

namespace App\Livewire\Forms;

use App\Models\CommiteePosition;
use App\Models\ParticipantType;
use Livewire\Component;

class TableParticipants extends Component
{
    public $participantType = '';
    public $commiteePositions = [];
    public $rows = [];
    public $defaultRows = ['name' => '', 'institution' => '', 'participant_type_id' => '', 'commitee_position_id' => null];

    public function mount($participantType)
    {
        $this->participantType = $participantType;
        $this->rows = array_fill(0, 4, $this->defaultRows);
        $this->commiteePositions = $this->getCommiteePositions();
    }

    public function render()
    {
        $get_participant_type = $this->getParticipantTypes();
        return view('livewire.forms.table-participants', compact('get_participant_type'));
    }

    public function getParticipantTypes()
    {
        $model = new ParticipantType();
        switch ($this->participantType) {
            case 'speaker':
                return $model->whereNotIn('name', ['Panitia', 'Peserta'])->get();
            case 'commitee':
            case 'participant':
                return $model->where('name', $this->participantType)->first();
            default:
                return $model->all();
        }
    }

    public function getCommiteePositions()
    {
        return CommiteePosition::all();
    }

    public function addRow()
    {
        $this->rows[] = $this->defaultRows;
    }

    public function deleteRow($index)
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows); // Re-index the array
    }

    public function saveData()
    {
        dd($this->rows);
    }
}
