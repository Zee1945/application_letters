<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDetail;
use App\Models\ApplicationDraftCostBudget;
use App\Models\ApplicationParticipant;
use App\Models\ApplicationSchedule;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use App\Services\AuthService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ApplicationService
{
    /**
     * Register services.
     *
     * @return void
     */
    public static function storeApplications($data)
    {
        // Assuming you have an Application model

        try {
            DB::beginTransaction();
            // Validate the data if necessary
                $get_verificators = User::approvers()->whereIn('id', $data['verificators'])->get();
                $application = Application::create([
                    'activity_name'        => $data['activity_name'],
                    'funding_source'       => (int)$data['funding_source'],
                    'approval_status'      => 1, // Ongoing or pending
                    'current_user_approval' => 0,
                    'user_approval_ids'    => implode(',', $get_verificators->pluck('id')->toArray()),
                    'department_id'        => AuthService::currentAccess()['department_id'],
                    'created_by'           => AuthService::currentAccess()['id'],
                ]);
                // Create ApplicationUserApproval records for each verifier
                foreach ($get_verificators as $key => $verifier) {
                    $application->userApprovals()->create([
                        'user_id' => $verifier->id,
                        'user_text' => $verifier->name, // Assuming you want to store the name of the verifier
                        'sequence' => $key+1, // Assuming sequence starts at 1
                        'status' => 0, // Assuming 0 means pending
                        'application_id' => $application->id,
                        'department_id' => AuthService::currentAccess()['department_id'], // Assuming the department ID is from the current user
                        'created_by' => Auth::id(), // Assuming you want to store who created this approval
                    ]);
                }
                DB::commit();
                return $application;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }


        return $application;
    }

    public static function storeApplicationDetails($data,$participants=[],$rundowns=[], $draft_costs=[])
    {
        try {
            DB::beginTransaction();
            $app = Application::find($data['application_id']);
            $app->draft_step_saved = $data['draft_step_saved'];
            $app->save();

            unset($data['draft_step_saved']);
            $details = ApplicationDetail::updateOrCreate($data);

                if (!$details) {
                    return ['status' => false, 'message' => 'data pengajuan Gagal ditambahkan'];
                }
                foreach ($participants as $key => $value) {
                    $participant = ApplicationParticipant::updateOrCreate($value);
                }

                foreach ($rundowns as $key => $value) {
                    $rundown = ApplicationSchedule::updateOrCreate($value);
                }
                foreach ($draft_costs as $key => $value) {
                    $draft_cost = ApplicationDraftCostBudget::updateOrCreate($value);
                }
            DB::commit();
            return['status'=>true,'message'=>'data pengajuan berhasil ditambahkan'];
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            // throw $th;
        }

    }




    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
