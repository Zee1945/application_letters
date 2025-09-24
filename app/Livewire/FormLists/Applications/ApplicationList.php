<?php

namespace App\Livewire\FormLists\Applications;

use App\Models\Application;
use App\Models\Department;
use App\Services\AuthService;
use App\Services\MasterManagementService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ApplicationList extends Component
{
     use WithPagination;

     public $pagination = 10;

    // #[Url(except: '')]
    // public $department_id = 2;
    // public department_list
    public function render()
    {
        // $this->department_list = MasterManagementService::getDepartmentList();
        $applications = Application::orderBy('created_at', 'desc')->paginate($this->pagination);
        // $applications = Application::paginate($this->pagination);
        $department = Department::find(AuthService::currentAccess()['department_id']);
        return view('livewire.form-lists.applications.application-list',compact('applications','department'))
            ->extends('layouts.main');
    }
}
