<?php

namespace App\Livewire\FormLists;

use App\Models\Application;
use App\Models\Department;
use App\Services\ApplicationService;
use App\Services\AuthService;
use App\Services\MasterManagementService;
use Livewire\Component;

class Dashboard extends Component
{

    public $selected_department;
    public $department_list;
    public function mount(){
        $this->department_list = MasterManagementService::getDepartmentList();

        if (empty($this->selected_department)) {
    $selected = array_filter($this->department_list, function($item){
        return $item['is_selected'];
    });
    if (!empty($selected)) {
        $this->selected_department = array_values($selected)[0]['value'];
    }

}
    }
    public function render()
    {
        

        $apps = Application::needMyProcess()->get();
        $reports = Application::whereHas('report',function($query){
            return $query->needMyProcess();
        })->get();

        $apps = $apps->map(function($app) {
            $app->trans_type = 1;
            return $app;
        });
        $reports = $reports->map(function($app) {
            $app->trans_type = 2;
            return $app;
        });

        $need_process_apps = $apps->merge($reports);

        $user = AuthService::currentAccess()['department_id'];

        $total_application_department = Application::getByDepartemnt($user,false)->get()->count();
        return view('livewire.form-lists.dashboard',compact('need_process_apps'))->extends('layouts.main');
    }

}
