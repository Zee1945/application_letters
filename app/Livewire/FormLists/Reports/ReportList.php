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

 #[Url(except: '')]
     public $year = '';

 #[Url(except: 10)]
     public $per_page = 10;

    public function mount()
    {
        // Default filter tahun = tahun saat ini (jika belum di-set lewat URL).
        if ($this->year === '') {
            $this->year = (string) date('Y');
        }
    }

    /**
     * Opsi tahun untuk dropdown filter (dari tahun ini mundur 5 tahun).
     */
    public function getYearOptionsProperty()
    {
        $current = (int) date('Y');
        return range($current, $current - 5);
    }

    // Reset ke halaman 1 setiap kali filter / page size berubah.
    public function updatedSearch() { $this->resetPage(); }
    public function updatedStatusApproval() { $this->resetPage(); }
    public function updatedDepartmentId() { $this->resetPage(); }
    public function updatedYear() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function render()
    {
        $reports = ApplicationService::getListReport($this->search,$this->status_approval,$this->department_id,$this->year)
            ->paginate((int) $this->per_page);
        $department = Department::find(AuthService::currentAccess()['department_id']);
        $department_options = MasterManagementService::getDepartmentListOptions()->get();
        return view('livewire.form-lists.reports.report-list',compact('reports','department','department_options'))->extends('layouts.main');
    }
}
