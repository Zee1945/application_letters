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
    public function mount() {}
    public function render()
    {

        $this->department_list = MasterManagementService::getDepartmentList();

        if (empty($this->selected_department)) {
            $selected = array_filter($this->department_list, function ($item) {
                return $item['is_selected'];
            });
            if (!empty($selected)) {
                $this->selected_department = array_values($selected)[0]['value'];
            }
        }
        $apps = Application::needMyProcess()->get();
        $reports = Application::whereHas('report', function ($query) {
            return $query->needMyProcess();
        })->get();

        $apps = $apps->map(function ($app) {
            $app->trans_type = 1;
            return $app;
        });
        $reports = $reports->map(function ($app) {
            $app->trans_type = 2;
            return $app;
        });


        $need_process_apps = $apps->merge($reports);

        $count = ApplicationService::getDashboardInformation($this->selected_department);
        // dd($count_total_application);
        $user = AuthService::currentAccess()['department_id'];

        return view('livewire.form-lists.dashboard', compact('need_process_apps','count'))->extends('layouts.main');
    }



    public function filterDepartment()
    {
        // dd($this->selected_department);

        $this->render();
    }
}
