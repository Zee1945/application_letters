<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
