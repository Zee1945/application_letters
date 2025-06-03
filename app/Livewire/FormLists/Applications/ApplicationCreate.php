<?php

namespace App\Livewire\FormLists\Applications;

use Livewire\Component;

class ApplicationCreate extends Component
{
    public function render()
    {
        $applications = [];
        return view('livewire.form-list.application.application-create',compact('applications'))
        ->extends('layouts.main');
    }
}
