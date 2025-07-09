<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDetail;
use App\Models\ApplicationDraftCostBudget;
use App\Models\ApplicationParticipant;
use App\Models\ApplicationSchedule;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use App\Services\AuthService;
use Carbon\Carbon;
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

                $application->report()->create([
                    'current_user_approval'=> $get_finance->id,
                    'approval_status'=> 0,
                    'department_id'=> AuthService::currentAccess()['department_id'],
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
                        'report_status' => 0, // Assuming 0 means pending
                        'application_id' => $application->id,
                        'department_id' => AuthService::currentAccess()['department_id'], // Assuming the department ID is from the current user
                        'created_by' => Auth::id(), // Assuming you want to store who created this approval
                    ]);
                }

            $application_files = [
                'Draft TOR',
                'TOR',
                'SK',
                'LPJ',
                'Surat Permohonan Narsum',
                'Surat Permohonan Moderator',
                'Surat Undangan Peserta',
                'Surat Tugas',
                'Jadwal Kegiatan',
            ];
            foreach ($application_files as $name) {
                $application->applicationFiles()->create( [
                    'type_name'    => $name,
                    'code'         => strtolower(str_replace(' ', '_', $name)),
                    'trans_type'   => $name !== 'LPJ'?1:2,
                    'department_id' => $application->department_id,
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
            case 'submit-report':
                    if ($app->created_by == $current_user_id && $app->report->approval_status < 6) {
                        $app->report->approval_status = 6 ;
                        $app->report->save();
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
            case 'approve-report':
                    if ($current_user_id ==  $app->report->current_user_approval && $app->report->approval_status > 5 && $app->report->approval_status < 11) {
                        $user_approvals = $app->userApprovals()->where('user_id', $current_user_id)->first();
                        $user_approvals->report_status = 11;
                        $user_approvals->save();

                        $max_seq = $app->userApprovals()->max('sequence');
                        $approval_max_seq = $app->userApprovals()->where('sequence',$max_seq)->first();
                        if ( $current_user_id == $approval_max_seq->user_id && $approval_max_seq->report_status > 10 && $approval_max_seq->report_status < 21) {
                            $app->report->approval_status = 11;
                            $app->report->save();

                            $department = Department::find($app->department_id);
                            $department->current_limit_submission = $department->current_limit_submission-1;
                            $department->save();

                            // self::storeListLetterNumber($app);
                        }else{
                            $current_user = $app->userApprovals()->where('user_id',$app->report->current_user_approval)->first();
                            $next_user = $app->userApprovals()->where('sequence', $current_user->sequence+1)->first();
                            $app->report->current_user_approval = $next_user->user_id;
                            $app->report->save();
                        }

                    }
                    break;
            case 'revise':
                    if ($current_user_id == $app->current_user_approval && $app->approval_status > 5 && $app->approval_status < 11 ) {
                        $app->approval_status = 2;
                        $app->note = $note;
                        $app->updated_at = Carbon::now();
                        $app->save();

                        $user_approvals = $app->userApprovals()->where('user_id', $current_user_id)->first();
                        $user_approvals->status = 2;
                        $user_approvals->note = $note;
                        $user_approvals->updated_at = Carbon::now();
                        $user_approvals->save();
                    }
                    break;
            case 'revise-report':
                    if ($current_user_id == $app->report->current_user_approval && $app->report->approval_status > 5 && $app->report->approval_status < 11 ) {
                        $app->approval_status = 2;
                        $app->report()->note = $note;
                        $app->report()->updated_at = Carbon::now();
                        $app->save();

                        $user_approvals = $app->userApprovals()->where('user_id', $current_user_id)->first();
                        $user_approvals->report_status = 2;
                        $user_approvals->report_note = $note;
                        $user_approvals->updated_at = Carbon::now();
                        $user_approvals->save();
                    }
                    break;
            case 'reject':
                    if ($current_user_id ==  $app->current_user_approval && $app->approval_status > 5 && $app->approval_status < 11) {
                        $app->report()->approval_status = 21;
                        $app->report()->note = $note;
                        $app->save();


                        $user_approvals = $app->userApprovals()->where('user_id', $current_user_id)->first();
                        $user_approvals->report_status = 21;
                        $user_approvals->report_note = $note;
                        $user_approvals->updated_at = Carbon::now();
                        $user_approvals->save();
                    }

                    break;
            case 'reject-report':
                    if ($current_user_id ==  $app->current_user_approval && $app->approval_status > 5 && $app->approval_status < 11) {
                        $app->approval_status = 21;
                        $app->note = $note;
                        $app->save();


                        $user_approvals = $app->userApprovals()->where('user_id', $current_user_id)->first();
                        $user_approvals->status = 21;
                        $user_approvals->note = $note;
                        $user_approvals->updated_at = Carbon::now();
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
    public static function storeReport($data,$realization=[],$speakers_info=[])
    {
        // dd($speakers_info);
        try {
            DB::beginTransaction();
            $app = Application::find($data['application_id']);
            $reports = $app->report->update($data);

            // store file ke minio dan tambah ke table files
            foreach ($speakers_info as $key => $spk) {
                    // "participant_id" => 1
                    // "cv_file_id" => "temp/report/speaker-information/1/application.pdf-1-cv_file_id.pdf"
                    // "idcard_file_id" => "temp/report/speaker-information/1/tandaterima_disposisi_168_1742660389.pdf-1-idcard_file_id.pdf"
                    // "npwp_file_id" => "temp/report/speaker-information/1/dummy.pdf-1-npwp_file_id.pdf"
                    $columns = ['cv_file_id','idcard_file_id','npwp_file_id'];
                    foreach ($columns as $col) {
                        if ($spk[$col]) {
                            $new_dir = str_replace('temp/report/', '', $spk[$col]);
                            $get_file_storage = FileManagementService::getFileStorage($spk[$col],$app,$new_dir,'report');
                            $files = FileManagementService::storeFiles($get_file_storage,$app,'report',$spk[$col]);
                            $participant= ApplicationParticipant::find($spk['participant_id']);
                            $participant->$col = $files['data']->id;
                            $participant->save();


                        }
                    }

            }
            // store file id ke tabel draft_cost_application
            foreach ($realization as $key => $value) {
                if ($value['file_id']) {
                    $new_dir = str_replace('temp/report/', '', $value['file_id']);
                    $get_file_storage = FileManagementService::getFileStorage($value['file_id'],$app,$new_dir,'report');

                    $files = FileManagementService::storeFiles($get_file_storage,$app,'report', $value['file_id']);
                    $draf_cost= ApplicationDraftCostBudget::find($value['id']);
                    $draf_cost->realization = $value['realization'];
                    $draf_cost->save();
                    $draf_cost->files()->attach($files['data']->id);
                }
            }


            // foreach ($speakers_info as $key => $value) {
            //     $new_dir = str_replace('temp/report/', '', $path);
            //     $get_file_storage = FileManagementService::getFileStorage($value['file_id'],$app,$new_dir,'report');

            //     $files = FileManagementService::storeFiles($get_file_storage,$app,'report');
            //     $draf_cost= ApplicationDraftCostBudget::find($value['id']);
            //     $draf_cost->files()->attach($files->id);
            // }



            // if (!$reports) {
            //         return ['status' => false, 'message' => 'data LPJ Gagal ditambahkan'];
            //     }
            self::updateFlowApprovalStatus('submit-report', $data['application_id']);
            DB::commit();
            return['status'=>true,'message'=>'data LPJ berhasil ditambahkan'];
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

            $department = Department::find($app->department_id);
            $department->current_limit_submission = $department->current_limit_submission+1;
            $department->save();
            TemplateProcessorService::generateWord($app);
            DB::commit();
            return true;
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            dd($th);
            return false;
        }

    }

    public static function getListReport(){
       $list = Application::where('approval_status',12)->get();
       return $list;
    }
    public static function hasDepartmentQuota(){
        $department = Department::find(AuthService::currentAccess()['department_id']);
        $quota_remaining = $department->limit_submission - $department->current_limit_submission;
        if ($quota_remaining > 0) {
            return true;
        }
        return false;
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
