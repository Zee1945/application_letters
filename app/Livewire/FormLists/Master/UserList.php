<?php

namespace App\Livewire\FormLists\Master;

use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserList extends Component
{
    use WithPagination;

    public $search = '';
    public $department = 0;

    protected $updatesQueryString = ['search', 'page'];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
       $departments = Department::all();

        // Query users based on filters
        $users = User::query()
            ->when($this->search, function ($query) {
                return $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->when($this->department, function ($query) {
                return $query->where('department_id', $this->department);
            })
            ->paginate(10);

        return view('livewire.form-lists.master.user-list', [
            'departments' => $departments,
            'users' => $users,
        ])->extends('layouts.main');
    }
}
