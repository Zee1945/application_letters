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
use PhpOffice\PhpWord\TemplateProcessor;

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
                $get_finance = User::approvers()
                                ->where('department_id', AuthService::currentAccess()['department_id'])
                                ->role('finance')->first();

                $application = Application::create([
                    'activity_name'        => $data['activity_name'],
                    'funding_source'       => (int)$data['funding_source'],
                    'approval_status'      => 0, //Draft
                    'current_user_approval' => $get_finance->id,
                    'user_approval_ids'    => implode(',', $get_verificators->pluck('id')->toArray()),
                    'department_id'        => AuthService::currentAccess()['department_id'],
                    'created_by'           => AuthService::currentAccess()['id'],
                ]);
                // Create ApplicationUserApproval records for each verifier
                foreach ($get_verificators as $key => $verifier) {

                        if ($verifier->hasRole('finance')) {
                            $sequence = 1;
                        }else if($verifier->hasRole('dekan')){
                            $sequence = 2;
                        }
                    $application->userApprovals()->create([
                        'user_id' => $verifier->id,
                        'user_text' => $verifier->name, // Assuming you want to store the name of the verifier
                        'sequence' => $sequence, // Assuming sequence starts at 1
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

    public static function updateFlowApprovalStatus($action, $application_id,$note='')
    {
        try {
        DB::beginTransaction();
        $app = Application::findOrFail($application_id);
        $current_user_id = AuthService::currentAccess()['id'];
        switch ($action) {
            case 'submit':
                    if ($app->created_by == $current_user_id && $app->approval_status < 6) {
                        $app->approval_status = 6;
                        $app->save();
                    }
                    break;
            case 'approve':
                    if ($current_user_id ==  $app->current_user_approval && $app->approval_status > 5 && $app->approval_status < 11) {

                        $user_approvals = $app->userApprovals()->where('user_id', $current_user_id)->first();
                        $user_approvals->status = 11;
                        $user_approvals->save();

                        $max_seq = $app->userApprovals()->max('sequence');
                        $approval_max_seq = $app->userApprovals()->where('sequence',$max_seq)->first();
                        if ( $current_user_id == $approval_max_seq->user_id && $approval_max_seq->status > 10 && $approval_max_seq->status < 21) {
                            $app->approval_status = 11;
                            $app->save();

                            self::storeListLetterNumber($app);
                        }else{
                            $current_user = $app->userApprovals()->where('user_id',$app->current_user_approval)->first();
                            $next_user = $app->userApprovals()->where('sequence', $current_user->sequence+1)->first();
                            $app->current_user_approval = $next_user->user_id;
                            $app->save();
                        }

                    }
                    break;
            case 'revise':
                    if ($current_user_id == $app->current_user_approval && $app->approval_status > 5 && $app->approval_status < 11 ) {
                        $app->approval_status = 2;
                        $app->note = $note;
                        $app->save();

                        $user_approvals = $app->userApprovals()->where('user_id', $current_user_id)->first();
                        $user_approvals->status = 2;
                        $user_approvals->note = $note;
                        $user_approvals->save();
                    }
                    break;
            case 'reject':
                    if ($current_user_id ==  $app->current_user_approval && $app->approval_status > 5 && $app->approval_status < 11) {
                        $app->approval_status = 21;
                        $app->note = $note;
                        $app->save();


                        $user_approvals = $app->userApprovals()->where('user_id', $current_user_id)->first();
                        $user_approvals->status = 21;
                        $user_approvals->note = $note;
                        $user_approvals->save();
                    }

                    break;

            default:
                # code...
                break;
        }
            DB::commit();
            return ['status'=>true,'message'=>'success update flow'];
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return ['status' => false, 'message' => 'Failed update flow'];
        }
    }
    public static function storeApplicationDetails($data,$participants=[],$rundowns=[], $draft_costs=[],$is_submit=false)
    {
        try {
            DB::beginTransaction();
            $app = Application::find($data['application_id']);

                $app->draft_step_saved = $data['draft_step_saved'];
            if ($is_submit) {
               self::updateFlowApprovalStatus('submit', $data['application_id']);
            }
            $app->save();

            unset($data['draft_step_saved']);
            if ($app->detail?->exists) {
                $details = $app->detail()->update($data);
            }else{
                $details = $app->detail()->create($data);
            }


            self::clearData($app);


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

    public static function storeListLetterNumber($app){
        $fields_name = [
            [
            'letter_name'=>'mak',
            'letter_label'=>'MAK',
            'type_field'=>'text',
            'letter_number'=>null,
        ],
            [
            'letter_name'=>'nomor_sk',
            'letter_label'=>'Nomor Sk',
                'type_field' => 'text',

                'letter_number'=>null,
        ],
            [
            'letter_name'=>'tanggal_sk',
            'letter_label'=>'Tanggal SK',
                'type_field' => 'date',

                'letter_number'=>null,
        ],
            [
            'letter_name'=>'tanggal_berlaku_sk',
            'letter_label'=>'Tanggal Berlaku SK',
                'type_field' => 'date',

                'letter_number'=>null,
        ],
            [
            'letter_name'=>'nomor_surat_permohonan_speaker',
            'letter_label'=>'Nomor Surat Permohonan Narasumber/Moderator',
                'type_field' => 'text',

                'letter_number'=>null,
        ],
            [
            'letter_name'=>'nomor_surat_tugas',
            'letter_label'=>'Nomor Surat Tugas',
                'type_field' => 'text',

                'letter_number'=>null,
        ],
            [
            'letter_name'=>'nomor_surat_undangan_peserta',
                'letter_label'=>'Nomor Surat Undangan Peserta',
                'type_field' => 'text',

                'letter_number'=>null,
        ],
    ];

    foreach ($fields_name as $key => $value) {
        // dd([[...$value, 'department_id' => $app->department_id, 'application_id' => $app->id]]);
        try {
                $app->letterNumbers()->create(
                    [...$value, 'department_id' => $app->department_id, 'application_id' => $app->id]
                );
        } catch (\Throwable $th) {
            throw $th;
            dd($th);
        }
    }

    return true;

    }


    public static function clearData($app){
        // Menghapus semua peserta
        if ($app->participants->isNotEmpty()) {
            foreach ($app->participants as $participant) {
                $participant->delete();
            }
        }

        // Menghapus semua jadwal
        if ($app->schedules->isNotEmpty()) {
            foreach ($app->schedules as $schedule) {
                $schedule->delete();
            }
        }

        // Menghapus semua draftCostBudgets
        if ($app->draftCostBudgets->isNotEmpty()) {
            foreach ($app->draftCostBudgets as $draftCostBudget) {
                $draftCostBudget->delete();
            }
        }


        return true;
    }
    public static function updateLetterNumber($letterNumbers,$app){
        try {
            DB::beginTransaction();
            foreach ($letterNumbers as $key => $value) {
                $field = $app->letterNumbers()->find($value['id'])->update($value);
            }

            $app->update(['approval_status'=>12]);
            TemplateProcessorService::generateWord($app);
            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            dd($th);
            return false;
        }

        return true;
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
