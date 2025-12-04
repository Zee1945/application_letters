<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Scopes\DepartmentScope;
use App\Services\AuthService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_without_degree',
        'email',
        'password',
        'department_id',
        'position_id',
        'delete_note',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    public function scopeTes($query)
    {
        $userAuth = Auth::user();
        if ($userAuth) {
            if ($userAuth->hasRole('superadmin')) {
                return $query;
            } else {
                return $query->where('department_id', $userAuth->department_id)
                             ->role(['dekan', 'finance']);
            }
        }
        return $query;
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function scopeUserProcessors($query)
    {
        $user= Auth::user();
        if ($user){
            $check_is_admin = $user->position->hasRole('super_admin');
            if ($check_is_admin) {
                return $query;
            } else {
                $data = $query->where('id',$user->id)->orWhereHas('department',function($dq)use($user){
                    if ($user->department->approval_by == 'self') {
                        return $dq->where('id', $user->department->id);
                    }else if($user->department->approval_by == 'parent'){
                        return $dq->where('id', $user->department->parent->id);
                    }
                })->whereHas('position', function ($sub_query) {
                    return $sub_query->role(['dekan','finance','kabag']);
                });
                // dd($data);
                return $data;
            }
        }
    }

    public function scopeApprovers($query)
    {
        $user = Auth::user();
        if ($user) {
            $check_is_admin = $user->position->hasRole('super_admin');
            if ($check_is_admin) {
                return $query;
            } else {
                $data = $query->whereHas('department',function($dq)use($user){
                    if ($user->department->approval_by == 'self') {
                        return $dq->where('id', $user->department->id);
                    }else if($user->department->approval_by == 'parent'){
                        return $dq->where('id', $user->department->parent->id);
                    }
                })->whereHas('position', function ($sub_query) {
                    return $sub_query->role(['dekan', 'finance']);
                });
                // dd($data);
                return $data;
            }
        }
        return $query;
    }




public function scopeRestricted($query)
{
    $current_user = AuthService::currentAccess();
    // Jika super_admin, tampilkan semua user
    if ($current_user['role'] == 'super_admin') {
        return $query;
    }

    // Jika admin, tampilkan user yang bukan super_admin dan dari departemen sendiri atau anaknya
    if ($current_user['role'] == 'admin') {
        // Ambil id departemen sendiri dan anak-anaknya
        $departmentIds = [$current_user['department_id']];
        $childDepartments = Department::where('parent_id', $current_user['department_id'])->pluck('id')->toArray();
        $departmentIds = array_merge($departmentIds, $childDepartments);

        return $query->whereHas('position', function ($q) {
                $q->whereDoesntHave('roles', function ($qr) {
                    $qr->where('name', 'super_admin');
                });
            })
            ->whereIn('department_id', $departmentIds);
    }

    // Untuk role lain, bisa tambahkan filter sesuai kebutuhan
    // return $query;
}
public function scopeRolePosition($query, $role, $department_id = null)
{
    if ($department_id) {
        $department = Department::find($department_id);
        // dd($role);
        if ($department && $role !='user') {
            $query = $query->whereHas('department', function($q) use($department) {
                if ($department->approval_by == 'self') {
                    return $q->where('id', $department->id);
                } else if ($department->approval_by == 'parent') {
                    return $q->where('id', $department->parent_id);
                } else if ($department->approval_by == 'central') {
                    return $q->where('id', 1);
                }
            });
        }else{
            $query = $query->where('department_id', $department->id);
        }
    }
    
    return $query->whereHas('position', function($q) use($role) {
        $q->role($role);
    });
}
}
