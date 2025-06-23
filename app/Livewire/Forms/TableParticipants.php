<?php

namespace App\Livewire\Forms;

use App\Models\CommiteePosition;
use App\Models\ParticipantType;
use Livewire\Component;

class TableParticipants extends Component
{
    public $participantType = '';
    public $commiteePositions = [];
    public $raw_participant = [];
    public $filteredParticipants=[];

    public function mount($participants,$participantType)
    {
        $this->raw_participant = $participants;
        $this->participantType = $participantType;
        $this->filteredParticipants = $this->filterParticipantByType();
        $this->commiteePositions = $this->getCommiteePositions();
    }

    public function render()
    {
        return view('livewire.forms.table-participants');
    }


    public function filterParticipantByType(){
        $participant_type= new ParticipantType();
        $ids = [];
        switch ($this->participantType) {
            case 'speaker':
                $ids = $participant_type::whereIn('name',['narasumber','moderator'])->get()->pluck('id')->toArray();
                break;
            case 'participant':
                $ids = $participant_type::whereIn('name',['peserta'])->get()->pluck('id')->toArray();
                break;
            case 'commitee':
                $ids = $participant_type::whereIn('name',['panitia'])->get()->pluck('id')->toArray();
                break;

            default:
                # code...
                break;
        }
        return array_filter($this->raw_participant,function ($item) use ($ids){
            if (in_array($item['participant_type_id'],$ids)) {
                return $item;
            }
        });
    }
    public function getCommiteePositions()
    {
        return CommiteePosition::all();
    }
    public function findName($type,$id)
    {
        switch ($type) {
            case 'commitee':
                return CommiteePosition::findOrFail($id)->name;
            case 'participant':
                return ParticipantType::findOrFail($id)->name;
            default:
                dd('none are match');
                break;
        }
    }
    public function debugger()
    {
        dd($this->raw_participant);
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
