<?php

namespace App\Services;

use App\Models\Application;
use App\Models\ApplicationDetail;
use App\Models\ApplicationDraftCostBudget;
use App\Models\ApplicationLetterNumber;
use App\Models\ApplicationParticipant;
use App\Models\ApplicationSchedule;
use App\Models\Department;
use App\Models\LogActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use App\Services\SessionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;

class MasterManagementService
{
    /**
     * Register services.
     *
     * @return void
     */

    public static function getRoleListOptions(){
        $current_role = AuthService::currentAccess()['role'];
        if ($current_role == 'super_admin') return new Role(); 
        $roles = Role::whereNotIn('name',['super_admin','admin']);
        return $roles;
    }
    public static function getDepartmentListOptions(){
        $current_role = AuthService::currentAccess()['role'];
            $current_dept_id = AuthService::currentAccess()['department_id'];
        if ($current_role == 'super_admin') return new Department(); 
        // $departments = Department::children()->orWhere('id',AuthService::currentAccess()['department_id'])->get();
        $departments = Department::where('id', $current_dept_id)
        ->orWhereHas('parent', function($q) use ($current_dept_id) {
            $q->where('id', $current_dept_id);
        })
        ;
        return $departments;
    }
    public static function getDepartmentList(){
        $dep_list = [];
        $user = AuthService::currentAccess();
        $userrole = $user['role'];

        if ($userrole == 'super_admin') {
                $dep_all = Department::all();
                 $dep_list[]=['value'=>0,'is_selected'=>true,'label'=>'Seluruh department '];
                foreach ($dep_all as $item) {
                    $dep_list[] = [
                        'value' => $item->id,
                        'is_selected' => false,
                        'label' => $item->name
                    ];
                }
            return $dep_list;
        }
        $get_current_dept = Department::find($user['department_id']);  

        if ($get_current_dept->approval_by =='self' && $userrole !=='user') {
            $child =$get_current_dept->children()->get()->toArray();
            $dep_list[]=['value'=>0,'is_selected'=>true,'label'=>'Seluruh department dibawah '.$get_current_dept->name];
            $dep_list[]=['value'=>$get_current_dept->id,'is_selected'=>false,'label'=>$get_current_dept->name];
            foreach ($child as $item) {
                $dep_list[] = ['value' => $item['id'],'is_selected'=>false, 'label' => $item['name']];
            }

        }else{
            $dep_list[]=['value'=>$get_current_dept->id,'is_selected'=>true,'label'=>$get_current_dept->name];
        }
        return $dep_list;
    }

    public static function generateUserProcessData(){ 
        $data = [];
        $seq_role_app = [1=>'drafter',2=>'finance',3=>'dekan',4=>'kabag',5=>'drafter',6=>'dekan']; 
        foreach ($seq_role_app as $key => $role) {
            if ($role == 'drafter') {
               $user = User::userProcessors()
                        ->whereDoesntHave('position.roles', function($q) {
                            $q->whereIn('name', ['kabag', 'dekan', 'finance']);
                        })
            ->first();
            }else{
                $user = User::userProcessors()
                ->whereHas('position.roles', function($q) use ($role) {
                    $q->where('name', $role);
                })->first();
            }

                $is_verificator = in_array($role,['finance','dekan']);
                $data[] = [
                        'user_id' => $user->id,
                        'user_text' => $user->name.($user->position?' - '.$user->position->name:'').($user->department?' - '.$user->department->name:''), // Assuming you want to store the name of the verifier
                        'sequence' => $key,
                        'status' => ($key == 1?1:0), 
                        'position_id' => $user->position_id,
                        'department_id' => AuthService::currentAccess()['department_id'], 
                        'created_by' => Auth::id(),
                        'role' => $role,
                        'role_text' => self::setRoleText($role,($key > 4? 2:1)),
                        'trans_type' => ($key > 4? 2:1),
                        'is_verificator' => $is_verificator,
                ];
        }

            return $data;

        }

        public static function setRoleText($role,$trans_type){
            $role_transType = $role.'_'.$trans_type;
            $dictionary = [
                'drafter_1'=>'Drafter Pengajuan',
                'finance_1'=>'Verifikator Pengajuan',
                'dekan_1'=>'Penandatangan Pengajuan',
                'kabag_1'=>'Pemroses Nomor Surat',
                'drafter_2'=>'Drafter Laporan',
                'dekan_2'=>'Penandatangan Laporan',
            ];

            return $dictionary[$role_transType];
        } 

        public static function storeLogActivity($action,$reference_id,$reference_name=''){
            try {           
            $currrent_user = AuthService::currentAccess();
            $descriptions = [
                'submit' => 'User '.$currrent_user['name'].' telah mengajukan "'.$reference_name.'"',
                'submit-report' => 'User '.$currrent_user['name'].' telah mengirim laporan untuk "'.$reference_name.'"',
                'approve' => 'User '.$currrent_user['name'].' telah menyetujui pengajuan "'.$reference_name.'"',
                'approve-report' => 'User '.$currrent_user['name'].' telah menyetujui laporan "'.$reference_name.'"',
                'revise' => 'User '.$currrent_user['name'].' meminta revisi pada pengajuan "'.$reference_name.'"',
                'revise-report' => 'User '.$currrent_user['name'].' meminta revisi pada laporan "'.$reference_name.'"',
                'reject' => 'User '.$currrent_user['name'].' menolak pengajuan "'.$reference_name.'"',
                'reject-report' => 'User '.$currrent_user['name'].' menolak laporan "'.$reference_name.'"',
                'create-application' => 'User '.$currrent_user['name'].' membuat pengajuan baru "'.$reference_name.'"',
                'update-letter-number' => 'User '.$currrent_user['name'].' menambahkan nomor surat pada pengajuan "'.$reference_name.'"',
                'delete-application' => 'User '.$currrent_user['name'].' menghapus pengajuan "'.$reference_name.'"',
                'save-draft-application' => 'User '.$currrent_user['name'].' menyimpan draft pengajuan "'.$reference_name.'"',
                'create-user' => 'User '.$currrent_user['name'].' menambahkan user baru pada "'.$reference_name.'"',
                'create-departement' => 'User '.$currrent_user['name'].' menambahkan departemen baru pada "'.$reference_name.'"',
                'create-position' => 'User '.$currrent_user['name'].' menambahkan posisi baru pada "'.$reference_name.'"',
                'update-user' => 'User '.$currrent_user['name'].' memperbarui data user pada "'.$reference_name.'"',
                'update-departement' => 'User '.$currrent_user['name'].' memperbarui data departemen pada "'.$reference_name.'"',
                'update-position' => 'User '.$currrent_user['name'].' memperbarui data posisi pada "'.$reference_name.'"',
                'delete-user' => 'User '.$currrent_user['name'].' menghapus user pada "'.$reference_name.'"',
                'delete-departement' => 'User '.$currrent_user['name'].' menghapus departemen pada "'.$reference_name.'"',
                'delete-position' => 'User '.$currrent_user['name'].' menghapus posisi pada "'.$reference_name.'"',
                'update-manage-template' => 'User '.$currrent_user['name'].' memperbarui template dokumen pada "'.$reference_name.'"',
                'admin-regenerate-document' => 'User '.$currrent_user['name'].' melakukan regenerasi dokumen pada "'.$reference_name.'"',
                'admin-update-application' => 'User '.$currrent_user['name'].' memperbarui data pengajuan "'.$reference_name.'"',
                'user-login' => 'User '.$currrent_user['name'].' login ke aplikasi',
                'update-profile' => 'User '.$currrent_user['name'].' mengubah profile user "'.$reference_name.'"',
                'edit-detail' => 'User '.$currrent_user['name'].' mengubah detail "'.$reference_name.'"',
            ];

            $data = [
                'activity'=>$action,
                'user_id'=>$currrent_user['id'],
                'description'=>$descriptions[$action],
                'reference_id'=>$reference_id
            ];
    LogActivity::create($data);



             } catch (\Throwable $th) {
                //throw $th;
                Log::info($th);
            }

        }



        




        
        

        

   

    /**
     * Isi data dummy detail draft untuk sebuah application.
     *
     * Mengisi 5 tabel: application_details, application_schedules,
     * application_participants, application_draft_cost_budgets, dan
     * application_letter_numbers. Dibungkus transaksi agar all-or-nothing.
     *
     * Idempoten: memanggil ulang akan menghapus data dummy lama milik
     * application tsb (forceDelete) lalu mengisi ulang, sehingga tidak
     * menumpuk saat command dijalankan berkali-kali.
     *
     * @param int $applicationId
     * @return array{status:bool, message:string, counts?:array}
     */
    public static function seedDummyDraftDetail($applicationId): array
    {
        $app = Application::withoutGlobalScopes()->find($applicationId);
        if (!$app) {
            return ['status' => false, 'message' => "Application id {$applicationId} tidak ditemukan."];
        }

        $departmentId = $app->department_id;
        $createdBy    = $app->created_by;

        try {
            DB::beginTransaction();

            // Bersihkan data dummy lama agar idempoten (hard delete).
            $app->detail()->forceDelete();
            $app->schedules()->forceDelete();
            $app->participants()->forceDelete();
            // $app->draftCostBudgets()->forceDelete();
            // $app->letterNumbers()->forceDelete();

            $counts = [];

            // 1) application_details (hasOne)
            ApplicationDetail::create([
                'application_id'        => $app->id,
                'department_id'         => $departmentId,
                'created_by'            => $createdBy,
                'activity_outcome'      => 'Meningkatnya kompetensi peserta pada bidang terkait.',
                'activity_output'       => 'Laporan kegiatan dan dokumentasi.',
                'performance_indicator' => 'Jumlah peserta yang lulus pelatihan',
                'unit_of_measurment'    => 'Orang',
                'activity_volume'       => '30',
                'general_description'   => 'Kegiatan dummy untuk keperluan pengujian sistem.',
                'objectives'            => 'Menguji alur pembuatan dokumen dari data dummy.',
                'beneficiaries'         => 'Peserta dan panitia kegiatan.',
                'activity_scope'        => 'Lingkup internal instansi.',
                'implementation_method' => 'Luring (tatap muka).',
                'implementation_stages' => 'Persiapan, pelaksanaan, evaluasi.',
                'activity_dates'        => self::dummyActivityDatesString(),
                'activity_location'     => 'Aula Utama, Gedung A',
            ]);
            $counts['application_details'] = 1;

            // 2) application_participants
            $participants = self::dummyParticipants($app->id, $departmentId, $createdBy);
            foreach ($participants as $p) {
                ApplicationParticipant::create($p);
            }
            $counts['application_participants'] = count($participants);

            // 3) application_schedules (rundown)
            $schedules = self::dummySchedules($app->id, $departmentId, $createdBy);
            foreach ($schedules as $s) {
                ApplicationSchedule::create($s);
            }
            $counts['application_schedules'] = count($schedules);

            // 4) application_draft_cost_budgets
            $draftCosts = self::dummyDraftCostBudgets($app->id, $departmentId, $createdBy);
            foreach ($draftCosts as $d) {
                ApplicationDraftCostBudget::create($d);
            }
            $counts['application_draft_cost_budgets'] = count($draftCosts);

            // 5) application_letter_numbers
            // $letterNumbers = self::dummyLetterNumbers($app->id, $departmentId, $createdBy);
            // foreach ($letterNumbers as $l) {
            //     ApplicationLetterNumber::create($l);
            // }
            // $counts['application_letter_numbers'] = count($letterNumbers);

            DB::commit();

            return [
                'status'  => true,
                'message' => "Berhasil mengisi data dummy untuk application id {$app->id}.",
                'counts'  => $counts,
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('seedDummyDraftDetail gagal: ' . $th->getMessage(), [
                'application_id' => $applicationId,
            ]);
            return ['status' => false, 'message' => 'Gagal mengisi data dummy: ' . $th->getMessage()];
        }
    }

    /**
     * Tiga tanggal berurutan (format "d-m-Y" dipisah koma), seperti activity_dates asli.
     */
    private static function dummyActivityDatesString(): string
    {
        $base = Carbon::today()->addDays(14);
        $dates = [];
        for ($i = 0; $i < 3; $i++) {
            $dates[] = $base->copy()->addDays($i)->format('d-m-Y');
        }
        return implode(',', $dates);
    }

    private static function dummyParticipants($applicationId, $departmentId, $createdBy): array
    {
        $common = [
            'application_id' => $applicationId,
            'department_id'  => $departmentId,
            'created_by'     => $createdBy,
        ];

        return [
            // Ketua panitia (wajib ada 1 signer committee).
            array_merge($common, [
                'participant_type_id' => 1, // Panitia
                'name'                => 'Budi Santoso',
                'institution'         => 'Instansi Dummy',
                'commitee_position'   => 'Ketua',
                'is_signer_commitee'  => 1,
                'is_option_rundown'   => 0,
            ]),
            array_merge($common, [
                'participant_type_id' => 1, // Panitia
                'name'                => 'Siti Aminah',
                'institution'         => 'Instansi Dummy',
                'commitee_position'   => 'Sekretaris',
                'is_signer_commitee'  => 0,
                'is_option_rundown'   => 0,
            ]),
            array_merge($common, [
                'participant_type_id' => 2, // Narasumber
                'name'                => 'Dr. Andi Wijaya',
                'institution'         => 'Universitas Contoh',
                'is_option_rundown'   => 1,
            ]),
            array_merge($common, [
                'participant_type_id' => 4, // Moderator
                'name'                => 'Rina Kartika',
                'institution'         => 'Lembaga Contoh',
                'is_option_rundown'   => 1,
            ]),
            array_merge($common, [
                'participant_type_id' => 3, // Peserta
                'name'                => 'Agus Prasetyo',
                'institution'         => 'Instansi Dummy',
                'is_option_rundown'   => 0,
            ]),
        ];
    }

    /**
     * Rundown dummy. speaker_text/moderator_text: "nama-instansi" dipisah ";".
     * officer_text: "nama###instansi###participant_type_id" dipisah ";".
     */
    private static function dummySchedules($applicationId, $departmentId, $createdBy): array
    {
        $date = Carbon::today()->addDays(14)->format('Y-m-d');
        $common = [
            'application_id' => $applicationId,
            'department_id'  => $departmentId,
            'created_by'     => $createdBy,
            'date'           => $date,
        ];

        return [
            array_merge($common, [
                'name'           => 'Registrasi & Pembukaan',
                'start_date'     => $date . ' 08:00:00',
                'end_date'       => $date . ' 09:00:00',
                'speaker_text'   => null,
                'moderator_text' => null,
                'officer_text'   => 'Budi Santoso###Instansi Dummy###1',
            ]),
            array_merge($common, [
                'name'           => 'Materi Sesi 1',
                'start_date'     => $date . ' 09:00:00',
                'end_date'       => $date . ' 11:00:00',
                'speaker_text'   => 'Dr. Andi Wijaya-Universitas Contoh',
                'moderator_text' => 'Rina Kartika-Lembaga Contoh',
                'officer_text'   => 'Dr. Andi Wijaya###Universitas Contoh###2;Rina Kartika###Lembaga Contoh###4',
            ]),
            array_merge($common, [
                'name'           => 'Penutupan',
                'start_date'     => $date . ' 11:00:00',
                'end_date'       => $date . ' 12:00:00',
                'speaker_text'   => null,
                'moderator_text' => null,
                'officer_text'   => null,
            ]),
        ];
    }

    private static function dummyDraftCostBudgets($applicationId, $departmentId, $createdBy): array
    {
        $common = [
            'application_id' => $applicationId,
            'department_id'  => $departmentId,
            'created_by'     => $createdBy,
        ];

        $rows = [
            ['code' => 'A', 'item' => 'Belanja Bahan',        'sub_item' => 'Konsumsi peserta', 'volume' => '30', 'unit' => 'Orang', 'cost_per_unit' => 50000],
            ['code' => 'A', 'item' => 'Belanja Bahan',        'sub_item' => 'ATK & penggandaan', 'volume' => '1',  'unit' => 'Paket', 'cost_per_unit' => 500000],
            ['code' => 'B', 'item' => 'Honor Narasumber',     'sub_item' => 'Honor narasumber',  'volume' => '2',  'unit' => 'JP',    'cost_per_unit' => 1000000],
            ['code' => 'C', 'item' => 'Belanja Perjalanan',   'sub_item' => 'Transport lokal',   'volume' => '2',  'unit' => 'Orang', 'cost_per_unit' => 300000],
        ];

        return array_map(function ($r) use ($common) {
            $total = (int) $r['volume'] * (int) $r['cost_per_unit'];
            return array_merge($common, $r, ['total' => $total]);
        }, $rows);
    }

    private static function dummyLetterNumbers($applicationId, $departmentId, $createdBy): array
    {
        $today = Carbon::today()->format('Y-m-d');

        $fields = [
            ['letter_name' => 'mak',                          'letter_label' => 'MAK',                                        'type_field' => 'text',     'is_with_date' => 0, 'letter_number' => '523.001',           'letter_date' => null],
            ['letter_name' => 'keterangan_mak',               'letter_label' => 'Poin No 7 SK terkait MAK',                   'type_field' => 'textarea', 'is_with_date' => 0, 'letter_number' => 'Keterangan MAK dummy', 'letter_date' => null],
            ['letter_name' => 'nomor_sk',                     'letter_label' => 'Nomor Sk',                                   'type_field' => 'text',     'is_with_date' => 1, 'letter_number' => 'SK/001/DUMMY',      'letter_date' => $today],
            ['letter_name' => 'tanggal_berlaku_sk',           'letter_label' => 'Tanggal Berlaku SK',                         'type_field' => 'date',     'is_with_date' => 0, 'letter_number' => $today,              'letter_date' => null],
            ['letter_name' => 'nomor_surat_permohonan',       'letter_label' => 'Nomor Surat Permohonan Narasumber/Moderator','type_field' => 'text',     'is_with_date' => 1, 'letter_number' => 'SP/001/DUMMY',      'letter_date' => $today],
            ['letter_name' => 'nomor_surat_tugas',            'letter_label' => 'Nomor Surat Tugas Narasumber/Moderator',     'type_field' => 'text',     'is_with_date' => 1, 'letter_number' => 'ST/001/DUMMY',      'letter_date' => $today],
            ['letter_name' => 'nomor_surat_tugas_peserta',    'letter_label' => 'Nomor Surat Tugas Peserta',                  'type_field' => 'text',     'is_with_date' => 1, 'letter_number' => 'ST/002/DUMMY',      'letter_date' => $today],
            ['letter_name' => 'nomor_surat_undangan_peserta', 'letter_label' => 'Nomor Surat Undangan Peserta',               'type_field' => 'text',     'is_with_date' => 1, 'letter_number' => 'SU/001/DUMMY',      'letter_date' => $today],
        ];

        return array_map(function ($f) use ($applicationId, $departmentId, $createdBy) {
            return array_merge($f, [
                'application_id' => $applicationId,
                'department_id'  => $departmentId,
                'created_by'     => $createdBy,
            ]);
        }, $fields);
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
