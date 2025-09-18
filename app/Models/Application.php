<?php

namespace App\Models;

use App\Models\Scopes\DepartmentScope;
use App\Services\AuthService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Helper\ProcessHelper;

class Application extends AbstractModel
{
    // use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'activity_name',
        'funding_source',
        'approval_status',
        'current_user_approval',
        'user_approval_ids',
        'draft_step_saved ',
        'note',
        'delete_note',
        'department_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function detail()
    {
        return $this->hasOne(ApplicationDetail::class);
    }
    public function draftCostBudgets()
    {
        return $this->hasMany(ApplicationDraftCostBudget::class);
    }
    public function letterNumbers()
    {
        return $this->hasMany(ApplicationLetterNumber::class);
    }
    public function participants()
    {
        return $this->hasMany(ApplicationParticipant::class);
    }
    public function report()
    {
        return $this->hasOne(ApplicationReport::class);
    }
    public function schedules()
    {
        return $this->hasMany(ApplicationSchedule::class);
    }
    public function userApprovals()
    {
        return $this->hasMany(ApplicationUserApproval::class);
    }

    public function currentUserApproval()
    {
        return $this->belongsTo(ApplicationUserApproval::class, 'current_user_approval', 'user_id');
    }
    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
    public function applicationFiles()
    {
        return $this->hasMany(ApplicationFile::class);
    }
    public function scopeNeedMyProcess(Builder $query)
    {
        $user = AuthService::currentAccess();
        return $query->where(function($query)use($user){
            $q = $query->where('current_user_approval',$user['id'])->where('approval_status','<',12);
            if ($user['role'] == 'finance') {
                $q = $q->where('approval_status','>',0);
            }
            return $q;
        })->orWhere(function($query)use ($user){
            if ($user['role'] == 'kabag') {
                return $query->where('approval_status',11);
            }
            return $query;
        })->orWhere(function($query) use ($user){
            $query->where('created_by',$user['id'])->where('approval_status',0);
        });
    }
    public function scopeRejected()
    {
        return $this->hasMany(ApplicationFile::class);
    }
    public function scopeOngoing()
    {
        return $this->hasMany(ApplicationFile::class);
    }

    public function scopeGetByDepartemnt(Builder $query, $department_id,$with_children=false){
        if ($with_children) {
            return $query->whereHas('department',function($query) use($department_id){
                    $query
                    ->where('parent_id',$department_id)
                    ->orWhere('id',$department_id);
            });
        }else{
            return $query->where('department_id',$department_id);
        }
    }

}
