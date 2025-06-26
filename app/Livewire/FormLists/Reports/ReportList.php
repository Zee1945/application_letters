<?php

namespace App\Livewire\FormLists\Reports;

use App\Models\Application;
use App\Services\ApplicationService;
use Livewire\Component;

class ReportList extends Component
{
    public function render()
    {
        $reports= ApplicationService::getListReport();
        return view('livewire.form-lists.reports.report-list',compact('reports'))->extends('layouts.main');
    }
}
