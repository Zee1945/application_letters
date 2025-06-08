<?php

namespace App\Livewire\FormLists\Applications;

use App\Models\Application;
use Livewire\Component;

class ApplicationList extends Component
{
    public function render()
    {
        $applications = Application::all();
        return view('livewire.form-lists.applications.application-list',compact('applications'))
            ->extends('layouts.main');
    }
}
