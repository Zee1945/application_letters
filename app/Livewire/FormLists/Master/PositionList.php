<?php

namespace App\Livewire\FormLists\Master;

use App\Models\Position;
use Livewire\Component;
use Livewire\WithPagination;

class PositionList extends Component
{
     use WithPagination;
      public $search = '';
    public function render()
    {
        $positions = Position::with(['department'])
            ->when($this->search, function($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->paginate(10);
        return view('livewire.form-lists.master.position-list',compact('positions'))
            ->extends('layouts.main');
    }
}
