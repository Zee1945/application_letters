<?php

namespace App\Livewire\FormLists\Master;

use App\Models\Position;
use Livewire\Component;

class PositionList extends Component
{
    public function render()
    {
        $position = Position::with(['department', 'position'])
            ->when($this->search, function($query) {
                $query->where('name', 'like', '%'.$this->search.'%');
            })
            ->paginate(10);
        return view('livewire.form-lists.master.position-list');
    }
}
