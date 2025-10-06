<?php

namespace App\Services;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use App\Services\SessionService;
use Illuminate\Support\Facades\Auth;
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




        
        

        

   

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
