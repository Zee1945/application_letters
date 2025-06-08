<?php

namespace App\Livewire\FormLists\Applications;

use App\Livewire\AbstractComponent;
use App\Models\Application;
use App\Services\AuthService;
use Livewire\Component;

class ApplicationCreateDraft extends AbstractComponent
{
    public $content2 = '';
    public function mount($application_id = null)
    {
       $this->permissionApplication($application_id);

    }
    public function render()
    {
        return view('livewire.form-lists.applications.application-create-draft')->extends('layouts.main');
    }
}
