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


 #[Url(except: '')]
    public $search = '';

 #[Url(except: '')]

     public $status_approval = '';
 #[Url(except: '')]

     public $department_id = '';
     


    public function updatingSearch()
    {
        $this->resetPage();
    }

    //  public $query_params = [
    //     'search'=>null,
    //     'current_approval_status'=>null,
    //     'departemen_id'=>null,
    //  ];

    // #[Url(except: '')]
    // public $department_id = 2;
    // public department_list
    // public function mount()
    // {
      
    // }
    public function render()
    {
        // $this->query_params = [
        //     'search'=>$this->search,
        //     'current_approval_status'=>$this->status_approval,
        //     'departemen_id'=>$this->department_id,
        // ];
        // $this->department_list = MasterManagementService::getDepartmentList();
        // $applications = Application::orderBy('created_at', 'desc')->paginate($this->pagination);

        $applications = ApplicationService::getListApp($this->search,$this->status_approval,$this->department_id)->paginate(10);
        // dd($applications);
        $department = Department::find(AuthService::currentAccess()['department_id']);
        return view('livewire.form-lists.applications.application-list',compact('applications','department'))
            ->extends('layouts.main');
    }
}
