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
        
        if (!$application) {
            session()->flash('error', 'Application not found.');
            return redirect()->route('applications.index');
        }

        $currentUser = \App\Services\AuthService::currentAccess();
        $userDepartmentId = $currentUser['department_id'];
        
        // Ambil role user dari position
        $user = \App\Models\User::find($currentUser['id']);
        $userRoles = $user->position->getRoleNames()->toArray();
        
        // Cek apakah user memiliki role finance atau dekan
        $isFinanceOrDekan = array_intersect(['finance', 'dekan','kabag'], $userRoles);
        
        $hasPermission = false;
        
        if ($isFinanceOrDekan) {
            // Jika finance/dekan, bisa akses application dari:
            // 1. Department sendiri
            // 2. Child departments (department yang parent_id-nya adalah department user)
            
            $childDepartmentIds = \App\Models\Department::where('parent_id', $userDepartmentId)
                                                      ->pluck('id')
                                                      ->toArray();
            $allowedDepartmentIds = array_merge([$userDepartmentId], $childDepartmentIds);
            
            $hasPermission = in_array($application->department_id, $allowedDepartmentIds);
        } else {
            // User biasa hanya bisa akses application dari department sendiri
            $hasPermission = $userDepartmentId === $application->department_id;
        }
        
        if (!$hasPermission) {
            session()->flash('error', 'You do not have permission to access this application.');
            return redirect()->route('applications.index');
        }
        
        // $this->step = $application->draft_step_saved;
        return $this->application = $application;
    }
}
?>
