<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDetail;
use App\Models\ApplicationDraftCostBudget;
use App\Models\ApplicationParticipant;
use App\Models\ApplicationSchedule;
use App\Models\Department;
use App\Models\FileType;
use App\Models\LogApproval;
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

        try {
            DB::beginTransaction();
            // Validate the data if necessary
                $get_verificators = User::approvers()->whereIn('id', $data['verificators'])->get();
                $get_finance = User::approvers()->rolePosition('finance',AuthService::currentAccess()['department_id'])->first();
                $get_dekan = User::approvers()->rolePosition('dekan',AuthService::currentAccess()['department_id'])->first();
                // dd($get_finance);
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
                    'current_user_approval'=> $get_dekan->id,
                    'approval_status'=> 0,
                    'department_id'=> AuthService::currentAccess()['department_id'],
                ]);
                // Create ApplicationUserApproval records for each verifier
                foreach ($get_verificators as $key => $verifier) {

                        if ($verifier->position->hasRole('finance')) {
                            $sequence = 1;
                        }else if($verifier->position->hasRole('dekan')){
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
            $get_file_types = FileType::whereNotIn('code',['surat_permohonan_moderator','surat_permohonan_narasumber','surat_permohonan'])->get();
            foreach ($get_file_types as $ft) {
                $app_file = [
                    'display_name'=> $ft->name,
                    'seq'=> $ft->seq,
                    'file_type_id'=> $ft->id,
                    'department_id' => $application->department_id,
                ];
                $application->applicationFiles()->create($app_file);
            }
            DB::commit();
            return $application;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }


        // return $application;
    }




    public static function storeLogApproval($action, $application_id,$note='')
    {
        $split_action = explode('-',$action);
        $log = LogApproval::create([
            'notes'=>$note,
            'location_city'=>'Yogyakarta',
            'action'=>$split_action[0],
            'trans_type'=>strpos($action, 'report')?2:1,
            'application_id'=> $application_id,
            'user_id'=> AuthService::currentAccess()['id'],
            'position_id'=> AuthService::currentAccess()['position_id'],
            'department_id'=> AuthService::currentAccess()['department_id']
        ]);

        // dd($log);

        if (!$log) {
           return ['status'=>false,'message'=>'gagal menambahkan riwayat approval !'];
        }
        return ['status' => true, 'message' => 'Berhasil menambahkan riwayat approval !'];
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
                        self::storeLogApproval('approve', $application_id, '');
                        $app_file = $app->applicationFiles()->findCode('draft_tor')->first();
                        TemplateProcessorService::generateDocumentToPDF($app, 'draft_tor',$app);
                    }
                    break;
            case 'submit-report':
                    if ($app->created_by == $current_user_id && $app->report->approval_status < 6) {
                        $app->report->approval_status = 6 ;
                        $app->report->save();
                        self::storeLogApproval($action, $application_id, '');
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
                            self::storeLogApproval($action,$application_id,'');
                            self::storeListLetterNumber($app);
                            self::storeSuratPermohonanFiles($app);
                      
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
                            self::storeLogApproval($action, $application_id, '');

                            $app_file = $app->applicationFiles()->findCode('laporan_kegiatan')->first();
                            TemplateProcessorService::generateDocumentToPDF($app, 'laporan_kegiatan',$app_file);
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


    public static function storeSuratPermohonanFiles($app)
    {
        $surat_permohonan = FileType::whereIn('code',['surat_permohonan_narasumber','surat_permohonan_moderator'])->get();
        $data = [];
        foreach ($surat_permohonan as $ft) {
                    $par_type = explode('_',$ft->code)[2];
                    $get_speaker = $app->participants()->whereHas('participantType',function($q) use ($par_type){
                        return $q->where('name',ucfirst($par_type));
                    })->get();
                    // dd($get_speaker);
                    foreach ($get_speaker as $key => $value) {
                        $app_file = [
                            'display_name'=> $ft->name.' - '.$value->name,
                            'participant_id'=> $value->id,
                            'file_type_id'    => $ft->id,
                            'department_id' => $app->department_id,
                        ];
                        // $data[]= $app_file;
                        $app->applicationFiles()->create($app_file);
                    }
                } 
                // dd($data);
        return true; 
    }
    public static function storeApplicationDetails($data,$participants=[],$rundowns=[], $draft_costs=[],$is_submit=false)
    {
        // dd($rundowns, $participants);
        try {
            DB::beginTransaction();
            $app = Application::find($data['application_id']);

                $app->draft_step_saved = $data['draft_step_saved'];
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
                    $value['department_id']= $app->department_id;
                    $value['application_id']= $app->id;
                    $rundown = ApplicationSchedule::updateOrCreate($value);
                }
                foreach ($draft_costs as $key => $value) {
                    $value['volume_realization'] = $value['volume'];
                    $value['unit_cost_realization'] = $value['cost_per_unit'];
                    $draft_cost = ApplicationDraftCostBudget::updateOrCreate($value);
                }

            if ($is_submit) {
                self::updateFlowApprovalStatus('submit', $data['application_id']);
            }
            DB::commit();
            return['status'=>true,'message'=>'data pengajuan berhasil ditambahkan'];
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            // throw $th;
        }

    }
    public static function storeReport($data,$realization=[],$speakers_info=[],$is_submit=false)
    {
        try {
            DB::beginTransaction();
            $app = Application::find($data['application_id']);
            $reports = $app->report->update($data);



            // store file ke minio dan tambah ke table files
            $temp=[];
            foreach ($speakers_info as $key => $spk) {

                    $columns = ['cv_file_id','idcard_file_id','npwp_file_id'];
                    foreach ($columns as $col) {
                        if ($spk[$col]) {
                            $new_dir = str_replace('temp/report/', '', $spk[$col]);
                            $get_file_storage = FileManagementService::getFileStorage($spk[$col],$app,$new_dir,'report');
                            $files = FileManagementService::storeFiles($get_file_storage,$app,'report',$spk[$col]);

                            //  $temp[$spk['participant_id']][] = [$col=>$files,''];

                        $participant= ApplicationParticipant::find($spk['participant_id']);
                            $participant->$col = $files['data']->id;

                            $participant->save();
                            $temp[]=$participant;
                        }
                    }

            }
            // dd($reports,$temp,$realization);
            // store file id ke tabel draft_cost_application
            foreach ($realization as $key => $value) {
                if ($value['file_id']) {
                    $new_dir = str_replace('temp/report/', '', $value['file_id']);
                    $get_file_storage = FileManagementService::getFileStorage($value['file_id'],$app,$new_dir,'report');

                    $files = FileManagementService::storeFiles($get_file_storage,$app,'report', $value['file_id']);
                    $draf_cost= ApplicationDraftCostBudget::find($value['id']);
                    $draf_cost->realization = $value['realization'];
                    $draf_cost->volume_realization = $value['volume_realization'];
                    $draf_cost->unit_cost_realization = $value['unit_cost_realization'];
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

            if ($is_submit) {
                self::updateFlowApprovalStatus('submit-report', $data['application_id']);
            }
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
                'is_with_date' => 0,
                'letter_number'=>null,
        ],
            [
            'letter_name'=>'nomor_sk',
            'letter_label'=>'Nomor Sk',
                'type_field' => 'text',

                'letter_number'=>null,
                'is_with_date'=>1,
        ],

            [
            'letter_name'=>'tanggal_berlaku_sk',
            'letter_label'=>'Tanggal Berlaku SK',
                'type_field' => 'date',
                'is_with_date' => 0,
                'letter_number'=>null,
        ],
            [
            'letter_name'=>'nomor_surat_permohonan',
            'letter_label'=>'Nomor Surat Permohonan Narasumber/Moderator',
                'type_field' => 'text',
                'is_with_date' => 1,
                'letter_number'=>null,
        ],
            [
            'letter_name'=>'nomor_surat_tugas',
            'letter_label'=>'Nomor Surat Tugas',
                'type_field' => 'text',
                'is_with_date' => 1,
                'letter_number'=>null,
        ],
            [
            'letter_name'=>'nomor_surat_undangan_peserta',
                'letter_label'=>'Nomor Surat Undangan Peserta',
                'type_field' => 'text',
                'is_with_date' => 1,
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

            // dd($letterNumbers);
            foreach ($letterNumbers as $key => $value) {
                $field = $app->letterNumbers()->find($value['id'])->update($value);
            }

            $app->update(['approval_status'=>12]);

            $department = Department::find($app->department_id);
            $department->current_limit_submission = $department->current_limit_submission+1;
            $department->save();
            TemplateProcessorService::generateApplicationDocument($app);
            // TemplateProcessorService::generateDocumentToPDF($app, 'sk');
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
