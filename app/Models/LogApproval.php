<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class LogApproval extends Model
{
    use HasFactory;
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

    public function scopeGetSigner($query,$role_id,$department_id,$app_id){
        $role = Role::find($role_id);
        $user = User::where('department_id',$department_id)->role($role->name)->first();
        return $query->where('user_id',$user->id)->where('application_id',$app_id)->where('department_id',$department_id);
    }
}
