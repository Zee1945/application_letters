<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'start_date',
        'end_date',
        'status',
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
