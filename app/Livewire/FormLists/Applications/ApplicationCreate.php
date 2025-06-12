<?php

namespace App\Livewire\FormLists\Applications;

use App\Models\Application;
use App\Models\User;
use App\Services\ApplicationService;
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\On;

class ApplicationCreate extends Component
{

    public $activity_name; // Nama Kegiatan
    public $fund_source; // Sumber Pendanaan
    public $verificator ; // Verifikator/Penandatangan

    public function render()
    {
        $user_approvers = User::approvers()->get();
        return view('livewire.form-lists.applications.application-create',compact('user_approvers'))
        ->extends('layouts.main');
    }


    public function store()
    {
        // Validate the form data
        // $this->validate();
        // $app = Application::create([
        //     'name' => $this->name,
        //     'fund_source' => $this->fund_source,
        //     'verifier' => $this->verificator,
        // ]);
        $this->verificator = User::approvers()->get()->pluck('id');
        $app = [
            'activity_name' => $this->activity_name,
            'funding_source' => $this->fund_source,
            'verificators' => $this->verificator,
        ];

        $application = ApplicationService::storeApplications($app);
        // dd($application);
        if (!$application) {
            session()->flash('error', 'Failed to create application.');
            return;
        }
        return redirect()->route('applications.create.draft',['application_id'=>$application->id]);






        // Optionally, reset the form data
        $this->reset();

        // Redirect or show a success message
        session()->flash('message', 'Application successfully created!');
    }



}
