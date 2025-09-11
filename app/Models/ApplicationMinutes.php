<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplicationMinutes extends Model
{
    use HasFactory;

    protected $table = 'application_minutes';
        protected $fillable = [
        'introduction',
        'topic',
        'explanation',
        'deadline',
        'follow_up',
        'assignee',
        'delete_note',
        'created_by',
        'deleted_by',
        'department_id',
        'application_id',
        'updated_by',
        'created_by',
        'updated_by',
    ];


    public function application()
    {
        return $this->belongsTo(Application::class);
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
