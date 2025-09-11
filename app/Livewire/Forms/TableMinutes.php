<?php

namespace App\Livewire\Forms;

use App\Models\ApplicationParticipant;
use App\Models\ParticipantType;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Component;

class TableMinutes extends Component
{
    public $handleDisable ='';
    public $minutes = [
         [
            'topic' => '',
            'explanation' => '',
            'follow_up' => '',
            'deadline' => null,
            'assignee' => '',
         ],
         [
            'topic' => '',
            'explanation' => '',
            'follow_up' => '',
            'deadline' => null,
            'assignee' => '',
         ],
         [
            'topic' => '',
            'explanation' => '',
            'follow_up' => '',
            'deadline' => null,
            'assignee' => '',
         ],
         [
            'topic' => '',
            'explanation' => '',
            'follow_up' => '',
            'deadline' => null,
            'assignee' => '',
         ]
         
    ];

    public function addRow()
    {
        $this->minutes[] = [
            'topic' => '',
            'explanation' => '',
            'follow_up' => '',
            'deadline' => null,
            'assignee' => '',
        ];
    }

    public function removeRow($index)
    {
        if (count($this->minutes) > 1) {
            array_splice($this->minutes, $index, 1);
        }
    }

    public function syncMinutes(){
        $filter_minutes = array_filter($this->minutes,function($item){
            return !empty($item['topic']);
        });
        $this->dispatch('transfer-minutes',[...$filter_minutes]);
    }

    public function debugger(){
        dd($this->minutes);
    }


   public function mount($oldMinutes,$handleDisable)
    {   
        $this->handleDisable = $handleDisable;
        $this->minutes = count($oldMinutes) > 0? $oldMinutes : $this->minutes;

        // dd($this->minutes);
    }
  


    public function render()
    {
        return view('livewire.forms.table-minutes');
    }
}
