<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class LogApproval extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'notes',
        'location_city',
        'action',
        'trans_type',
        'user_id',
        'application_id',
        'position_id',
        'department_id',
        'created_by',
        'updated_by',
        'deleted_note',
        'deleted_at',
        'deleted_by',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function application(){
        return $this->belongsTo(Application::class);
    }
    public function position(){
        return $this->belongsTo(Position::class);
    }
    public function department(){
        return $this->belongsTo(Department::class);
    }

    public function scopeGetSigner($query,$role_id,$department_id,$trans_type,$app_id){
        $role = Role::find($role_id);
        $app = Application::find($app_id);
        $user = User::rolePosition($role->name,$app->department_id)->first();
        // Log::info('pas user => ',$user->toArray());
        // dd($user);
        return $query->where('user_id',$user->id)->where('application_id',$app_id)->where('trans_type',$trans_type)->where('department_id',$this->getIdDepartment($app->department,$role->name));
    }

    public function getIdDepartment(Department $department,$role_name){
        $approval_by = $department->approval_by;
        if ($role_name != 'user') {
            if ($approval_by == 'self') {
                return $department->id;
            }else if ($approval_by =='parent') {
                return $department->parent_id;
            }else if ($approval_by == 'central') {
                return 1;
            }
        }else{
             return $department->id;
        }

    }

    // public function scopeGetSigner($query,$role_id,$department_id,$trans_type,$app_id){
    //     $role = Role::find($role_id);
    //     $app = Application::find($app_id);
    //     $department = Department::find($department_id);
    //     // dd($role->name);

    //     if ($role->name == 'user') {
    //         $user = User::where('department_id',$department_id)->role($role->name)->first();
    //     }else{
    //         $user = User::rolePosition($role->name)->whereHas('department',function($q)use($department){
    //             if ($department->approval_by == 'self') {
    //                 return $q->where('id',$department->id);
    //             }else if ($department->approval_by == 'parent') {
    //                 return $q->where('id',$department->parent_id);
    //             }else if ($department->approval_by == 'central') {
    //                 return $q->where('id',1);
    //             }
    //         });
    //     }
        


    //     return $query->where('user_id',$user->id)->where('application_id',$app_id)->where('trans_type',$trans_type)->where('department_id',$department_id);
    // }
}
