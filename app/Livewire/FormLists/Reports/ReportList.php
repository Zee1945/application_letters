<?php

namespace App\Livewire\FormLists\Reports;

use App\Models\Application;
use App\Services\ApplicationService;
use Livewire\Component;
use Livewire\WithPagination;

class ReportList extends Component
{
    use WithPagination;
     public $pagination = 10;
    public function render()
    {
        $reports= ApplicationService::getListReport()->orderBy('created_at', 'desc')->paginate($this->pagination);
        return view('livewire.form-lists.reports.report-list',compact('reports'))->extends('layouts.main');
    }
}
