<?php

namespace App\Livewire\Forms;

use App\Models\ApplicationParticipant;
use App\Models\ParticipantType;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class TableRundown extends Component
{
    public $rundowns = [];

    #[On('transfer-rundowns')]
    public function receiveRundowns($rundowns){
        $this->rundowns = $rundowns;
    }

    public function debugger(){
        dd($this->rundown);
    }


    public function render()
    {
        return view('livewire.forms.table-rundown');
    }
}
