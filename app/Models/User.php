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

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
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

    public function scopeRolePosition($query, $role){
        return $query->whereHas('position',function($q) use($role){
            $q->role($role);
        });
    }
}
