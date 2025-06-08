<?php

namespace App\Livewire;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[\AllowDynamicProperties]
abstract class AbstractComponent extends Component
{
    use WithPagination;

    #[Url(except: '1')]
    public $step = '';

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
        $this->step = $application->draft_step_saved;
        return $this->application = $application;
    }
}
?>
