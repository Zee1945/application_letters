<?php

namespace App\Livewire\FormLists\Applications;

use App\Models\Application;
use App\Models\Department;
use App\Services\AuthService;
use Livewire\Component;

class ApplicationList extends Component
{
    public function render()
    {
        $applications = Application::all();
        $department = Department::find(AuthService::currentAccess()['department_id']);
        return view('livewire.form-lists.applications.application-list',compact('applications','department'))
            ->extends('layouts.main');
    }
}
