<?php

namespace App\Services;

use App\Jobs\GenerateApplicationFileJob;
use App\Jobs\GenerateReportJob;
use App\Models\Application;
use App\Models\ApplicationDetail;
use App\Models\ApplicationDraftCostBudget;
use App\Models\ApplicationParticipant;
use App\Models\ApplicationSchedule;
use App\Models\Department;
use App\Models\FileType;
use App\Models\LogApproval;
use App\Models\ReportAttachment;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\TemplateProcessor;

class ApplicationService
{
    /**
     * Register services.
     *
     * @return void
     */
    public static $upload_rules = [
        'documentation_photos'=>[
                'old_props'=>'documentation_photos',
                'name'=>'documentation_photos',
                'is_required'=>false,
                'max_per_filesize'=>2048,
                'max_file'=>5,
                'accept'=>'.jpg,.jpeg,.png',
                'max_total_filesize'=>(5*2048),
                'current_file'=>0,
                'is_multiple'=>true,
                'elements'=>[]
        ],
        'minutes_file'=>[
                'name'=>'minutes_file',
                'is_required'=>false,
                'max_per_filesize'=>2048,
                'max_file'=>null,
                'accept'=>'.pdf',
                'max_total_filesize'=>(5*2048),
                'current_file'=>0,
                'is_multiple'=>true,
                'elements'=>[]
        ],
        'spj_file'=>[
                'name'=>'spj_file',
                'is_required'=>false,
                'max_per_filesize'=>2048,
                'max_file'=>null,
                'accept'=>'.pdf',
                'max_total_filesize'=>(10*2048),
                'current_file'=>0,
                'is_multiple'=>true,
                'elements'=>[]
        ],
        'attendence_files'=>[
                'name'=>'attendence_files',
                'is_required'=>false,
                'max_per_filesize'=>2048,
                'max_file'=>null,
                'accept'=>'.pdf',
                'max_total_filesize'=>(10*2048),
                'current_file'=>0,
                'is_multiple'=>true,
                'elements'=>[]
        ],
    ];



    public static function getListApp($search='',$status_approval='',$department_id='')
    {
        $qp_search = isset($search)?$search:null; 
        
        $applications = Application::when($qp_search, function($query) use($qp_search) {
                            $query->where('activity_name', 'like', '%'.$qp_search.'%');
                                if (strtolower(trim($qp_search)) === 'blu') {
                                    $query->orWhere('funding_source', 1);
                                } elseif (strtolower(trim($qp_search)) === 'boptn') {
                                    $query->orWhere('funding_source', 2);
                                }
                        })->when($department_id,function($query) use($department_id){
                            $query->where('department_id',$department_id);
                        })->when($status_approval,function($query) use($status_approval){
                        //     <select class="form-select form-select-sm" wire:model.live="status_approval">
                        // <option value="">Filter Status</option>
                        // <option value="need-my-process">Butuh proses saya</option>
                        // <option value="ongoing">Sedang Diproses</option>
                        // <option value="finished">Approved & Finish</option>
                        // <option value="need-revision">Butuh Revisi</option>
                        // <option value="rejected">Ditolak</option>
                    // </select>
                    switch ($status_approval) {
                        case 'need-my-process':
                            $query->whereNot('current_approval_status', 13)->whereHas('userApprovals',function($q){
                                $q->where('user_id',AuthService::currentAccess()['id'])
                                  ->whereColumn('sequence', 'applications.current_seq_user_approval');
                            });
                            break;
                        case 'ongoing':
                            // Contoh: status sedang diproses (misal status 7-10)
                            $query->whereBetween('current_approval_status', [1, 12]);
                            break;
                        case 'finished':
                            // Contoh: status approved & finish (misal status 11)
                            $query->where('current_approval_status', 13);
                            break;
                        case 'need-revision':
                            // Contoh: status butuh revisi (misal status 2)
                            $query->where('current_approval_status', 2);
                            break;
                        case 'rejected':
                            // Contoh: status ditolak (misal status 21)
                            $query->where('current_approval_status','>', 20);
                            break;
                    }
        })
        ->orderBy('created_at', 'desc');
                        // if ($search || $status_approval || $department_id) {
                        //     $applications = $applications->get();
                        // }else{
                        // }
                        
// dd($applications);
        return $applications;
    }
    public static function getList($department_id)
    {
        $apps = new Application();

        if ($department_id !== 0) {
            $apps = $apps->where('department_id',$department_id);
        }else{
            $list_department = MasterManagementService::getDepartmentList();
            $list_department_ids = array_filter($list_department,function($item){
                return $item['value'] !==0;
            });

            $list_department_ids =array_map(function($item){
                return $item['value'];
            },$list_department_ids);

            $apps = $apps->whereIn('department_id',$list_department_ids);
        }

        return $apps;
    }


    public static function getDashboardInformation($department_id)
    {
        $data = [
            'total_application'=>0,
            'rejected'=>0,
            'ongoing'=>0,
            'need_my_process'=>0,
            'approved'=>0,
        ];
       $data['total_application'] = self::getList($department_id)->get()->count();
       $data['rejected'] = self::getList($department_id)->where('current_approval_status','>',20)->orWhereHas('report',function($q){
            return $q->where('current_approval_status','>',20);
        })->get()->count()??0;

       $data['ongoing'] = self::getList($department_id)->where('current_approval_status','<',12)->orWhereHas('report',function($q){
            return $q->where('current_approval_status','<',11);
        })->get()->count()??0;


       $data['approved'] = self::getList($department_id)->whereHas('report', function($q) {
                $q->where('current_approval_status', '>', 10)
                ->where('current_approval_status', '<', 20);
            })->get()->count();
        return $data;
        
    }
    public static function storeApplications($data)
    {

        try {
            DB::beginTransaction();
            // Validate the data if necessary
                $get_verificators = MasterManagementService::generateUserProcessData();
                // dd($get_finance);
                $application = Application::create([
                    'activity_name'        => $data['activity_name'],
                    'funding_source'       => (int)$data['funding_source'],
                    'current_approval_status'      => 0,
                    'current_seq_user_approval' => 1,
                    'department_id'        => AuthService::currentAccess()['department_id'],
                    'created_by'           => AuthService::currentAccess()['id'],
                ]);
                

                $application->report()->create([
                    'department_id'=> AuthService::currentAccess()['department_id'],
                    'created_by'=> AuthService::currentAccess()['id'],
                ]);
                // Create ApplicationUserApproval records for each verifier
                foreach ($get_verificators as $key => $verifier) {
                    $verifier['application_id'] = $application->id;
                    $application->userApprovals()->create($verifier);
                }
            $exclude = ['surat_permohonan_moderator','surat_permohonan_narasumber','materi_narasumber','absensi_kehadiran','file_spj','notulensi'];
            $get_file_types = FileType::whereNotIn('code',$exclude)->get();
            foreach ($get_file_types as $ft) {
                $app_file = [
                    'display_name'=> $ft->name,
                    'seq'=> $ft->seq,
                    'file_type_id'=> $ft->id,
                    'order'=> $ft->order,
                    'department_id' => $application->department_id,
                ];
                $application->applicationFiles()->create($app_file);
            }
            DB::commit();
            return $application;
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);
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
                    if ($app->created_by == $current_user_id && $app->current_approval_status < 6) {
                        $update_current_appr = $app->currentUserApproval()->first();
                        $update_current_appr->status = 5;
                        $update_current_appr->save();


                        $app->current_seq_user_approval = 2;
                        $app->current_approval_status = 6;
                        $app->note = '';
                        $app->save();

                        $update_next_user_appr = $app->currentUserApproval()->first();
                        $update_next_user_appr->status = 6;
                        $update_next_user_appr->save();


                        self::storeLogApproval('approve', $application_id, '');
                        $app_file = $app->applicationFiles()->findCode('draft_tor')->first();
                        TemplateProcessorService::generateDocumentToPDF($app, 'draft_tor',$app);
                    }
                    break;
            case 'submit-report':
                    if ($app->created_by == $current_user_id && $app->current_approval_status < 6) {

                        $update_current_appr = $app->currentUserApproval()->first();
                        $update_current_appr->status = 5;
                        $update_current_appr->save();

                        $app->current_seq_user_approval = $app->current_seq_user_approval+1;
                        $app->current_approval_status = 6;
                        $app->save();

                        $update_next_user_appr = $app->currentUserApproval()->first();
                        $update_next_user_appr->status = 6;
                        $update_next_user_appr->save();

                        self::storeLogApproval($action, $application_id, '');
                    }
                    break;
            case 'approve':
                    if ($current_user_id ==  $app->currentUserApproval->user_id && $app->current_approval_status > 5 && $app->current_approval_status < 11) {
                        
                        $user_approvals = $app->userApprovals()->where('user_id', $current_user_id)->first();
                        $user_approvals->status = 11;
                        $user_approvals->save();

                        $update_current_appr = $app->currentUserApproval()->first();
                        $update_current_appr->status = 12;
                        $update_current_appr->save();


                        $current_seq =  $app->current_seq_user_approval;
                        $app->current_seq_user_approval = $current_seq+1;
                        $app->current_approval_status = 6;
                        $app->save();

                        // dd($app);

                        $max_seq = $app->userApprovals()->where('trans_type',1)->where('is_verificator',1)->max('sequence');
                        $approval_max_seq = $app->userApprovals()->where('sequence',$max_seq)->first();
                        if ($current_user_id == $approval_max_seq->user_id && $approval_max_seq->status > 10 && $approval_max_seq->status < 21) {
                         
                            $app->current_approval_status = 11;
                            $app->save();

                            $next_appr = $app->currentUserApproval()->first();
                            $next_appr->status = 11;
                            $next_appr->save();

                            
                            self::storeLogApproval($action,$application_id,'');
                            self::storeListLetterNumber($app);
                            self::storeSuratPermohonanFiles($app);
                        }

                    }
                    break;
            case 'approve-report':
                     if ($current_user_id ==  $app->currentUserApproval->user_id && $app->current_approval_status > 5 && $app->current_approval_status < 11) {
                        $max_seq = $app->userApprovals()->where('trans_type',2)->where('is_verificator',1)->max('sequence');
                        $approval_max_seq = $app->userApprovals()->where('sequence',$max_seq)->first();
                      
                        // dd($current_user_id ." == " .$approval_max_seq->user_id ." && ".$approval_max_seq->status ." > 5 && ".$approval_max_seq->status. ' < 21');
                        if ($current_user_id == $approval_max_seq->user_id && $approval_max_seq->status < 21) {
                        // if ($current_user_id == $approval_max_seq->user_id && $approval_max_seq->status > 10 && $approval_max_seq->status < 21) {
                            $app->current_approval_status = 13;
                            $app->save();

                            $department = Department::find($app->department_id);
                            $department->current_limit_submission = $department->current_limit_submission-1;
                            $department->save();

                            $next_appr = $app->currentUserApproval()->first();
                            $next_appr->status = 13;
                            $next_appr->save();

                            self::storeLogApproval($action, $application_id, '');
                            GenerateReportJob::dispatch($app);
                        }
                    }
                    break;
            case 'revise':
                    if ($current_user_id == $app->currentUserApproval->user_id && $app->current_approval_status > 5 && $app->current_approval_status < 11 ) {
                                                
                        foreach ($app->userApprovals()->where('trans_type',1)->where('is_verificator',1)->get() as $key => $approvers) {
                            $approvers->status = 0;
                            if ($app->currentUserApproval()->first()->sequence == $approvers->sequence) {
                                $approvers->note = $note;
                                $note_for_apps = $approvers->user_text."###".$note;
                            }
                            $approvers->updated_at = Carbon::now();
                            $approvers->save();
                        }
                        
                        $app->current_approval_status = 2;
                        $app->current_seq_user_approval = 1;
                        $app->note = $note_for_apps;
                        $app->updated_at = Carbon::now();
                        $app->save();

                        $update_current_appr = $app->currentUserApproval()->first();
                        $update_current_appr->status = 2;
                        $update_current_appr->updated_at = Carbon::now();
                        $update_current_appr->save();

                    }
                    break;
            case 'revise-report':
                    if ($current_user_id == $app->currentUserApproval->user_id && $app->current_approval_status > 5 && $app->current_approval_status < 11 ) {
                        foreach ($app->userApprovals()->where('trans_type',2)->where('is_verificator',1)->get() as $key => $approvers) {
                            $approvers->status = 0;
                            if ($app->currentUserApproval()->first()->sequence == $approvers->sequence) {
                                $approvers->note = $note;
                                $note_for_apps = $approvers->user_text."###".$note;
                            }
                            $approvers->updated_at = Carbon::now();
                            $approvers->save();
                        }
                        
                        $app->current_approval_status = 2;
                        $app->current_seq_user_approval = 5;
                        $app->note = $note_for_apps;
                        $app->updated_at = Carbon::now();
                        $app->save();

                        $update_current_appr = $app->currentUserApproval()->first();
                        $update_current_appr->status = 2;
                        $update_current_appr->updated_at = Carbon::now();
                        $update_current_appr->save();
                    }
                    break;
            case 'reject':
                    if ($current_user_id ==  $app->currentUserApproval->user_id && $app->current_approval_status > 5 && $app->current_approval_status < 11) {
                       

                         $update_current_appr = $app->currentUserApproval()->first();
                        $update_current_appr->status = 21;
                        $update_current_appr->note = $note;
                        $update_current_appr->updated_at = Carbon::now();
                        $update_current_appr->save();

                        $app->current_approval_status = 21;
                        $app->note = $update_current_appr->user_text."###".$note;
                        $app->save();
                    }

                    break;
            case 'reject-report':
                    if ($current_user_id ==  $app->currentUserApproval->user_id && $app->current_approval_status > 5 && $app->current_approval_status < 11) {
                        $update_current_appr = $app->currentUserApproval()->first();
                        $update_current_appr->status = 21;
                        $update_current_appr->note = $note;
                        $update_current_appr->updated_at = Carbon::now();
                        $update_current_appr->save();

                        $app->current_approval_status = 21;
                        $app->note = $update_current_appr->user_text."###".$note;
                        $app->save();
                    }

                    break;

            default:
                # code...
                break;
        }

            DB::commit();
            MasterManagementService::storeLogActivity($action,$application_id,$app->activity_name);
            return ['status'=>true,'message'=>'success update flow'];
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            Log::error($th);
            return ['status' => false, 'message' => 'Failed update flow'];
        }
    }


    public static function storeAttachmentToDetails($app,$is_regenerate=false){

        try {
    DB::beginTransaction(); // Mulai transaksi database

    if ($is_regenerate) {
        $attachment_details = $app->applicationFiles()->whereHas('fileType', function ($q) {
            return $q->whereIn('code', ['absensi_kehadiran', 'file_spj', 'notulensi']);
        })->get();
        foreach ($attachment_details as $key => $detail) {
            $detail->delete();
        }
    }

    $get_multiple_file = $app->report->attachments()->whereIn('type', ['attendence-files', 'spj-file', 'minutes-file'])->get();
    $attendance_type = FileType::whereCode('absensi_kehadiran')->first();
    $spj_type = FileType::whereCode('file_spj')->first();
    $minutes_type = FileType::whereCode('notulensi')->first();
    $participant_speakers = $app->participants()->whereNotNull('material_file_id')->get();

    foreach ($get_multiple_file ?? [] as $key => $attachment) {
        $ft = null;
        if ($attachment->type == 'attendence-files') {
            $ft = $attendance_type;
        } elseif ($attachment->type == 'spj-file') {
            $ft = $spj_type;
        } elseif ($attachment->type == 'minutes-file') {
            $ft = $minutes_type;
        }

        // Sanitasi dan limit panjang filename
        $raw_filename = $ft->name . ' - ' . $attachment->file->filename;
        $new_file_name = self::sanitizeFilename($raw_filename, 200);
        $data = [
            'display_name' => $new_file_name,
            'file_id' => $attachment->file_id,
            'order' => $ft->order,
            'status_ready' => 3,
            'participant_id' => null,
            'file_type_id' => $ft->id,
            'department_id' => $app->department_id,
        ];
        $app_file = $app->applicationFiles()->create($data);

        // start dari sini
        $file = $app_file->file()->first();
        $oldPath = $file->path; // contoh: 2025-9/2/report/spj-file/Ryu3aoEQUNFoFJMcF2SQKhHviz2Ac3doGtMC6TVY.png
        $fileContent = Storage::disk('minio')->get($oldPath);

        // Path baru, tetap di folder yang sama, hanya nama file diganti
        $newPath = dirname($oldPath) . '/' . $new_file_name; // hasil: 2025-9/2/report/spj-file/nama_baru.png
        if ($file->filename !== $new_file_name || $file->path !== dirname($oldPath)) {
            // Simpan file ke path baru
            Storage::disk('minio')->put($newPath, $fileContent);

            // Hapus file lama
            Storage::disk('minio')->delete($oldPath);

            // Update path di database
            $file->filename = $new_file_name;
            $file->path = $newPath;
            $file->save();
        }
    }

    if (count($participant_speakers) > 0) {
        foreach ($participant_speakers as $key => $par) {
            self::updateApplicationFilesReport($app, 'materi_narasumber', $par->material_file_id, $par->id);
        }
    }

    DB::commit(); // Commit transaksi jika semua berhasil
    return true;
} catch (\Throwable $th) {
    DB::rollBack(); // Rollback transaksi jika terjadi kesalahan
    Log::error('Error saat memproses attachment: ' . $th->getMessage(), ['exception' => $th]);
    throw $th; // Lempar ulang exception agar bisa ditangani di level yang lebih tinggi
}

    }
    public static function storeAttachmentToDetailsClose($app)
    {
        $attachments = $app->report->attachments()->whereIn('type', ['spj-file', 'minutes-file'])->get();
        $participant_speakers = $app->participants()->whereNotNull('material_file_id')->get();
        if (count($attachments) > 0) {
            foreach ($attachments as $key => $attachment) {
                switch ($attachment->type) {
                    case 'minutes-file':
                        $code = 'notulensi';
                        break;
                    case 'spj-file':
                        $code = 'file_spj';
                        break;
                    // case 'attendence-files':
                    //     $code = 'absensi_kehadiran';
                    //     break;
                    default:
                        $code = 'none';
                        break;
                }
                self::updateApplicationFilesReport($app,$code,$attachment->file_id,);
            }
        }

     
        if (count($participant_speakers) > 0) {
            foreach ($participant_speakers as $key => $par) {
                self::updateApplicationFilesReport($app,'materi_narasumber',$par->material_file_id,$par->id);
            }
        }

        
        return ['status' => true, 'message' => 'Berhasil memindahkan file ke detail'];

    }

    
    public static function updateApplicationFilesReport($app,$code,$file_id,$participant_id=null){
        $app_files = $app->applicationFiles()->whereHas('fileType', function ($q) use ($code) {
                    $q->where('code', $code);
                })
                ->when($participant_id, function($query) use ($participant_id) {
                    return $query->where('participant_id', $participant_id);
                })
                ->first();
                $app_files->status_ready = 3;
                $app_files->file_id = $file_id;
                $app_files->save();




                $file = $app_files->file()->first();
                $oldPath = $file->path; // contoh: 2025-9/2/report/spj-file/Ryu3aoEQUNFoFJMcF2SQKhHviz2Ac3doGtMC6TVY.png
                $fileContent = Storage::disk('minio')->get($oldPath);

                // Tentukan ekstensi dari mimetype
                $extension = match ($file->mimetype) {
                    'application/pdf' => 'pdf',
                    'image/png' => 'png',
                    'image/jpeg' => 'jpg',
                    default => 'dat',
                };
                
                // Path baru, tetap di folder yang sama, hanya nama file diganti
                $newFileName = $app_files->fileType->name . '-' . $app->activity_name . '-' . $app->id .(!empty($participant_id)?'-partid-'.$participant_id:null). '.' . $extension;
                $newPath = dirname($oldPath) . '/' . $newFileName; // hasil: 2025-9/2/report/spj-file/nama_baru.png
            if ($file->filename !== $newFileName || $file->path !== dirname($oldPath)) {
                // Simpan file ke path baru
                Storage::disk('minio')->put($newPath, $fileContent);

                // Hapus file lama
                Storage::disk('minio')->delete($oldPath);

                // Update path di database
                $file->filename = $newFileName;
                $file->path = $newPath;
                // $file->path = dirname($oldPath);
                $file->save();
            }
    }
    public static function storeSuratPermohonanFiles($app,$is_reset=false)
    {
        $surat_permohonan = FileType::whereIn('code',['surat_permohonan_narasumber','surat_permohonan_moderator'])->get();
        $materi = FileType::where('code','materi_narasumber')->first();
        $data = [];

        $materi_narasumbers = [];
        foreach ($surat_permohonan as $ft) {
                    $par_type = explode('_',$ft->code)[2];
                    $get_speaker = $app->participants()->whereHas('participantType',function($q) use ($par_type){
                        return $q->where('name',ucfirst($par_type));
                    })->get();
                    // dd($get_speaker);
                    foreach ($get_speaker as $key => $value) {
                        $app_file = [
                            'display_name'=> $ft->name.' - '.$value->name,
                            'order'=> $ft->order,
                            'participant_id'=> $value->id,
                            'file_type_id'    => $ft->id,
                            'department_id' => $app->department_id,
                        ];
                        // $data[]= $app_file;
                        $app->applicationFiles()->create($app_file);
                        if ($par_type == 'narasumber') {
                            $cp_app_file = $app_file;
                            $cp_app_file['display_name'] = $materi->name.' - '.$value->name;
                            $cp_app_file['file_type_id'] = $materi->id;
                            $cp_app_file['order'] = $materi->order;
                            $materi_narasumbers[]=$cp_app_file;
                            // $app->applicationFiles()->create($cp_app_file);
                        }
                    }
                } 

                foreach ($materi_narasumbers as $key => $app_file_materi) {
                    $app->applicationFiles()->create($app_file_materi);
                }
        return true;
    }

    /**
     * Validasi format activity_dates
     * Format yang valid: "01-10-2025" atau "01-10-2025, 02-10-2025"
     */
    private static function validateActivityDates($activity_dates)
    {
        if (empty($activity_dates)) {
            return ['valid' => false, 'message' => 'Tanggal kegiatan tidak boleh kosong'];
        }

        // Split berdasarkan koma untuk multiple dates
        $dates = explode(',', $activity_dates);

        // Pattern untuk format dd-mm-yyyy
        $pattern = '/^\d{2}-\d{2}-\d{4}$/';

        foreach ($dates as $date) {
            $date = trim($date);

            // Cek format menggunakan regex
            if (!preg_match($pattern, $date)) {
                return [
                    'valid' => false,
                    'message' => "Format tanggal kegiatan tidak valid. Gunakan format dd-mm-yyyy (contoh: 01-10-2025). Tanggal yang salah: {$date}"
                ];
            }

            // Validasi apakah tanggal valid (bukan 32-13-2025, dll)
            list($day, $month, $year) = explode('-', $date);
            if (!checkdate((int)$month, (int)$day, (int)$year)) {
                return [
                    'valid' => false,
                    'message' => "Tanggal tidak valid: {$date}. Pastikan tanggal sesuai dengan kalender"
                ];
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validasi timeline rundowns
     * - start_date harus sebelum atau sama dengan end_date untuk setiap item
     * - Rundowns tidak boleh bentrok antara satu dengan lainnya
     * - Rundowns harus urut berdasarkan waktu
     */
    private static function validateRundownsTimeline($rundowns)
    {
        if (empty($rundowns)) {
            return ['valid' => true, 'message' => ''];
        }

        $sortedRundowns = [];

        foreach ($rundowns as $index => $rundown) {
            // Pastikan start_date dan end_date ada
            if (empty($rundown['start_date']) || empty($rundown['end_date'])) {
                return [
                    'valid' => false,
                    'message' => "Rundown jadwal " . ($rundown['name']) . " harus memiliki tanggal mulai dan tanggal selesai"
                ];
            }

            try {
                $startDate = Carbon::parse($rundown['start_date']);
                $endDate = Carbon::parse($rundown['end_date']);

                // Validasi: start_date harus <= end_date
                if ($startDate->greaterThan($endDate)) {
                    return [
                        'valid' => false,
                        'message' => "Rundown jadwal " . ($rundown['name']) . ": Tanggal mulai ({$startDate->format('d-m-Y H:i')}) tidak boleh lebih besar dari tanggal selesai ({$endDate->format('d-m-Y H:i')})"
                    ];
                }

                $sortedRundowns[] = [
                    'index' => $index + 1,
                    'start' => $startDate,
                    'end' => $endDate,
                    'title' => $rundown['name'] ?? 'Tanpa judul'
                ];
            } catch (\Exception $e) {
                return [
                    'valid' => false,
                    'message' => "Format tanggal tidak valid pada rundown item #" . ($index + 1)
                ];
            }
        }

        // Sort berdasarkan start_date
        usort($sortedRundowns, function($a, $b) {
            return $a['start']->timestamp <=> $b['start']->timestamp;
        });

        // Validasi tidak ada bentrok antar rundowns
        for ($i = 0; $i < count($sortedRundowns) - 1; $i++) {
            $current = $sortedRundowns[$i];
            $next = $sortedRundowns[$i + 1];

            // Cek apakah rundown saat ini bentrok dengan rundown berikutnya
            // Bentrok jika: current.end > next.start
            if ($current['end']->greaterThan($next['start'])) {
                return [
                    'valid' => false,
                    'message' => "Rundown item #{$current['index']} ({$current['title']}) bentrok dengan item #{$next['index']} ({$next['title']}). " .
                                 "Item #{$current['index']} selesai pada {$current['end']->format('d-m-Y H:i')}, " .
                                 "sementara item #{$next['index']} dimulai pada {$next['start']->format('d-m-Y H:i')}"
                ];
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    public static function storeApplicationDetails($data,$participants=[],$rundowns=[], $draft_costs=[],$is_submit=false)
    {
        try {

            DB::beginTransaction();
            $app = Application::find($data['application_id']);

                $app->draft_step_saved = $data['draft_step_saved'];
            $app->save();

            unset($data['draft_step_saved']);
            // cleaning tanggal
            $data['activity_dates'] = !empty($data['activity_dates'])
                                    ? preg_replace('/\s*,\s*/', ',', trim($data['activity_dates']))
                                    : $data['activity_dates'];

            // Validasi jika is_submit true
            if ($is_submit) {
                // Validasi format activity_dates
                $dateValidation = self::validateActivityDates($data['activity_dates']);
                if (!$dateValidation['valid']) {
                    $is_submit = false;
                    DB::commit();
                    return ['status' => false, 'message' => $dateValidation['message']];
                }

                // Validasi rundowns timeline
                $rundownValidation = self::validateRundownsTimeline($rundowns);
                if (!$rundownValidation['valid']) {
                    $is_submit = false;
                    DB::commit();
                    return ['status' => false, 'message' => $rundownValidation['message']];
                }
            }

            if ($app->detail?->exists) {
                $details = $app->detail()->update($data);
            }else{
                $details = $app->detail()->create($data);
            }

            if ($app && $app->current_seq_user_approval > 3  && AuthService::currentAccess()['role']=='admin') {
                $files_with_participant = $app->applicationFiles()->whereNotNull('participant_id')->get();
                if (!empty($files_with_participant)) {
                   foreach ($files_with_participant as $key => $file) {
                       $file->delete();
                   }
                }
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

            if ($app && $app->current_seq_user_approval > 3 && AuthService::currentAccess()['role']=='admin') {
                self::storeSuratPermohonanFiles($app);
            }

            if ($is_submit) {
                self::updateFlowApprovalStatus('submit', $data['application_id']);
            }
            DB::commit();
            return['status'=>true,'message'=>'data pengajuan berhasil ditambahkan'];
        } catch (\Throwable $th) {
            DB::rollBack();
            // dd($th);
            throw $th;
        }

    }
    public static function storeReport($data,$realization=[],$speakers_info=[],$is_submit=false)
    {
        try {
            DB::beginTransaction();
            $app = Application::find($data['application_id']);
            $reports = $app->report->update($data);

            // dd($data['attachments'],$speakers_info,$realization);

            // dd($reports,$temp,$realization);
            // store file id ke tabel draft_cost_application
            foreach ($realization as $key => $value) {
                    $draf_cost= ApplicationDraftCostBudget::find($value['id']);

                    $draf_cost->realization = $value['realization'];
                    $draf_cost->volume_realization = $value['volume_realization'];
                    $draf_cost->unit_cost_realization = $value['unit_cost_realization'];
                    $draf_cost->save();

                  if (isset($value['file_id']) && !empty($value['file_id'])) {
                    $new_dir = str_replace('temp/report/', '', $value['file_id']);
                    $get_file_storage = FileManagementService::getFileStorage($value['file_id'],$app,$new_dir,'report');
                    $files = FileManagementService::storeFiles($get_file_storage,$app,'report', $value['file_id']);
                    $draf_cost->files()->attach($files['data']->id);
                 }

            }

            // foreach ($data['attachments']??[] as $att_type) {
            // //    dd(Storage::disk('minio')->files($att_type['file_path']),$att_type['file_path']);
            //     if (Storage::disk('minio')->exists($att_type['file_path'])) {
            //         $get_dir_files = Storage::disk('minio')->files($att_type['file_path']); // Mendapatkan semua file dalam folder
            //         foreach ($get_dir_files as $dir_file) {
            //             $new_dir = str_replace('temp/report/'.$app->id.'/', '', $dir_file);
            //             $get_file_storage = FileManagementService::getFileStorage($dir_file,$app,$new_dir,'report');
            //             $file = FileManagementService::storeFiles($get_file_storage,$app,'report', $dir_file);
                       
            //             if ($file['status']) {
            //                 $arr = [
            //                     'file_id'=>$file['data']->id,
            //                     'reference_id'=>null,
            //                     'application_report_id'=>$att_type['application_report_id'],
            //                     'type'=>$att_type['type']
            //                 ];
            //                 $app->report->attachments()->create($arr);
            //             } 
            //         }
            // }

            //     # code...
            // }

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
            // dd($th);
            throw $th;
        }

    }
    

    public static function storeDocSpeaker(Request $request){
         try {
            DB::beginTransaction();
            $participant_id = explode('_',$request['participant_data'])[0]; // Ambil ID peserta
            $participant= ApplicationParticipant::find($participant_id);
            $app = Application::find($participant->application_id);

            $files = [
                'cv_file_id' => $request['cv_file_id'],
                'idcard_file_id' => $request['idcard_file_id'],
                'npwp_file_id' => $request['npwp_file_id'],
                'material_file_id' => $request['material_file_id']
            ];
            $directory = 'speaker-information/' . $participant_id;

            Log::info('Masok ke fungsi =>',['$key'=>'yessss']);
            foreach ($files as $key => $file) {
                Log::info('Masok ke loop fiiles =>',['$key'=>$key]);
                if ($file instanceof UploadedFile) {
                        Log::info('Masok ke upload =>',['$key'=>$key]);
                    $fileName = $file->getClientOriginalName().'-'.$participant_id . '-' . $key . '.' . $file->getClientOriginalExtension();
                        $path = $directory.'/'.$fileName;
                        // Simpan file ke MinIO
                        $file->storeAs($directory, $fileName, 'minio');

                        $get_file_storage = FileManagementService::getFileStorage($path,$app,$path,'report');
                                    $files = FileManagementService::storeFiles($get_file_storage,$app,'report');
                                if ($files['status']) {
                                    $participant->$key = $files['data']?->id;
                                }
                                $participant->save();
                }
            }
        DB::commit();
        return['status'=>true,'message'=>'Data Personal berhasil ditambahkan'];
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::info('Gagal Nambah Data Personel Baru',['err'=>$th]);
            return['status'=>true,'message'=>'Data Personal berhasil ditambahkan'];
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
            'letter_name'=>'keterangan_mak',
            'letter_label'=>'Poin No 7 SK terkait MAK',
            'type_field'=>'textarea',
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
            'letter_label'=>'Nomor Surat Tugas Narasumber/Moderator',
                'type_field' => 'text',
                'is_with_date' => 1,
                'letter_number'=>null,
        ],
        [
            'letter_name'=>'nomor_surat_tugas_peserta',
            'letter_label'=>'Nomor Surat Tugas Peserta',
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
    public static function updateLetterNumber($letterNumbers,$app,$is_submit=true){
        try {
            DB::beginTransaction();
            foreach ($letterNumbers as $key => $value) {
                $field = $app->letterNumbers()->find($value['id'])->update($value);
            }
            if ($is_submit) {
                $update_current_seq = $app->currentUserApproval()->first();
                $update_current_seq->status = 13;
                $update_current_seq->save();

                $app->update(['current_approval_status'=>1,'current_seq_user_approval'=>($app->current_seq_user_approval+1)]);


                $update_next_seq = $app->currentUserApproval()->first();
                $update_next_seq->status = $app->current_approval_status;
                $update_next_seq->save();

                $department = Department::find($app->department_id);
                $department->current_limit_submission = $department->current_limit_submission+1;
                $department->save();
                GenerateApplicationFileJob::dispatch($app);
                MasterManagementService::storeLogActivity('submit-letter-number',$app->id,$app->name);

            }

            // TemplateProcessorService::generateApplicationDocument($app);
            DB::commit();
            return true;
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return false;
        }

    }

public static function getListReport($search = '', $status_approval = '', $department_id = '')
{
    $qp_search = isset($search) ? $search : null;

    $list = Application::whereHas('userApprovals', function ($q) {
            $q->where('trans_type', 2)
              ->whereColumn('sequence', 'applications.current_seq_user_approval');
        })
        ->when($qp_search, function ($query) use ($qp_search) {
            $query->where('activity_name', 'like', '%' . $qp_search . '%');
            if (strtolower(trim($qp_search)) === 'blu') {
                $query->orWhere('funding_source', 1);
            } elseif (strtolower(trim($qp_search)) === 'boptn') {
                $query->orWhere('funding_source', 2);
            }
        })
        ->when($department_id, function ($query) use ($department_id) {
            $query->where('department_id', $department_id);
        })
        ->when($status_approval, function ($query) use ($status_approval) {
            switch ($status_approval) {
                case 'need-my-process':
                    $query->whereNot('current_approval_status', 13)
                        ->whereHas('userApprovals', function ($q) {
                            $q->where('user_id', AuthService::currentAccess()['id'])
                              ->whereColumn('sequence', 'applications.current_seq_user_approval');
                        });
                    break;
                case 'ongoing':
                    $query->whereBetween('current_approval_status', [1, 12]);
                    break;
                case 'finished':
                    $query->where('current_approval_status', 13);
                    break;
                case 'need-revision':
                    $query->where('current_approval_status', 2);
                    break;
                case 'rejected':
                    $query->where('current_approval_status', '>', 20);
                    break;
            }
        })
        ->orderBy('created_at', 'desc');

    return $list;
}

    public static function destroyRecursive($id){
    try {
        DB::beginTransaction();
        $app = Application::find($id);
        if (!$app) {
            DB::rollBack();
            return ['status' => false, 'message' => 'Data tidak ditemukan'];
        }
        // Hapus semua relasi
        // Hapus detail
        if ($app->detail) {
            $app->detail->delete();
        }
        // Hapus draft cost budgets
        foreach ($app->draftCostBudgets as $draftCostBudget) {
            $draftCostBudget->delete();
        }
        // Hapus letter numbers
        foreach ($app->letterNumbers as $letterNumber) {
            $letterNumber->delete();
        }
        // Hapus participants
        foreach ($app->participants as $participant) {
            $participant->delete();
        }
        // Hapus report dan relasi attachments
        if ($app->report) {
            foreach ($app->report->attachments as $attachment) {
                $attachment->delete();
            }
            $app->report->delete();
        }
        // Hapus schedules
        foreach ($app->schedules as $schedule) {
            $schedule->delete();
        }
        // Hapus user approvals
        foreach ($app->userApprovals as $userApproval) {
            $userApproval->delete();
        }
        // Hapus application files
        if (!empty($app->applicationFiles)) {
            foreach ($app->applicationFiles as $file) {
                $file->delete();
            }
        }

        // Terakhir, hapus aplikasi utama
        $app->delete();
        DB::commit();
        return ['status' => true, 'message' => 'Data dan seluruh detail berhasil dihapus'];
    } catch (\Throwable $th) {
        DB::rollBack();
        Log::error($th);
        return ['status' => false, 'message' => 'Gagal menghapus data: ' . $th->getMessage()];
    }
}
    public static function submitReport($metadata_files,$application,$report){

  
    // Validasi file dengan ukuran maksimal 10MB untuk semua file
    // $request->validate([
    //     'spj_file.*' => 'nullable|file|max:10240', // Maksimal 10MB per file
    //     'minutes_file.*' => 'nullable|file|max:10240', // Maksimal 10MB per file
    //     'attendence_files.*' => 'nullable|file|max:10240', // Maksimal 10MB per file
    //     'documentation_photos.*' => 'nullable|file|max:10240', // Maksimal 10MB per file
    // ], [
    //     'spj_file.*.max' => 'Setiap file SPJ tidak boleh lebih dari 10MB.',
    //     'minutes_file.*.max' => 'Setiap file Minutes tidak boleh lebih dari 10MB.',
    //     'attendence_files.*.max' => 'Setiap file absensi tidak boleh lebih dari 10MB.',
    //     'documentation_photos.*.max' => 'Setiap foto dokumentasi tidak boleh lebih dari 10MB.',
    // ]);

    try {
        // Proses file SPJ
        DB::beginTransaction();
              foreach ($metadata_files??[] as $att_type) {
            //    dd(Storage::disk('minio')->files($att_type['file_path']),$att_type['file_path']);
                if (Storage::disk('minio')->exists($att_type['file_path'])) {
                    $get_dir_files = Storage::disk('minio')->files($att_type['file_path']); // Mendapatkan semua file dalam folder
                    foreach ($get_dir_files as $dir_file) {
                        $new_dir =  $dir_file;
                        $get_file_storage = FileManagementService::getFileStorage($dir_file,$application,$new_dir,'report');
                        $file = FileManagementService::storeFiles($get_file_storage,$application,'report', $dir_file);
                        if ($file['status']) {
                            $arr = [
                                'file_id'=>$file['data']->id,
                                'reference_id'=>null,
                                'application_report_id'=>$att_type['application_report_id'],
                                'type'=>$att_type['type']
                            ];
                            $report->attachments()->create($arr);
                        } 
                    }
            }

                # code...
            }

        DB::commit();
        // Redirect dengan pesan sukses
        // return redirect()->back()->with('success', 'Laporan berhasil disubmit.');
        return ['status'=>true,'message'=>'Laporan berhasil disubmit.'];
    } catch (\Exception $e) {
        DB::rollback();
        dd($e);
        // Tangani error dan log pesan error
        Log::error('Error saat submit laporan: ' . $e->getMessage());
        return ['status'=>false,'message'=>'Laporan gagal disubmit.'];

    }
    }
    public static function hasDepartmentQuota(){
        $department = Department::find(AuthService::currentAccess()['department_id']);
        $quota_remaining = $department->limit_submission - $department->current_limit_submission;
        if ($quota_remaining > 0) {
            return true;
        }
        return false;
    }
    public static function regenerateReport($app){
         // $attachment_details = ApplicationFile::whereHas('fileType',function($q){
            //     return $q->whereIn('code',['absensi_kehadiran','file_spj','notulensi']);
            // })->get();
        GenerateReportJob::dispatch($app,true);
    }

    

    public static function destroyAttachments($file_id, $related_table = [])
    {
        try {
            DB::beginTransaction(); // Mulai transaksi database

            $file = FileManagementService::find($file_id);
            foreach ($related_table as $key => $table_name) {
                switch ($table_name) {
                    case 'report_attachments':
                        $file_item = ReportAttachment::where('file_id', $file_id)->first();
                            $file_item->delete();
                        break;

                    case 'draft_cost_budget_file':
                        // Tambahkan logika untuk draft_cost_budget_file jika diperlukan
                        break;
                    case 'application_participants':
                        // Tambahkan logika untuk draft_cost_budget_file jika diperlukan
                        $participant = ApplicationParticipant::where('cv_file_id',$file_id)
                                                                ->orWhere('npwp_file_id',$file_id)
                                                                ->orWhere('idcard_file_id',$file_id)
                                                                ->orWhere('material_file_id',$file_id)
                                                                ->first();
                         if ($participant) {
                                // Array kolom yang akan dicek
                                $file_columns = ['cv_file_id', 'npwp_file_id', 'idcard_file_id', 'material_file_id'];

                                // Cari kolom mana yang berisi file_id yang akan dihapus
                                foreach ($file_columns as $column) {
                                    if ($participant->$column == $file_id) {
                                        // Update kolom tersebut menjadi null
                                        $participant->$column = null;
                                        $participant->save();

                                        Log::info("Kolom {$column} berhasil diupdate menjadi null untuk participant ID {$participant->id}");
                                        break; // Keluar dari loop setelah menemukan kolom yang sesuai
                                    }
                                }
                            }

                        break;
                    default:
                        // Jika tabel tidak dikenali, tambahkan log atau abaikan
                        Log::warning("Unknown table name '{$table_name}' in destroyAttachments.");
                        break;
                }
            }

            if (!empty($file)) {
                $path = $file->path;
                if ($file->delete()) {
                    Storage::disk('minio')->delete($path);
                    Log::info("File berhasil dihapus dari database dan MinIO.", ['file_path' => $file->path]);
                } else {
                    Log::warning("File gagal dihapus dari database.", ['file_id' => $file->id]);
                }
            }

            DB::commit(); // Commit transaksi jika semua berhasil
            return ['status' => true, 'message' => 'File berhasil dihapus.'];
        } catch (\Throwable $th) {
            DB::rollBack(); // Rollback transaksi jika terjadi kesalahan
            Log::error('Error saat menghapus file dan relasi: ' . $th->getMessage(), [
                'file_id' => $file_id,
                'related_table' => $related_table,
            ]);
            return ['status' => false, 'message' => 'Gagal menghapus file.'];
        }
    }

    /**
     * Sanitize filename untuk menghindari karakter ilegal dan limit panjang
     *
     * @param string $filename
     * @param int $maxLength
     * @return string
     */
    private static function sanitizeFilename($filename, $maxLength = 200)
    {
        // Karakter ilegal untuk filename di Windows dan Linux
        $illegalChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|'];

        // Replace karakter ilegal dengan underscore
        $sanitized = str_replace($illegalChars, '_', $filename);

        // Remove multiple spaces dan trim
        $sanitized = preg_replace('/\s+/', ' ', $sanitized);
        $sanitized = trim($sanitized);

        // Pisahkan nama file dan ekstensi
        $pathInfo = pathinfo($sanitized);
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';
        $nameOnly = $pathInfo['filename'];

        // Hitung panjang maksimal untuk nama file (tanpa ekstensi)
        $maxNameLength = $maxLength - strlen($extension);

        // Trim nama file jika terlalu panjang
        if (strlen($nameOnly) > $maxNameLength) {
            $nameOnly = substr($nameOnly, 0, $maxNameLength);
        }

        return $nameOnly . $extension;
    }
}
