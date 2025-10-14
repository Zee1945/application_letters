<?php

namespace App\Livewire\FormLists\Applications;

use App\Models\Application;
use App\Models\Department;
use App\Services\ApplicationService;
use App\Services\AuthService;
use App\Services\MasterManagementService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ApplicationList extends Component
{
     use WithPagination;


 #[Url(except: 1)]
    public $page = 1;
    
 #[Url(except: '')]
    public $search = '';

 #[Url(except: '')]

     public $status_approval = '';
 #[Url(except: '')]
     public $department_id = '';

    public function render()
    {
        $applications = ApplicationService::getListApp($this->search,$this->status_approval,$this->department_id)->paginate(10);
        $department = Department::find(AuthService::currentAccess()['department_id']);
        $department_options = MasterManagementService::getDepartmentListOptions()->get();
        return view('livewire.form-lists.applications.application-list',compact('applications','department','department_options'))
            ->extends('layouts.main');
    }
}
