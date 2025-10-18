<?php

namespace App\Livewire\FormLists\Reports;

use App\Models\Application;
use App\Models\Department;
use App\Services\ApplicationService;
use App\Services\AuthService;
use App\Services\MasterManagementService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class ReportList extends Component
{
    use WithPagination;
    public $pagination = 10;
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
        $reports = ApplicationService::getListReport($this->search,$this->status_approval,$this->department_id)->paginate(10);
        $department = Department::find(AuthService::currentAccess()['department_id']);
        $department_options = MasterManagementService::getDepartmentListOptions()->get();
        return view('livewire.form-lists.reports.report-list',compact('reports','department','department_options'))->extends('layouts.main');
    }
}
