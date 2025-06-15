<?php

namespace App\Livewire;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads; // Untuk menangani file uploads

#[\AllowDynamicProperties]
abstract class AbstractComponent extends Component
{
    use WithPagination;
    use WithFileUploads;

    #[Url(except: '')]
    public $step = 2;

    public $application = null;
    public function permissionApplication($application_id)
    {
        $application = \App\Models\Application::find($application_id);
        $permission = \App\Services\AuthService::currentAccess()['department_id'] === $application->department_id
            ? true : false;
        if (!$permission) {
            session()->flash('error', 'You do not have permission to access this application.');
            return redirect()->route('applications.index');
        }
        // $this->step = $application->draft_step_saved;
        return $this->application = $application;
    }
}
?>
