<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationDetail extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'application_id',
        'activity_outcome',
        'activity_output',
        'performance_indicator',
        'activity_volume',
        'general_description',
        'objectives',
        'beneficiaries',
        'activity_scope',
        'implementation_method',
        'implementation_stages',
        'activity_start_date',
        'activity_end_date',
        'activity_location',
        'department_id',
        'delete_note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function participantType()
    {
        return $this->belongsTo(ParticipantType::class);
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
