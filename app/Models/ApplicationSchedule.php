<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationSchedule extends Model
{
    use HasFactory;
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'date',
        'moderator_text',
        'speaker_text',
        'department_id',
        'application_id',
        'delete_note',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
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
    public function userApprovals()
    {
        return $this->hasMany(ApplicationUserApproval::class);
    }
}
