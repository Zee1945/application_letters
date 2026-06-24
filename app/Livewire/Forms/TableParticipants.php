<?php

namespace App\Livewire\Forms;

use App\Models\CommiteePosition;
use App\Models\ParticipantType;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class TableParticipants extends Component
{
    public $participantType = '';
    public $commiteePositions = [];

    // Reactive: ikut ter-update saat parent mengubah $this->participants
    // (edit/hapus peserta), sehingga tabel ikut refresh tanpa reload.
    #[Reactive]
    public $participants = [];

    public $handleDisable = '';

    public function mount($participants, $participantType, $handleDisable = '')
    {
        $this->participants = $participants;
        $this->participantType = $participantType;
        $this->handleDisable = $handleDisable;
    }

    public function render()
    {
        // Hitung ulang tiap render agar sinkron dgn perubahan dari parent.
        return view('livewire.forms.table-participants', [
            'filteredParticipants' => $this->filterParticipantByType(),
        ]);
    }


    public function filterParticipantByType(){
        $participant_type= new ParticipantType();
        $ids = [];

        // Tipe "others": tampilkan peran SELAIN 4 tipe utama (peserta, panitia,
        // moderator, narasumber). Logikanya kebalikan — exclude, bukan include.
        if ($this->participantType === 'others') {
            $excluded_ids = $participant_type::whereIn('name', ['peserta', 'panitia', 'moderator', 'narasumber'])
                ->get()->pluck('id')->toArray();

            return array_filter($this->participants, function ($item) use ($excluded_ids) {
                return !in_array($item['participant_type_id'], $excluded_ids);
            });
        }

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
        return array_filter($this->participants,function ($item) use ($ids){
            if (in_array($item['participant_type_id'],$ids)) {
                return $item;
            }
        });
    }

    public function findName($type,$id)
    {
        switch ($type) {
            case 'participant':
                return ParticipantType::findOrFail($id)->name;
            default:
                dd('none are match');
                break;
        }
    }
    public function debugger()
    {
        dd($this->participants);
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
