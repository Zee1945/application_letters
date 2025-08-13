<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Support\ServiceProvider;
use App\Services\SessionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class MasterManagementService
{
    /**
     * Register services.
     *
     * @return void
     */

    public static function getDepartmentList(){
        $dep_list = [];
        $user = AuthService::currentAccess();
        $userrole = $user['role'];

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


    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

    }
}
